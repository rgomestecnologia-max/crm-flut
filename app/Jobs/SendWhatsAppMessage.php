<?php

namespace App\Jobs;

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

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Message $message,
        public ?string $base64Content = null,
    ) {}

    public function handle(): void
    {
        // A message já carrega o company_id pós-Fase 2.
        app(\App\Services\CurrentCompany::class)->set((int) $this->message->company_id, persist: false);

        $contact  = $this->message->conversation->contact;
        // Prioriza telefone real (55...) sobre chat_lid (@lid)
        // Telefone real BR: começa com 55 e tem 12-13 dígitos (55 + DDD + número)
        $realPhone = ($contact->phone && preg_match('/^55\d{10,11}$/', $contact->phone)) ? $contact->phone : null;
        $phone     = $realPhone ?? $contact->chat_lid ?? $contact->phone;
        $mediaRef = $this->base64Content ?? $this->message->media_url;

        // Usa Evolution API se configurada e ativa; caso contrário cai para Z-API
        $evolutionConfig = EvolutionApiConfig::current();
        $useEvolution    = $evolutionConfig && $evolutionConfig->is_active;

        if ($useEvolution) {
            $this->sendViaEvolution(new EvolutionApiService($evolutionConfig), $phone, $mediaRef);
        } else {
            $this->sendViaZapi(app(ZapiService::class), $phone, $mediaRef);
        }
    }

    private function sendViaEvolution(EvolutionApiService $api, string $phone, ?string $mediaRef): void
    {
        $text    = $this->prefixAgentName($this->message->content);
        $caption = $this->prefixAgentName($this->message->content ?? '');

        $result = match ($this->message->type) {
            'image'    => $api->sendImage($phone, $mediaRef, $caption),
            'audio'    => $api->sendAudio($phone, $mediaRef),
            'video'    => $api->sendVideo($phone, $mediaRef, $caption),
            'document' => $api->sendDocument($phone, $mediaRef, $this->message->media_filename ?? 'documento'),
            default    => $api->sendText($phone, $text),
        };

        if ($result['success'] ?? false) {
            $msgId = $result['key']['id'] ?? $result['id'] ?? null;
            $this->message->update([
                'delivery_status' => 'sent',
                'zapi_message_id' => $msgId,
            ]);
        } else {
            Log::error('SendWhatsAppMessage (Evolution) failed', [
                'message_id' => $this->message->id,
                'result'     => $result,
            ]);
            $this->message->update(['delivery_status' => 'failed']);
            $this->fail('Evolution API error: ' . ($result['error'] ?? 'unknown'));
        }
    }

    private function sendViaZapi(ZapiService $zapi, string $phone, ?string $mediaRef): void
    {
        $text    = $this->prefixAgentName($this->message->content);
        $caption = $this->prefixAgentName($this->message->content ?? '');

        $result = match ($this->message->type) {
            'image'    => $zapi->sendImageMessage($phone, $mediaRef, $caption),
            'audio'    => $zapi->sendAudioMessage($phone, $mediaRef),
            'video'    => $zapi->sendVideoMessage($phone, $mediaRef, $caption),
            'document' => $zapi->sendDocumentMessage($phone, $mediaRef, $this->message->media_filename ?? 'documento'),
            default    => $zapi->sendTextMessage($phone, $text),
        };

        if ($result['success'] ?? false) {
            $zapiId = $result['messageId'] ?? $result['id'] ?? null;
            $this->message->update([
                'delivery_status' => 'sent',
                'zapi_message_id' => $zapiId,
            ]);
        } else {
            Log::error('SendWhatsAppMessage (Z-API) failed', [
                'message_id' => $this->message->id,
                'result'     => $result,
            ]);
            $this->message->update(['delivery_status' => 'failed']);
            $this->fail('Z-API error: ' . ($result['error'] ?? 'unknown'));
        }
    }

    /**
     * Prefixa o conteúdo com o nome do agente em negrito (formato WhatsApp).
     * Ex: "*Thayna Faria:*\nOlá! Tudo bem?"
     *
     * Só aplica quando a mensagem foi enviada por um agente (sender_type=agent).
     * Mensagens de sistema, bot ou sem conteúdo ficam inalteradas.
     */
    private function prefixAgentName(?string $content): ?string
    {
        if (!$content || $this->message->sender_type !== 'agent') {
            return $content;
        }

        $agent = $this->message->sender;
        if (!$agent) {
            return $content;
        }

        return "*{$agent->name}:*\n{$content}";
    }
}
