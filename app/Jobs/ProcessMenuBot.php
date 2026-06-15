<?php

namespace App\Jobs;

use App\Events\MessageReceived;
use App\Models\AiBotConfig;
use App\Models\ChatbotMenuConfig;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMenuBot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public Conversation      $conversation,
        public ChatbotMenuConfig $config,
        public ?AiBotConfig      $botConfig,
        public int               $triggerMessageId,
    ) {}

    public function handle(): void
    {
        app(\App\Services\CurrentCompany::class)->set((int) $this->conversation->company_id, persist: false);

        try {
            $triggerMessage = Message::find($this->triggerMessageId);
            if (!$triggerMessage || $triggerMessage->sender_type !== 'contact') return;

            // Garante estado mais recente da conversa
            $this->conversation->refresh();

            // Evita resposta dupla
            $alreadyResponded = Message::where('conversation_id', $this->conversation->id)
                ->where('sender_type', 'agent')
                ->whereNull('sender_id')
                ->where('id', '>', $this->triggerMessageId)
                ->exists();
            if ($alreadyResponded) return;

            // Se a conversa já tem agente OU humano estava atendendo pelo WhatsApp,
            // o bot não deve enviar menu nem "opção inválida".
            $humanAttending = $this->conversation->assigned_to
                || $this->conversation->waiting_human_reason === 'Atendente respondeu pelo WhatsApp';

            if ($humanAttending) {
                if ($this->conversation->menu_awaiting) {
                    $this->conversation->update(['menu_awaiting' => false]);
                }
                if ($this->conversation->assigned_to && $this->botConfig && $this->botConfig->is_active && $this->botConfig->hasKey()) {
                    ProcessBotResponse::dispatch($this->conversation, $this->botConfig, $this->triggerMessageId);
                }
                return;
            }

            // Verifica horário de funcionamento (só para conversas sem agente)
            if (!$this->conversation->menu_awaiting) {
                if (!$this->config->isWithinBusinessHours()) {
                    $this->sendOutsideHoursMessage();
                    return;
                }
            }

            if ($this->conversation->menu_awaiting) {
                $this->processSelection($triggerMessage);
            } elseif ($this->menuAlreadyCompleted()) {
                // Menu já foi concluído nesta sessão — encaminha para IA
                if ($this->botConfig && $this->botConfig->is_active && $this->botConfig->hasKey()) {
                    ProcessBotResponse::dispatch($this->conversation, $this->botConfig, $this->triggerMessageId);
                }
            } else {
                $this->sendWelcomeMenu();
            }
        } catch (\Throwable $e) {
            Log::error('MenuBot: exceção', [
                'conv'  => $this->conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendWelcomeMenu(): void
    {
        $departments = $this->getMenuDepartments();
        if ($departments->isEmpty()) return;

        $contactName = $this->conversation->contact->name ?? 'cliente';
        $companyName = $this->config->company_name ?: config('app.name');

        $welcomeText = str_replace(
            ['{nome}', '{empresa}'],
            [$contactName, $companyName],
            $this->config->welcome_template
        );

        $lines = [$welcomeText, '', $this->config->menu_prompt, ''];
        foreach ($departments as $index => $dept) {
            $lines[] = ($index + 1) . ' - ' . $dept->name;
        }

        $fullText = implode("\n", $lines);

        $msg = $this->saveAndSend($fullText);

        // Marca conversa aguardando seleção
        $this->conversation->update(['menu_awaiting' => true]);

        Log::info('MenuBot: menu de boas-vindas enviado', ['conv' => $this->conversation->id]);
    }

    private function processSelection(Message $triggerMessage): void
    {
        $input = trim($triggerMessage->content ?? '');

        // Aceita só dígitos simples
        if (!ctype_digit($input)) {
            $this->sendInvalidOption();
            return;
        }

        $choice = (int) $input;
        $departments = $this->getMenuDepartments();

        if ($choice < 1 || $choice > $departments->count()) {
            $this->sendInvalidOption();
            return;
        }

        $department = $departments->get($choice - 1);

        $hasAi = $this->botConfig && $this->botConfig->is_active && $this->botConfig->hasKey();

        // Verifica se dept usa outro número WhatsApp
        $currentEvoId = $this->conversation->evolution_api_config_id;
        // Se dept não tem instância específica (NULL), usa a primeira instância da empresa (número principal)
        $deptEvoId = $department->evolution_api_config_id;
        if (!$deptEvoId) {
            $defaultConfig = \App\Models\EvolutionApiConfig::withoutGlobalScopes()
                ->where('company_id', $this->conversation->company_id)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
            $deptEvoId = $defaultConfig?->id;
        }
        $deptUsesOtherNumber = $deptEvoId && $deptEvoId !== $currentEvoId;

        if ($deptUsesOtherNumber) {
            // ── MULTI-NÚMERO: cria conversa separada no outro número ──

            // Verifica se já existe conversa nesse número (aberta ou resolvida) para evitar duplicatas
            $existingOtherConv = \App\Models\Conversation::where('contact_id', $this->conversation->contact_id)
                ->where('evolution_api_config_id', $deptEvoId)
                ->where('is_group', false)
                ->latest()
                ->first();

            if ($existingOtherConv && in_array($existingOtherConv->status, ['open', 'pending'])) {
                // Já tem conversa aberta nesse número — avisa e reenvia menu
                $deptList = $this->getMenuDepartments();
                $menuLines = ["Você já tem um atendimento em andamento no setor de *{$department->name}*. Aguarde a resposta pelo outro número. 😊", "", "Se quiser falar com outro setor, basta digitar o número da opção desejada:", ""];
                foreach ($deptList as $idx => $d) {
                    $menuLines[] = ($idx + 1) . ' - ' . $d->name;
                }
                $this->saveAndSend(implode("\n", $menuLines));
                $this->conversation->update(['menu_awaiting' => true]);
                return;
            }
            if ($existingOtherConv && $existingOtherConv->status === 'resolved') {
                // Reabre conversa resolvida em vez de criar nova
                $existingOtherConv->update([
                    'status'        => 'open',
                    'department_id' => $department->id,
                    'waiting_human_reason' => null,
                ]);
            }

            // Busca o número da nova instância
            $newConfig = \App\Models\EvolutionApiConfig::find($deptEvoId);
            $newNumber = '';
            if ($newConfig) {
                try {
                    $api = new \App\Services\EvolutionApiService($newConfig);
                    $info = $api->fetchInstances($newConfig->instance_name);
                    $ownerJid = $info[0]['ownerJid'] ?? null;
                    if ($ownerJid) {
                        $num = str_replace('@s.whatsapp.net', '', $ownerJid);
                        $newNumber = '(' . substr($num, 2, 2) . ') ' . substr($num, 4, 5) . '-' . substr($num, 9);
                    }
                } catch (\Throwable) {}
            }

            // Avisa o cliente na conversa original
            $transferMsg = "✅ Seu atendimento foi direcionado para o setor de *{$department->name}*.\n\n"
                . "📱 Você receberá uma mensagem do nosso número exclusivo desse setor"
                . ($newNumber ? " {$newNumber}" : "") . ".\n\n"
                . "Se quiser falar com outro setor, basta digitar o número da opção desejada:";
            $this->saveAndSend($transferMsg);

            // Reenvia menu na conversa original (mantém no dept/instância original)
            $this->conversation->update(['menu_awaiting' => true]);
            $deptList = $this->getMenuDepartments();
            $menuLines = [];
            foreach ($deptList as $idx => $d) {
                $menuLines[] = ($idx + 1) . ' - ' . $d->name;
            }
            $this->saveAndSend(implode("\n", $menuLines));

            // Usa conversa reaberta ou cria nova
            $newConv = ($existingOtherConv && $existingOtherConv->status === 'open')
                ? $existingOtherConv
                : \App\Models\Conversation::create([
                    'contact_id'             => $this->conversation->contact_id,
                    'department_id'          => $department->id,
                    'evolution_api_config_id' => $deptEvoId,
                    'status'                 => 'open',
                    'is_group'               => false,
                ]);

            // Envia boas-vindas pelo novo número
            $welcomeText = "Olá! Sou do setor de *{$department->name}* da {$this->config->company_name}. Como posso ajudar? 😊";
            $welcomeMsg = Message::create([
                'conversation_id' => $newConv->id,
                'sender_type'     => 'agent',
                'sender_id'       => null,
                'content'         => $welcomeText,
                'type'            => 'text',
                'delivery_status' => 'pending',
            ]);
            $newConv->update(['last_message_at' => now()]);
            \App\Jobs\SendWhatsAppMessage::dispatch($welcomeMsg);

            // Auto-criar card no pipeline do departamento
            $this->autoCreateCardAndTag($department);

            // Marca menu como concluído na nova conversa (evita URA reentrar)
            Message::create([
                'conversation_id' => $newConv->id,
                'sender_type'     => 'system',
                'content'         => "Menu: cliente selecionou {$department->name}",
                'type'            => 'text',
                'delivery_status' => 'sent',
            ]);

            // Sistema na conversa original
            Message::create([
                'conversation_id' => $this->conversation->id,
                'sender_type'     => 'system',
                'content'         => "Menu: cliente direcionado para {$department->name} (outro número)",
                'type'            => 'text',
                'delivery_status' => 'sent',
            ]);

            Log::info('MenuBot: multi-número — conversa separada criada', [
                'original' => $this->conversation->id, 'nova' => $newConv->id, 'dept' => $department->name,
            ]);
            return;
        }

        // ── MESMO NÚMERO: transfere conversa normalmente ──
        $this->conversation->update([
            'department_id'        => $department->id,
            'menu_awaiting'        => false,
            'status'               => 'open',
            'waiting_human_reason' => null,
        ]);

        $afterMsg = $this->config->after_selection_message;
        $confirmText = $afterMsg
            ? str_replace('{departamento}', $department->name, $afterMsg)
            : "Perfeito! Direcionando você para o setor de *{$department->name}*. Em breve um de nossos atendentes irá te responder. 😊";

        $msg = $this->saveAndSend($confirmText);

        // Mensagem de sistema no histórico
        $sysMsg = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_type'     => 'system',
            'content'         => 'Menu: cliente selecionou ' . $department->name,
            'type'            => 'text',
            'delivery_status' => 'sent',
        ]);
        $this->broadcast($sysMsg);

        // Machinery Prime (empresa 11): criar card no pipeline ao selecionar Comercial
        $companyId = app(\App\Services\CurrentCompany::class)->id();
        if ($companyId === 11 && $department->id === 21) {
            $this->createCardForDepartment($triggerMessage);
        }

        // Auto-criar card no pipeline correspondente ao departamento e taguear conversa
        // Busca pipeline pelo nome do departamento (match parcial)
        $this->autoCreateCardAndTag($department);

        // Ativa IA após seleção do menu (apenas na primeira opção / departamento principal)
        if ($hasAi && $choice === 1) {
            // Envia saudação da IA imediatamente após direcionamento
            $greeting = $this->botConfig->initial_greeting
                ?: 'Olá! Em que posso te ajudar?';
            $this->saveAndSend($greeting);

            Log::info('MenuBot: IA ativada após seleção do menu', ['conv' => $this->conversation->id, 'dept' => $department->name]);
        }

        Log::info('MenuBot: cliente selecionou departamento', [
            'conv' => $this->conversation->id,
            'dept' => $department->name,
        ]);
    }

    private function sendOutsideHoursMessage(): void
    {
        $message = $this->config->outside_hours_message;
        if (!$message) {
            $message = 'Olá! No momento estamos fora do horário de atendimento. Deixe sua mensagem que retornaremos assim que possível!';
        }

        $companyName = $this->config->company_name ?: config('app.name');
        $message = str_replace('{empresa}', $companyName, $message);

        // Evita enviar a mensagem de fora do horário mais de uma vez na mesma conversa
        $alreadySent = Message::where('conversation_id', $this->conversation->id)
            ->where('sender_type', 'agent')
            ->whereNull('sender_id')
            ->where('content', $message)
            ->where('created_at', '>=', now()->startOfDay())
            ->exists();

        if ($alreadySent) return;

        $this->saveAndSend($message);

        Log::info('MenuBot: mensagem fora do horário enviada', ['conv' => $this->conversation->id]);
    }

    private function sendInvalidOption(): void
    {
        $departments = $this->getMenuDepartments();

        $lines = [$this->config->invalid_option_message, '', $this->config->menu_prompt, ''];
        foreach ($departments as $index => $dept) {
            $lines[] = ($index + 1) . ' - ' . $dept->name;
        }

        $this->saveAndSend(implode("\n", $lines));
    }

    private function saveAndSend(string $content): Message
    {
        $msg = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_type'     => 'agent',
            'sender_id'       => null,
            'content'         => $content,
            'type'            => 'text',
            'delivery_status' => 'pending',
        ]);

        $this->conversation->update(['last_message_at' => now()]);
        SendWhatsAppMessage::dispatch($msg);
        $this->broadcast($msg);

        return $msg;
    }

    /**
     * Verifica se o menu já foi concluído nesta sessão da conversa,
     * procurando pela mensagem de sistema "Menu: cliente selecionou".
     */
    /**
     * Auto-cria card no pipeline e tagueia conversa quando o departamento tem
     * um pipeline CRM com nome correspondente (ex: "Xerox e impressão" → pipeline "Impressão").
     */
    private function autoCreateCardAndTag(Department $department): void
    {
        try {
            $contact = $this->conversation->contact;
            if (!$contact) return;

            // Busca pipeline cujo nome esteja contido no nome do departamento ou vice-versa
            $pipeline = \App\Models\CrmPipeline::get()->first(function ($p) use ($department) {
                return stripos($department->name, $p->name) !== false
                    || stripos($p->name, $department->name) !== false;
            });
            if (!$pipeline) return;

            $firstStage = $pipeline->stages()->orderBy('sort_order')->first();
            if (!$firstStage) return;

            // Cria card se não existir
            $card = \App\Models\CrmCard::where('contact_id', $contact->id)
                ->where('pipeline_id', $pipeline->id)->first();
            if (!$card) {
                $card = \App\Models\CrmCard::create([
                    'pipeline_id' => $pipeline->id,
                    'stage_id'    => $firstStage->id,
                    'contact_id'  => $contact->id,
                    'title'       => $contact->display_name ?? $contact->name,
                ]);
                Log::info('MenuBot: card criado automaticamente', [
                    'contact' => $contact->name, 'pipeline' => $pipeline->name,
                ]);
            }

            // OrangeXpress: preenche campo "Departamento" no card
            $companyId = app(\App\Services\CurrentCompany::class)->id();
            if ($companyId == 3 && $card) {
                $deptField = \App\Models\CrmCustomField::where('company_id', 3)->where('key', 'departamento')->first();
                if ($deptField) {
                    \App\Models\CrmCardFieldValue::updateOrCreate(
                        ['card_id' => $card->id, 'field_id' => $deptField->id],
                        ['value' => $department->name]
                    );
                }
            }

            // Tagueia conversa com tag do pipeline (se existir tag com mesmo nome)
            $tag = \App\Models\Tag::where('name', $pipeline->name)->first();
            if ($tag && !$this->conversation->tags()->where('tags.id', $tag->id)->exists()) {
                $this->conversation->tags()->attach($tag->id);
                Log::info('MenuBot: tag adicionada à conversa', [
                    'conv' => $this->conversation->id, 'tag' => $tag->name,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('MenuBot: autoCreateCardAndTag falhou', ['error' => $e->getMessage()]);
        }
    }

    private function menuAlreadyCompleted(): bool
    {
        return Message::where('conversation_id', $this->conversation->id)
            ->where('sender_type', 'system')
            ->where('content', 'like', 'Menu: cliente selecionou%')
            ->exists();
    }

    private function getMenuDepartments()
    {
        $query = Department::active()->orderBy('sort_order')->orderBy('name');

        if (!empty($this->config->menu_departments)) {
            $query->whereIn('id', $this->config->menu_departments);
        }

        return $query->get();
    }

    private function broadcast(Message $message): void
    {
        try {
            broadcast(new MessageReceived($message));
        } catch (\Throwable $e) {
            Log::warning('MenuBot: broadcast falhou', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Machinery Prime: cria card no Pipeline Comercial quando cliente seleciona departamento.
     */
    private function createCardForDepartment(Message $triggerMessage): void
    {
        try {
            $contact = $this->conversation->contact;
            if (!$contact) return;

            $pipeline   = \App\Models\CrmPipeline::where('name', 'Comercial')->first();
            $firstStage = $pipeline?->stages()->orderBy('sort_order')->first();
            if (!$pipeline || !$firstStage) return;

            $card = \App\Models\CrmCard::where('contact_id', $contact->id)
                ->where('pipeline_id', $pipeline->id)
                ->first();

            if (!$card) {
                $card = \App\Models\CrmCard::create([
                    'pipeline_id' => $pipeline->id,
                    'stage_id'    => $firstStage->id,
                    'contact_id'  => $contact->id,
                    'title'       => $contact->display_name,
                ]);

                \App\Models\CrmCardActivity::create([
                    'card_id' => $card->id,
                    'type'    => 'note',
                    'content' => 'Card criado automaticamente via chatbot (selecionou Comercial)',
                ]);

                Log::info('MenuBot: card criado automaticamente', [
                    'card'     => $card->id,
                    'contact'  => $contact->name,
                    'pipeline' => $pipeline->name,
                    'stage'    => $firstStage->name,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('MenuBot: createCardForDepartment falhou', ['error' => $e->getMessage()]);
        }
    }
}
