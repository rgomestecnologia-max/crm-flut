<?php

namespace App\Jobs;

use App\Models\Automation;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\CrmCard;
use App\Models\Department;
use App\Models\Message;
use App\Services\EvolutionApiService;
use App\Services\MetaWhatsAppService;
use App\Services\WhatsAppProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAutomationMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public Automation $automation,
        public Contact    $contact,
        public CrmCard    $card,
    ) {}

    public function handle(): void
    {
        app(\App\Services\CurrentCompany::class)->set((int) $this->automation->company_id, persist: false);

        try {
            $conversation = Conversation::where('contact_id', $this->contact->id)
                ->where('is_group', false)
                ->whereIn('status', ['open', 'pending'])
                ->latest()
                ->first();

            if (!$conversation) {
                $department = Department::active()->first();
                if (!$department) {
                    Log::warning('SendAutomationMessage: nenhum departamento ativo.');
                    return;
                }

                $assignedTo  = null;
                $deptId      = $department->id;
                $phone       = $this->contact->phone;
                if ($phone && str_starts_with($phone, '55') && strlen($phone) >= 12) {
                    $ddd = substr($phone, 2, 2);
                    $rule = \App\Models\DddRoutingRule::where('ddd', $ddd)->where('is_active', true)->first();
                    if ($rule) {
                        $assignedTo = $rule->agent_id;
                        if ($rule->department_id) {
                            $deptId = $rule->department_id;
                        }
                        Log::info('DDD routing: ' . $ddd . ' → agent ' . $rule->agent_id . ' dept ' . $deptId, [
                            'contact' => $this->contact->name,
                        ]);
                    }
                }

                $conversation = Conversation::create([
                    'contact_id'           => $this->contact->id,
                    'department_id'        => $deptId,
                    'assigned_to'          => $assignedTo,
                    'status'               => 'open',
                    'is_group'             => false,
                    'source_automation_id' => $this->automation->id,
                ]);

                if ($assignedTo) {
                    $agentName = \App\Models\User::find($assignedTo)?->name ?? '?';
                    $deptName  = Department::find($deptId)?->name ?? '?';
                    Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_type'     => 'system',
                        'content'         => "Roteamento DDD: atribuído a {$agentName} ({$deptName})",
                        'type'            => 'text',
                        'delivery_status' => 'sent',
                    ]);
                }
            } elseif (!$conversation->source_automation_id) {
                $conversation->update(['source_automation_id' => $this->automation->id]);
            }

            // ── Modo IA direta: saudação + IA responde à dúvida ────────
            if ($this->automation->ai_first_response) {
                $greeting = $this->automation->message_template
                    ? str_replace('{nome}', $this->contact->name ?? '', $this->automation->message_template)
                    : "Olá, {$this->contact->name}! 👋\nSeja bem-vindo(a)! Recebemos sua mensagem e seu atendimento continuará por aqui. 🚀";

                $result = $this->sendWhatsApp($greeting);
                $zapiId = $result['key']['id'] ?? $result['messageId'] ?? null;

                // Captura LID (apenas Evolution)
                $returnedJid = $result['key']['remoteJid'] ?? null;
                if ($returnedJid && str_contains($returnedJid, '@lid') && !$this->contact->chat_lid) {
                    $this->contact->update(['chat_lid' => $returnedJid]);
                }

                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_type'     => 'agent',
                    'sender_id'       => null,
                    'content'         => $greeting,
                    'type'            => 'text',
                    'zapi_message_id' => $zapiId,
                    'delivery_status' => 'sent',
                ]);
                $conversation->update(['last_message_at' => now()]);

                // Registra a dúvida do cliente como mensagem na conversa
                $clientMessage = $this->contact->notes;
                $contactMsg = null;
                if ($clientMessage) {
                    $contactMsg = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_type'     => 'contact',
                        'content'         => $clientMessage,
                        'type'            => 'text',
                        'delivery_status' => 'delivered',
                    ]);
                    $conversation->update(['last_message_at' => now()]);
                }

                // Dispara a IA para responder à dúvida
                $botConfig = \App\Models\AiBotConfig::current();
                if ($botConfig && $botConfig->is_active && $botConfig->hasKey() && $contactMsg) {
                    \App\Jobs\ProcessBotResponse::dispatch(
                        $conversation, $botConfig, $contactMsg->id
                    );
                }

                Log::info('ai_first_response: saudação + IA', [
                    'contact' => $this->contact->name,
                    'message' => $clientMessage,
                ]);

                return;
            }

            // ── Modo padrão: envia mensagem template fixa ────────────────
            $this->sendTemplateMessage($conversation);

            Log::info('Automação disparada', [
                'automation' => $this->automation->name,
                'contact'    => $this->contact->phone,
            ]);

        } catch (\Throwable $e) {
            Log::error('SendAutomationMessage falhou', [
                'automation' => $this->automation->id,
                'contact'    => $this->contact->phone,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function sendTemplateMessage(Conversation $conversation): void
    {
        $card = $this->card->load(['pipeline', 'stage', 'fieldValues.field']);
        $text = $this->automation->renderMessage($this->contact, $card);

        // Se provider é Meta e tem template configurado, envia via template
        if (WhatsAppProvider::isMeta() && $this->automation->meta_template_name) {
            $result = $this->sendMetaTemplate();
            // Usa o texto renderizado como conteúdo local da mensagem
        } else {
            $result = $this->sendWhatsApp($text);
        }

        $zapiId = $result['key']['id'] ?? $result['messageId'] ?? null;

        // Captura LID (apenas Evolution)
        $returnedJid = $result['key']['remoteJid'] ?? null;
        if ($returnedJid && str_contains($returnedJid, '@lid') && !$this->contact->chat_lid) {
            $this->contact->update(['chat_lid' => $returnedJid]);
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'agent',
            'sender_id'       => null,
            'content'         => $text,
            'type'            => 'text',
            'zapi_message_id' => $zapiId,
            'delivery_status' => 'sent',
        ]);

        $conversation->update(['last_message_at' => now(), 'status' => 'open']);
    }

    /**
     * Envia mensagem via template Meta WhatsApp.
     */
    private function sendMetaTemplate(): array
    {
        $service = WhatsAppProvider::service();
        if (!$service || !($service instanceof MetaWhatsAppService)) {
            return ['success' => false];
        }

        $realPhone = ($this->contact->phone && preg_match('/^55\d{10,11}$/', $this->contact->phone)) ? $this->contact->phone : null;
        $phone = $realPhone ?? $this->contact->chat_lid ?? $this->contact->phone;

        // Monta parâmetros do body com dados do contato
        $bodyParams = [];
        $template = \App\Models\MetaMessageTemplate::where('name', $this->automation->meta_template_name)->first();
        if ($template) {
            $paramCount = $template->body_parameter_count;
            // Preenche automaticamente: {{1}} = nome, {{2}} = telefone, etc.
            if ($paramCount >= 1) $bodyParams[] = $this->contact->name ?? '';
            if ($paramCount >= 2) $bodyParams[] = $this->contact->phone ?? '';
            if ($paramCount >= 3) $bodyParams[] = $this->contact->email ?? '';
        }

        return $service->sendTemplate(
            $phone,
            $this->automation->meta_template_name,
            $template->language ?? 'pt_BR',
            $bodyParams,
        );
    }

    /**
     * Envia texto via provider ativo (Evolution ou Meta).
     */
    private function sendWhatsApp(string $text): array
    {
        $service = WhatsAppProvider::service();
        if (!$service) {
            Log::error('SendAutomationMessage: nenhum provider WhatsApp ativo');
            return ['success' => false];
        }

        $realPhone = ($this->contact->phone && preg_match('/^55\d{10,11}$/', $this->contact->phone)) ? $this->contact->phone : null;
        $phone = $realPhone ?? $this->contact->chat_lid ?? $this->contact->phone;

        return $service->sendText($phone, $text);
    }
}
