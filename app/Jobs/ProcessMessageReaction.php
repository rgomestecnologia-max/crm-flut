<?php

namespace App\Jobs;

use App\Events\MessageReceived;
use App\Models\Message;
use App\Models\ZapiConfig;
use App\Services\CurrentCompany;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMessageReaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload) {}

    public function handle(): void
    {
        $companyId = $this->resolveCompanyId();

        if (!$companyId) {
            Log::warning('ProcessMessageReaction: payload sem empresa correspondente — descartando', [
                'instanceId'     => $this->payload['instanceId'] ?? null,
                'connectedPhone' => $this->payload['connectedPhone'] ?? null,
            ]);
            return;
        }

        app(CurrentCompany::class)->set($companyId, persist: false);

        try {
            // Z-API envia reações como ReceivedCallback com campo "reaction"
            // Estrutura: reaction.messageId = ID da mensagem reagida, reaction.value = emoji
            $reaction = $this->payload['reaction'] ?? [];

            // Z-API: reaction.referencedMessage.messageId = ID da mensagem reagida
            $targetZapiId = $reaction['referencedMessage']['messageId']
                ?? $reaction['messageId']
                ?? $this->payload['reactionMessage']['messageId']
                ?? null;

            $emoji = $reaction['value']
                ?? $this->payload['reactionMessage']['value']
                ?? $this->payload['emoji']
                ?? null;

            Log::info('Reaction recebida', [
                'targetZapiId' => $targetZapiId,
                'emoji'        => $emoji,
                'phone'        => $this->payload['phone'] ?? null,
                'reaction_raw' => $reaction,
            ]);

            $reactorPhone = preg_replace('/\D/', '', $this->payload['phone'] ?? '');

            if (!$targetZapiId || !$reactorPhone) {
                Log::info('Reaction: dados insuficientes', $this->payload);
                return;
            }

            $message = Message::where('zapi_message_id', $targetZapiId)->first();
            if (!$message) {
                Log::info('Reaction: mensagem original não encontrada', ['zapiId' => $targetZapiId]);
                return;
            }

            $reactions = $message->reactions ?? [];

            // Remove reação anterior deste telefone
            $reactions = array_values(array_filter($reactions, fn($r) => $r['phone'] !== $reactorPhone));

            // Adiciona nova reação (emoji vazio = remoção de reação)
            if ($emoji) {
                $reactions[] = [
                    'emoji' => $emoji,
                    'phone' => $reactorPhone,
                    'at'    => now()->toISOString(),
                ];
            }

            $message->update(['reactions' => $reactions]);

            // Broadcast para atualizar o chat em tempo real
            try {
                broadcast(new MessageReceived($message));
            } catch (\Throwable $e) {
                Log::warning('Reaction broadcast falhou', ['error' => $e->getMessage()]);
            }

            Log::info('Reaction processada', [
                'message_id' => $message->id,
                'emoji'      => $emoji,
                'phone'      => $reactorPhone,
            ]);

        } catch (\Throwable $e) {
            Log::error('ProcessMessageReaction falhou', ['error' => $e->getMessage(), 'payload' => $this->payload]);
        }
    }

    private function resolveCompanyId(): ?int
    {
        $instanceId     = $this->payload['instanceId'] ?? null;
        $connectedPhone = $this->payload['connectedPhone'] ?? null;

        $query = ZapiConfig::withoutCompanyScope();

        if ($instanceId) {
            $companyId = (clone $query)->where('instance_id', $instanceId)->value('company_id');
            if ($companyId) return (int) $companyId;
        }

        if ($connectedPhone) {
            $normalized = preg_replace('/\D/', '', $connectedPhone);
            $companyId  = (clone $query)->where('phone_number', $normalized)->value('company_id');
            if ($companyId) return (int) $companyId;
        }

        return null;
    }
}
