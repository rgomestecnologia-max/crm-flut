<?php

namespace App\Jobs;

use App\Events\MessageReceived;
use App\Models\AiBotConfig;
use App\Models\ChatbotMenuConfig;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\Message;
use App\Models\MetaWhatsAppConfig;
use App\Services\CurrentCompany;
use App\Services\MediaStorage;
use App\Services\MetaWhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMetaMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int     $companyId,
        public array   $messageData,
        public ?array  $contactInfo,
        public string  $phoneNumberId,
    ) {}

    public function handle(): void
    {
        app(CurrentCompany::class)->set($this->companyId, persist: false);

        try {
            $msg   = $this->messageData;
            $from  = $msg['from'] ?? null;  // E.164: 5511999999999
            $msgId = $msg['id'] ?? null;    // wamid.xxx
            $type  = $msg['type'] ?? 'text';

            if (!$from) {
                return;
            }

            // Nome do contato vem do webhook
            $contactName = $this->contactInfo['profile']['name'] ?? null;
            $phone = preg_replace('/\D/', '', $from);

            // ── Deduplicação ─────────────────────────────────────────────
            if ($msgId && Message::where('zapi_message_id', $msgId)->exists()) {
                return;
            }

            // ── Reações recebidas ────────────────────────────────────────
            if ($type === 'reaction') {
                $this->processReaction($msg, $phone);
                return;
            }

            // ── Contato ──────────────────────────────────────────────────
            $contact = Contact::where('phone', $phone)->first();

            if (!$contact) {
                $contact = Contact::create([
                    'phone' => $phone,
                    'name'  => $contactName,
                ]);
            } elseif ($contactName && !$contact->name) {
                $contact->update(['name' => $contactName]);
            }

            // ── Conversa ─────────────────────────────────────────────────
            $conversation = Conversation::where('contact_id', $contact->id)
                ->where('is_group', false)
                ->whereIn('status', ['open', 'pending', 'resolved'])
                ->latest()
                ->first();

            if (!$conversation) {
                $department = Department::active()->first();
                if (!$department) {
                    Log::error('ProcessMetaMessage: no active department found');
                    return;
                }

                $conversation = Conversation::create([
                    'contact_id'    => $contact->id,
                    'department_id' => $department->id,
                    'status'        => 'open',
                    'is_group'      => false,
                ]);
            } elseif ($conversation->status === 'resolved') {
                $conversation->update([
                    'status'        => 'open',
                    'assigned_to'   => null,
                    'menu_awaiting' => false,
                ]);
            }

            // ── Conteúdo da mensagem ─────────────────────────────────────
            [$content, $msgType, $mediaUrl, $mediaFilename] = $this->extractContent($msg);

            if (!$content && !$mediaUrl) {
                Log::info('ProcessMetaMessage: mensagem sem conteúdo suportado', ['type' => $type]);
                return;
            }

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type'     => 'contact',
                'sender_id'       => null,
                'content'         => $content,
                'type'            => $msgType,
                'media_url'       => $mediaUrl,
                'media_filename'  => $mediaFilename,
                'zapi_message_id' => $msgId,
                'delivery_status' => 'delivered',
            ]);

            $conversation->update(['last_message_at' => now(), 'status' => 'open']);

            // ── move_on_reply (automação) ─────────────────────────────────
            if ($conversation->source_automation_id) {
                try {
                    $sourceAuto = $conversation->sourceAutomation;
                    if ($sourceAuto && $sourceAuto->move_on_reply_from_stage_id && $sourceAuto->move_on_reply_to_stage_id) {
                        $card = \App\Models\CrmCard::where('contact_id', $contact->id)
                            ->where('stage_id', $sourceAuto->move_on_reply_from_stage_id)
                            ->first();
                        if ($card) {
                            $fromStage = $card->stage?->name ?? '—';
                            $toStage = \App\Models\CrmStage::find($sourceAuto->move_on_reply_to_stage_id);
                            $card->update(['stage_id' => $sourceAuto->move_on_reply_to_stage_id]);
                            \App\Models\CrmCardActivity::create([
                                'card_id' => $card->id,
                                'user_id' => null,
                                'type'    => 'stage_change',
                                'content' => "Cliente respondeu: {$fromStage} → " . ($toStage->name ?? '?'),
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('ProcessMetaMessage move_on_reply falhou', ['error' => $e->getMessage()]);
                }
            }

            // ── Bot / Chatbot ────────────────────────────────────────────
            $conversation->refresh();
            $humanSent = $conversation->assigned_to
                ? Message::where('conversation_id', $conversation->id)
                    ->where('sender_type', 'agent')
                    ->whereNotNull('sender_id')
                    ->exists()
                : false;

            if (!$conversation->waiting_human_reason && !$humanSent) {
                try {
                    $menuConfig = ChatbotMenuConfig::current();
                    $botConfig  = AiBotConfig::current();

                    $automationAi = false;
                    $aiOnlyForAutomation = false;

                    if ($conversation->source_automation_id) {
                        $sourceAutomation = $conversation->sourceAutomation;
                        $automationAi = $sourceAutomation?->enable_ai_on_reply === true;
                    }

                    if (!$conversation->source_automation_id && $botConfig && $botConfig->is_active) {
                        $hasAiAutomation = \App\Models\Automation::where('is_active', true)
                            ->where('enable_ai_on_reply', true)
                            ->exists();
                        if ($hasAiAutomation) {
                            $aiOnlyForAutomation = true;
                        }
                    }

                    $skipMenu = $automationAi && $botConfig && $botConfig->is_active && $botConfig->hasKey();

                    if ($aiOnlyForAutomation) {
                        $conversation->update(['waiting_human_reason' => 'Atendimento direto - aguardando humano']);
                        Message::create([
                            'conversation_id' => $conversation->id,
                            'sender_type'     => 'system',
                            'content'         => '🔔 Cliente entrou em contato direto (fora da automação) — aguardando atendente',
                            'type'            => 'text',
                            'delivery_status' => 'sent',
                        ]);
                    } elseif ($menuConfig && $menuConfig->is_active && !$skipMenu) {
                        ProcessMenuBot::dispatch($conversation, $menuConfig, $botConfig, $message->id);
                    } elseif ($botConfig && $botConfig->is_active && $botConfig->hasKey()) {
                        ProcessBotResponse::dispatch($conversation, $botConfig, $message->id);
                    }
                } catch (\Throwable $e) {
                    Log::warning('ProcessMetaMessage: bot dispatch falhou', ['error' => $e->getMessage()]);
                }
            }

            // ── Broadcast para atualizar UI ──────────────────────────────
            try {
                broadcast(new MessageReceived($message))->toOthers();
            } catch (\Throwable $e) {
                Log::warning('ProcessMetaMessage: broadcast falhou', ['error' => $e->getMessage()]);
            }

        } catch (\Throwable $e) {
            Log::error('ProcessMetaMessage falhou', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }

    private function processReaction(array $msg, string $phone): void
    {
        try {
            $reaction  = $msg['reaction'] ?? [];
            $targetId  = $reaction['message_id'] ?? null;
            $emoji     = $reaction['emoji'] ?? null;

            if (!$targetId) return;

            $message = Message::where('zapi_message_id', $targetId)->first();
            if (!$message) return;

            $reactions = $message->reactions ?? [];
            $reactions = array_values(array_filter($reactions, fn($r) => $r['phone'] !== $phone));

            if ($emoji) {
                $reactions[] = ['emoji' => $emoji, 'phone' => $phone, 'at' => now()->toISOString()];
            }

            $message->update(['reactions' => $reactions]);
            broadcast(new MessageReceived($message));
        } catch (\Throwable $e) {
            Log::error('ProcessMetaMessage reaction falhou', ['error' => $e->getMessage()]);
        }
    }

    private function extractContent(array $msg): array
    {
        $type = $msg['type'] ?? 'text';

        // Texto
        if ($type === 'text') {
            return [$msg['text']['body'] ?? null, 'text', null, null];
        }

        // Imagem
        if ($type === 'image') {
            $mediaUrl = $this->downloadAndStore($msg['image']['id'] ?? null, $msg['image']['mime_type'] ?? 'image/jpeg', 'image');
            $caption  = $msg['image']['caption'] ?? null;
            return [$caption, 'image', $mediaUrl, null];
        }

        // Áudio
        if ($type === 'audio') {
            $mediaUrl = $this->downloadAndStore($msg['audio']['id'] ?? null, $msg['audio']['mime_type'] ?? 'audio/ogg', 'audio');
            return [null, 'audio', $mediaUrl, null];
        }

        // Vídeo
        if ($type === 'video') {
            $mediaUrl = $this->downloadAndStore($msg['video']['id'] ?? null, $msg['video']['mime_type'] ?? 'video/mp4', 'video');
            $caption  = $msg['video']['caption'] ?? null;
            return [$caption, 'video', $mediaUrl, null];
        }

        // Documento
        if ($type === 'document') {
            $doc      = $msg['document'] ?? [];
            $mediaUrl = $this->downloadAndStore($doc['id'] ?? null, $doc['mime_type'] ?? 'application/pdf', 'document');
            $filename = $doc['filename'] ?? 'documento';
            $caption  = $doc['caption'] ?? null;
            return [$caption, 'document', $mediaUrl, $filename];
        }

        // Sticker
        if ($type === 'sticker') {
            $mediaUrl = $this->downloadAndStore($msg['sticker']['id'] ?? null, 'image/webp', 'sticker');
            return [null, 'image', $mediaUrl, null];
        }

        // Location
        if ($type === 'location') {
            $loc = $msg['location'] ?? [];
            $text = "📍 Localização: {$loc['latitude']}, {$loc['longitude']}";
            return [$text, 'text', null, null];
        }

        // Contacts
        if ($type === 'contacts') {
            $contact = $msg['contacts'][0] ?? [];
            $name    = $contact['name']['formatted_name'] ?? 'Contato';
            $phone   = $contact['phones'][0]['phone'] ?? '';
            return ["📇 Contato: {$name} - {$phone}", 'text', null, null];
        }

        return [null, 'text', null, null];
    }

    /**
     * Baixa mídia da Meta API e salva no storage.
     */
    private function downloadAndStore(?string $mediaId, string $mime, string $type): ?string
    {
        if (!$mediaId) return null;

        try {
            $config  = MetaWhatsAppConfig::current();
            $service = new MetaWhatsAppService($config);
            $media   = $service->downloadMedia($mediaId);

            if (!$media || empty($media['binary'])) return null;

            $ext  = $this->mimeToExt($mime);
            $path = "media/{$type}s/" . uniqid() . ".{$ext}";

            MediaStorage::put($path, $media['binary']);
            return MediaStorage::url($path);
        } catch (\Throwable $e) {
            Log::error('ProcessMetaMessage: download media falhou', [
                'media_id' => $mediaId,
                'error'    => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function mimeToExt(string $mime): string
    {
        return match (true) {
            str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') => 'jpg',
            str_contains($mime, 'png')  => 'png',
            str_contains($mime, 'gif')  => 'gif',
            str_contains($mime, 'webp') => 'webp',
            str_contains($mime, 'ogg')  => 'ogg',
            str_contains($mime, 'mp3') || str_contains($mime, 'mpeg') => 'mp3',
            str_contains($mime, 'mp4')  => 'mp4',
            str_contains($mime, 'pdf')  => 'pdf',
            default                     => 'bin',
        };
    }
}
