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
            $card = $this->card->load(['pipeline', 'stage', 'fieldValues.field']);
            $text = $this->automation->renderMessage($this->contact, $card);

            // Usa Evolution API se ativa; caso contrário cai para Z-API
            $evolutionConfig = EvolutionApiConfig::current();
            $useEvolution    = $evolutionConfig && $evolutionConfig->is_active;

            $phone = $this->contact->chat_lid ?? $this->contact->phone;

            if ($useEvolution) {
                $result = (new EvolutionApiService($evolutionConfig))->sendText($phone, $text);
                $zapiId = $result['key']['id'] ?? $result['id'] ?? null;
            } else {
                $result = $zapi->sendTextMessage($this->contact->phone, $text);
                $zapiId = $result['messageId'] ?? null;
            }

            // Cria/reabre conversa para registrar a mensagem
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

                $conversation = Conversation::create([
                    'contact_id'          => $this->contact->id,
                    'department_id'       => $department->id,
                    'status'              => 'open',
                    'is_group'            => false,
                    'source_automation_id' => $this->automation->id,
                ]);
            } elseif (!$conversation->source_automation_id && $this->automation->enable_ai_on_reply) {
                // Marca conversa existente com a automação fonte para o gatilho de IA
                $conversation->update(['source_automation_id' => $this->automation->id]);
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
}
