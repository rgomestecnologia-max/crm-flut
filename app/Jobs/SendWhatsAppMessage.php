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
        public ?array  $mentionedJid = null,
    ) {}

    public function handle(): void
    {
        app(\App\Services\CurrentCompany::class)->set((int) $this->message->company_id, persist: false);

        $conversation = $this->message->conversation;
        $contact      = $conversation->contact;
        $channel      = $conversation->channel ?? 'whatsapp';

        // Messenger ou Instagram — envia via MetaMessengerService
        if (in_array($channel, ['messenger', 'instagram'])) {
            $this->sendViaMessenger($contact, $channel);
            return;
        }

        // WhatsApp — sempre preferir número real sobre LID
        $realPhone = ($contact->phone && preg_match('/^55\d{10,11}$/', $contact->phone)) ? $contact->phone : null;

        // Se só tem LID, tenta resolver número real via Evolution API
        if (!$realPhone && $contact->chat_lid && str_contains($contact->chat_lid, '@lid')) {
            try {
                $evoConfig = $conversation->evolution_api_config_id
                    ? \App\Models\EvolutionApiConfig::find($conversation->evolution_api_config_id)
                    : \App\Models\EvolutionApiConfig::current();
                if ($evoConfig) {
                    $api = new \App\Services\EvolutionApiService($evoConfig);
                    $result = $api->get("/chat/findContacts/{$evoConfig->instance_name}?where.id={$contact->chat_lid}");
                    $resolved = $result[0]['id'] ?? null;
                    if ($resolved && str_contains($resolved, '@s.whatsapp.net')) {
                        $realPhone = preg_replace('/\D/', '', str_replace('@s.whatsapp.net', '', $resolved));
                        $contact->update(['phone' => $realPhone]);
                        Log::info('SendWhatsAppMessage: LID resolvido para número real', [
                            'lid' => $contact->chat_lid, 'phone' => $realPhone,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('SendWhatsAppMessage: falha ao resolver LID', ['error' => $e->getMessage()]);
            }
        }

        $phone = $realPhone ?? $contact->chat_lid ?? $contact->phone;
        $mediaRef = $this->base64Content ?? $this->message->media_url;

        // Se provider da empresa é Meta e config ativa, usa Meta (prioridade sobre Evolution)
        $provider = WhatsAppProvider::currentProvider();
        if ($provider === 'meta') {
            $metaConfig = \App\Models\MetaWhatsAppConfig::current();
            if ($metaConfig && $metaConfig->is_active) {
                $service = new MetaWhatsAppService($metaConfig);
                $this->sendViaMeta($service, $phone, $mediaRef);
                return;
            }
        }

        // Evolution API (single ou multi-instância)
        $specificConfig = null;
        if ($conversation->evolution_api_config_id) {
            $specificConfig = \App\Models\EvolutionApiConfig::find($conversation->evolution_api_config_id);
        } elseif ($conversation->department?->evolution_api_config_id) {
            $specificConfig = \App\Models\EvolutionApiConfig::find($conversation->department->evolution_api_config_id);
        }

        $service = WhatsAppProvider::service($specificConfig);
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

    private function sendViaMessenger($contact, string $channel): void
    {
        $config = \App\Models\MetaWhatsAppConfig::where('company_id', $this->message->company_id)->first();
        if (!$config || !$contact->meta_user_id) {
            $this->message->update(['delivery_status' => 'failed']);
            return;
        }

        $pageId = $config->page_id;
        $token  = $config->page_access_token;
        if (!$pageId || !$token) {
            $this->message->update(['delivery_status' => 'failed']);
            return;
        }

        $svc = new \App\Services\MetaMessengerService($pageId, $token);
        $text = $this->message->content;
        $mediaUrl = $this->message->media_url;

        $result = match ($this->message->type) {
            'image'    => $svc->sendImage($contact->meta_user_id, $mediaUrl),
            'video'    => $svc->sendVideo($contact->meta_user_id, $mediaUrl),
            'audio'    => $svc->sendAudio($contact->meta_user_id, $mediaUrl),
            'document' => $svc->sendDocument($contact->meta_user_id, $mediaUrl, $this->message->media_filename ?? 'file'),
            default    => $svc->sendText($contact->meta_user_id, $text),
        };

        $msgId = $result['message_id'] ?? null;
        $this->message->update([
            'delivery_status' => ($result['success'] ?? false) ? 'sent' : 'failed',
            'zapi_message_id' => $msgId,
        ]);
    }

    private function sendViaEvolution(EvolutionApiService $api, string $phone, ?string $mediaRef): void
    {
        $text    = $this->prefixAgentName($this->message->content);
        $caption = $this->prefixAgentName($this->message->content ?? '');
        $contact = $this->message->conversation->contact;

        // Converte URL para base64 para imagens e documentos (melhora entrega no celular)
        if ($mediaRef && str_starts_with($mediaRef, 'http') && in_array($this->message->type, ['image', 'document'])) {
            try {
                $resp = \Illuminate\Support\Facades\Http::timeout(10)->get($mediaRef);
                if ($resp->successful()) {
                    $mime = $resp->header('Content-Type') ?: 'application/octet-stream';
                    $mediaRef = 'data:' . $mime . ';base64,' . base64_encode($resp->body());
                }
            } catch (\Throwable $e) {
                Log::warning('SendWhatsApp: falha ao converter URL para base64, enviando URL direto', [
                    'url' => substr($mediaRef, 0, 80), 'error' => $e->getMessage(),
                ]);
            }
        }

        // Quoted message (resposta referenciando outra mensagem)
        $quotedId = null;
        if ($this->message->reply_to_id) {
            $replyMsg = Message::find($this->message->reply_to_id);
            if ($replyMsg && $replyMsg->zapi_message_id) {
                $quotedId = $replyMsg->zapi_message_id;
            }
        }

        $result = match ($this->message->type) {
            'image'    => $api->sendImage($phone, $mediaRef, $caption),
            'audio'    => $api->sendAudio($phone, $mediaRef),
            'video'    => $api->sendVideo($phone, $mediaRef, $caption),
            'document' => $api->sendDocument($phone, $mediaRef, $this->message->media_filename ?? 'documento'),
            'contact'  => $api->sendContact($phone, $this->message->media_filename ?? 'Contato', $this->message->media_url ?? ''),
            default    => $api->sendText($phone, $text, $quotedId, $this->mentionedJid),
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
