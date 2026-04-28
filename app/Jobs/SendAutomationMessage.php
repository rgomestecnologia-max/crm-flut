<?php

namespace App\Jobs;

use App\Models\Automation;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\CrmCard;
use App\Models\Department;
use App\Models\EvolutionApiConfig;
use App\Models\Message;
use App\Services\EvolutionApiService;
use App\Services\ZapiService;
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

    public function handle(ZapiService $zapi): void
    {
        app(\App\Services\CurrentCompany::class)->set((int) $this->automation->company_id, persist: false);

        try {
            // Cria/reabre conversa primeiro (necessário para ambos os fluxos)
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

                // Roteamento por DDD — atribui agente e departamento conforme telefone
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
                // 1) Envia saudação via WhatsApp (usa message_template da automação)
                $greeting = $this->automation->message_template
                    ? str_replace('{nome}', $this->contact->name ?? '', $this->automation->message_template)
                    : "Olá, {$this->contact->name}! 👋\nSeja bem-vindo(a)! Recebemos sua mensagem e seu atendimento continuará por aqui. 🚀";

                $evolutionConfig = EvolutionApiConfig::current();
                $realPhone = ($this->contact->phone && preg_match('/^55\d{10,11}$/', $this->contact->phone)) ? $this->contact->phone : null;
                $phone = $realPhone ?? $this->contact->chat_lid ?? $this->contact->phone;

                if ($evolutionConfig && $evolutionConfig->is_active) {
                    $result = (new EvolutionApiService($evolutionConfig))->sendText($phone, $greeting);
                    $zapiId = $result['key']['id'] ?? null;
                } else {
                    $result = $zapi->sendTextMessage($this->contact->phone, $greeting);
                    $zapiId = $result['messageId'] ?? null;
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

                // 2) Registra a dúvida do cliente como mensagem na conversa
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

                // 3) Dispara a IA para responder à dúvida
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
            $this->sendTemplateMessage($conversation, $zapi);

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

    private function sendTemplateMessage(Conversation $conversation, ZapiService $zapi): void
    {
        $card = $this->card->load(['pipeline', 'stage', 'fieldValues.field']);
        $text = $this->automation->renderMessage($this->contact, $card);

        $evolutionConfig = EvolutionApiConfig::current();
        $useEvolution    = $evolutionConfig && $evolutionConfig->is_active;

        $realPhone = ($this->contact->phone && preg_match('/^55\d{10,11}$/', $this->contact->phone)) ? $this->contact->phone : null;
        $phone = $realPhone ?? $this->contact->chat_lid ?? $this->contact->phone;

        if ($useEvolution) {
            $result = (new EvolutionApiService($evolutionConfig))->sendText($phone, $text);
            $zapiId = $result['key']['id'] ?? $result['id'] ?? null;
        } else {
            $result = $zapi->sendTextMessage($this->contact->phone, $text);
            $zapiId = $result['messageId'] ?? null;
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
}
