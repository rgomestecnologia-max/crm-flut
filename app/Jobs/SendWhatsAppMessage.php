<?php

namespace App\Jobs;

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
        app(\App\Services\CurrentCompany::class)->set((int) $this->message->company_id, persist: false);

        $contact  = $this->message->conversation->contact;
        $realPhone = ($contact->phone && preg_match('/^55\d{10,11}$/', $contact->phone)) ? $contact->phone : null;
        $phone     = $realPhone ?? $contact->chat_lid ?? $contact->phone;
        $mediaRef = $this->base64Content ?? $this->message->media_url;

        $service = WhatsAppProvider::service();
        if (!$service) {
            Log::error('SendWhatsAppMessage: nenhum provider WhatsApp ativo', [
                'message_id' => $this->message->id,
                'company_id' => $this->message->company_id,
            ]);
            $this->message->update(['delivery_status' => 'failed']);
            return;
        }

        if ($service instanceof MetaWhatsAppService) {
            $this->sendViaMeta($service, $phone, $mediaRef);
        } else {
            $this->sendViaEvolution($service, $phone, $mediaRef);
        }
    }

    private function sendViaEvolution(EvolutionApiService $api, string $phone, ?string $mediaRef): void
    {
        $text    = $this->prefixAgentName($this->message->content);
        $caption = $this->prefixAgentName($this->message->content ?? '');
        $contact = $this->message->conversation->contact;

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

            $returnedJid = $result['key']['remoteJid'] ?? null;
            if ($returnedJid && str_contains($returnedJid, '@lid') && $contact && !$contact->chat_lid) {
                $contact->update(['chat_lid' => $returnedJid]);
            }
        } else {
            Log::error('SendWhatsAppMessage (Evolution) failed', [
                'message_id' => $this->message->id,
                'result'     => $result,
            ]);
            $this->message->update(['delivery_status' => 'failed']);
            $this->fail('Evolution API error: ' . ($result['error'] ?? 'unknown'));
        }
    }

    private function sendViaMeta(MetaWhatsAppService $api, string $phone, ?string $mediaRef): void
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
            $msgId = $result['key']['id'] ?? null;
            $this->message->update([
                'delivery_status' => 'sent',
                'zapi_message_id' => $msgId,
            ]);
        } else {
            Log::error('SendWhatsAppMessage (Meta) failed', [
                'message_id' => $this->message->id,
                'result'     => $result,
            ]);
            $this->message->update(['delivery_status' => 'failed']);
            $this->fail('Meta API error: ' . ($result['error'] ?? 'unknown'));
        }
    }

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
