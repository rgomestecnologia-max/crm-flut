<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MetaWhatsAppConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessMessengerMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public MetaWhatsAppConfig $config,
        public array              $event,
        public string             $channel, // 'messenger' ou 'instagram'
    ) {}

    public function handle(): void
    {
        app(\App\Services\CurrentCompany::class)->set($this->config->company_id, persist: false);

        try {
            $senderId  = $this->event['sender']['id'] ?? null;
            $msgData   = $this->event['message'] ?? [];
            $messageId = $msgData['mid'] ?? null;
            $text      = $msgData['text'] ?? null;

            if (!$senderId || !$messageId) return;

            // Deduplicação
            if (Message::where('zapi_message_id', $messageId)->exists()) return;

            // Busca ou cria contato pelo meta_user_id
            $contact = Contact::where('company_id', $this->config->company_id)
                ->where('meta_user_id', $senderId)->first();

            if (!$contact) {
                // Busca nome/foto via Graph API
                $name = null;
                $avatarUrl = null;
                try {
                    $token = $this->channel === 'messenger'
                        ? $this->config->page_access_token
                        : $this->config->access_token;

                    $resp = Http::get("https://graph.facebook.com/v21.0/{$senderId}", [
                        'fields'       => 'name,profile_pic',
                        'access_token' => $token,
                    ]);
                    if ($resp->ok()) {
                        $name = $resp->json('name');
                        $avatarUrl = $resp->json('profile_pic');
                    }
                } catch (\Throwable) {}

                $contact = Contact::create([
                    'company_id'   => $this->config->company_id,
                    'meta_user_id' => $senderId,
                    'name'         => $name ?? 'Visitante ' . substr($senderId, -4),
                    'avatar_url'   => $avatarUrl,
                ]);
            }

            // Busca ou cria conversa
            $conversation = Conversation::where('contact_id', $contact->id)
                ->where('channel', $this->channel)
                ->where('is_group', false)
                ->whereIn('status', ['open', 'pending'])
                ->latest()
                ->first();

            if (!$conversation) {
                $department = \App\Models\Department::active()->first();
                if (!$department) return;

                $conversation = Conversation::create([
                    'contact_id'    => $contact->id,
                    'department_id' => $department->id,
                    'status'        => 'open',
                    'channel'       => $this->channel,
                    'is_group'      => false,
                ]);
            }

            // Detecta tipo e mídia
            $type = 'text';
            $mediaUrl = null;
            $mediaFilename = null;

            if (!empty($msgData['attachments'])) {
                $att = $msgData['attachments'][0];
                $attType = $att['type'] ?? 'file';
                $mediaUrl = $att['payload']['url'] ?? null;

                $type = match($attType) {
                    'image' => 'image',
                    'video' => 'video',
                    'audio' => 'audio',
                    default => 'document',
                };

                if (!$text && $type !== 'text') {
                    $text = match($type) {
                        'image' => '📷 Foto',
                        'video' => '🎬 Vídeo',
                        'audio' => '🎵 Áudio',
                        default => '📎 Arquivo',
                    };
                }
            }

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type'     => 'contact',
                'content'         => $text,
                'type'            => $type,
                'media_url'       => $mediaUrl,
                'media_filename'  => $mediaFilename,
                'zapi_message_id' => $messageId,
                'delivery_status' => 'delivered',
            ]);

            $conversation->update(['last_message_at' => now(), 'status' => 'open']);

            try { broadcast(new \App\Events\MessageReceived($message)); } catch (\Throwable) {}

            Log::info("ProcessMessengerMessage: {$this->channel}", [
                'contact' => $contact->name,
                'text'    => substr($text ?? '', 0, 50),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessMessengerMessage falhou', ['error' => $e->getMessage()]);
        }
    }
}
