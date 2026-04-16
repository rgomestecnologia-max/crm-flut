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

            // Se a conversa já tem agente, o bot não deve enviar menu nem "opção inválida".
            // Limpa o menu_awaiting (caso tenha ficado stale) e delega para a IA se ativa.
            if ($this->conversation->assigned_to) {
                if ($this->conversation->menu_awaiting) {
                    $this->conversation->update(['menu_awaiting' => false]);
                }
                if ($this->botConfig && $this->botConfig->is_active && $this->botConfig->hasKey()) {
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
        $departments = Department::active()->orderBy('sort_order')->orderBy('name')->get();
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
        $departments = Department::active()->orderBy('sort_order')->orderBy('name')->get();

        if ($choice < 1 || $choice > $departments->count()) {
            $this->sendInvalidOption();
            return;
        }

        $department = $departments->get($choice - 1);

        // Roteia para o departamento escolhido
        $this->conversation->update([
            'department_id' => $department->id,
            'menu_awaiting' => false,
            'status'        => 'open',
        ]);

        // Mensagem de confirmação
        $afterMsg = $this->config->after_selection_message;
        if ($afterMsg) {
            $confirmText = str_replace('{departamento}', $department->name, $afterMsg);
        } else {
            $confirmText = "Perfeito! Direcionando você para o setor de *{$department->name}*. Em breve um de nossos atendentes irá te responder. 😊";
        }

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
        $departments = Department::active()->orderBy('sort_order')->orderBy('name')->get();

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

    private function broadcast(Message $message): void
    {
        try {
            broadcast(new MessageReceived($message));
        } catch (\Throwable $e) {
            Log::warning('MenuBot: broadcast falhou', ['error' => $e->getMessage()]);
        }
    }
}
