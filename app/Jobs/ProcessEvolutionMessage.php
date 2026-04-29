<?php

namespace App\Jobs;

use App\Events\MessageReceived;
use App\Models\AiBotConfig;
use App\Models\ChatbotMenuConfig;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\EvolutionApiConfig;
use App\Models\Message;
use App\Services\CurrentCompany;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\MediaStorage;
use Illuminate\Support\Facades\Log;

class ProcessEvolutionMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public array $payload) {}

    public function handle(): void
    {
        // Resolve a empresa pelo nome da instância no payload do webhook.
        // Cada empresa tem sua própria EvolutionApiConfig com instance_name único.
        $instanceName = $this->payload['instance'] ?? null;
        $companyId    = $this->resolveCompanyId($instanceName);

        if (!$companyId) {
            Log::warning('ProcessEvolutionMessage: instância sem empresa correspondente — descartando', [
                'instance' => $instanceName,
                'event'    => $this->payload['event'] ?? null,
            ]);
            return;
        }

        app(CurrentCompany::class)->set($companyId, persist: false);

        try {
            $event = $this->payload['event'] ?? '';
            $data  = $this->payload['data'] ?? [];

            // Ignora eventos que não são de mensagem recebida
            if ($event !== 'messages.upsert') {
                return;
            }

            $key         = $data['key'] ?? [];
            $fromMe      = (bool) ($key['fromMe'] ?? false);
            $remoteJid   = $key['remoteJid'] ?? '';
            $messageId   = $key['id'] ?? null;
            $messageType = $data['messageType'] ?? 'conversation';

            // Ignora status do WhatsApp
            if (str_contains($remoteJid, '@broadcast') || str_contains($remoteJid, 'status@broadcast')) {
                return;
            }

            // ── Reações ─────────────────────────────────────────────────────
            if ($messageType === 'reactionMessage' || isset($data['message']['reactionMessage'])) {
                $this->processReaction($data, $remoteJid);
                return;
            }

            // ── Grupo ou individual ──────────────────────────────────────────
            $isGroup = str_ends_with($remoteJid, '@g.us');

            // Telefone do chat (grupo ou contato)
            $chatPhone = preg_replace('/\D/', '', preg_replace('/@.+/', '', $remoteJid));

            // Remetente real
            $participantJid = $data['participant'] ?? $key['participant'] ?? null;
            $senderPhone    = $participantJid
                ? preg_replace('/\D/', '', preg_replace('/@.+/', '', $participantJid))
                : $chatPhone;

            $senderName = $data['pushName'] ?? null;

            // Foto do perfil
            $senderPhoto = $data['message']['imageMessage']['url'] ?? null; // não vem na mensagem
            // (fotos de perfil chegam via CONTACTS_UPSERT separadamente)

            // Ignora se não tem telefone válido
            if (!$chatPhone || !$senderPhone) {
                Log::info('ProcessEvolutionMessage: sem telefone', ['remoteJid' => $remoteJid]);
                return;
            }

            // ── Contato ──────────────────────────────────────────────────────
            if ($isGroup) {
                // Para grupos, contato é o GRUPO (não o remetente individual)
                $groupName = $data['chatName'] ?? null;
                $groupPhone = substr($chatPhone, 0, 20); // trunca pra caber no campo

                $contact = Contact::where('chat_lid', $remoteJid)->first()
                    ?? Contact::where('phone', $groupPhone)->first();

                if (!$contact) {
                    $contact = Contact::create([
                        'phone'    => $groupPhone,
                        'name'     => $groupName,
                        'chat_lid' => $remoteJid,
                    ]);
                } elseif ($groupName && !$contact->name) {
                    $contact->update(['name' => $groupName]);
                }
                if ($remoteJid && !$contact->chat_lid) {
                    $contact->update(['chat_lid' => $remoteJid]);
                }
            } else {
                if ($fromMe) {
                    // Busca por JID (chat_lid) ou phone
                    $contact = Contact::where('chat_lid', $remoteJid)->first()
                        ?? Contact::where('phone', $chatPhone)->first();

                    // fromMe com LID: tenta vincular ao contato que tem phone real
                    if (!$contact && str_contains($remoteJid, '@lid')) {
                        // Busca por zapi_message_id da mensagem enviada pelo CRM
                        $sentMsg = Message::where('zapi_message_id', $key['id'] ?? '')->first();
                        if ($sentMsg) {
                            $conv = Conversation::find($sentMsg->conversation_id);
                            $contact = $conv ? Contact::find($conv->contact_id) : null;
                            if ($contact && !$contact->chat_lid) {
                                $contact->update(['chat_lid' => $remoteJid]);
                            }
                        }
                    }

                    if (!$contact) return;
                } else {
                    $contact = Contact::where('chat_lid', $remoteJid)->first()
                        ?? Contact::where('phone', $chatPhone)->first();

                    // Se não encontrou e o chatPhone é real (55...), tenta achar
                    // contato que tenha LID como phone mas mesmo pushName
                    // (resolve duplicação LID ↔ número real)
                    if (!$contact && $senderName && str_starts_with($chatPhone, '55')) {
                        $contact = Contact::where('name', $senderName)
                            ->whereRaw("LENGTH(phone) > 14 AND phone NOT LIKE '55%'")
                            ->first();
                        if ($contact) {
                            // Encontrou contato com LID — atualiza com telefone real
                            $contact->update([
                                'phone'    => $chatPhone,
                                'chat_lid' => $contact->chat_lid ?: $contact->phone . '@lid',
                            ]);
                            Log::info('Contact LID→real unificado', [
                                'contact' => $contact->id,
                                'name'    => $contact->name,
                                'old_phone' => $contact->getOriginal('phone'),
                                'new_phone' => $chatPhone,
                            ]);
                        }
                    }

                    if (!$contact) {
                        $contact = Contact::create([
                            'phone' => $chatPhone,
                            'name'  => $senderName,
                        ]);
                    }

                    if ($senderName && !$contact->name) {
                        $contact->update(['name' => $senderName]);
                    }
                    // Salva o JID completo para poder responder corretamente
                    if (!$contact->chat_lid) {
                        $contact->update(['chat_lid' => $remoteJid]);
                    }
                    // Se o contato tinha LID como phone e agora temos o real, atualiza
                    if ($contact->phone && !str_starts_with($contact->phone, '55')
                        && strlen($contact->phone) > 14 && str_starts_with($chatPhone, '55')) {
                        $oldLid = $contact->phone;
                        $contact->update([
                            'phone'    => $chatPhone,
                            'chat_lid' => $contact->chat_lid ?: $oldLid . '@lid',
                        ]);
                    }
                }
            }

            // ── Conversa ─────────────────────────────────────────────────────
            if ($isGroup) {
                // $groupName já definido na seção de contato acima
                // Grupo sempre reutiliza a mesma conversa (independente do status)
                $conversation = Conversation::where('contact_id', $contact->id)
                    ->where('is_group', true)
                    ->latest()
                    ->first();

                if (!$conversation) {
                    $department = Department::active()->first();
                    if (!$department) return;

                    $conversation = Conversation::create([
                        'contact_id'    => $contact->id,
                        'department_id' => $department->id,
                        'status'        => 'open',
                        'is_group'      => true,
                        'group_name'    => $groupName,
                    ]);
                } else {
                    // Reabre se estava resolvido/transferido
                    if (in_array($conversation->status, ['resolved', 'transferred'])) {
                        $conversation->update(['status' => 'open']);
                    }
                    if ($groupName && !$conversation->group_name) {
                        $conversation->update(['group_name' => $groupName]);
                    }
                }
            } else {
                $conversation = Conversation::where('contact_id', $contact->id)
                    ->where('is_group', false)
                    ->whereIn('status', ['open', 'pending', 'resolved'])
                    ->latest()
                    ->first();

                if (!$conversation) {
                    if ($fromMe) return;

                    $department = Department::active()->first();
                    if (!$department) {
                        Log::error('ProcessEvolutionMessage: no active department found');
                        return;
                    }

                    $conversation = Conversation::create([
                        'contact_id'    => $contact->id,
                        'department_id' => $department->id,
                        'status'        => 'open',
                        'is_group'      => false,
                    ]);
                } elseif (!$fromMe && $conversation->status === 'resolved') {
                    // Reabre a conversa: volta pra fila, reseta chatbot pra novo atendimento
                    $conversation->update([
                        'status'        => 'open',
                        'assigned_to'   => null,
                        'menu_awaiting' => false,
                    ]);
                }
            }

            // ── Deduplicação ─────────────────────────────────────────────────
            if ($messageId && Message::where('zapi_message_id', $messageId)->exists()) {
                return;
            }

            // ── Conteúdo da mensagem ──────────────────────────────────────────
            [$content, $type, $mediaUrl, $mediaFilename] = $this->extractContent($data);

            if (!$content && !$mediaUrl) {
                Log::info('ProcessEvolutionMessage: mensagem sem conteúdo suportado', [
                    'messageType' => $messageType,
                    'remoteJid'   => $remoteJid,
                ]);
                return;
            }

            $senderType     = $fromMe ? 'agent' : 'contact';
            $deliveryStatus = $fromMe ? 'sent'  : 'delivered';

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type'     => $senderType,
                'sender_id'       => null,
                'sender_name'     => ($isGroup && !$fromMe) ? $senderName : null,
                'sender_phone'    => ($isGroup && !$fromMe) ? $senderPhone : null,
                'content'         => $content,
                'type'            => $type,
                'media_url'       => $mediaUrl,
                'media_filename'  => $mediaFilename,
                'zapi_message_id' => $messageId,
                'delivery_status' => $deliveryStatus,
            ]);

            // Calcula duração do áudio via ffprobe
            if ($type === 'audio' && $mediaUrl) {
                try {
                    $path = ltrim(str_replace('/storage/', '', parse_url($mediaUrl, PHP_URL_PATH) ?? ''), '/');
                    $dur  = \App\Services\AudioProbe::durationFromStorage($path);
                    if ($dur) $message->update(['media_duration' => round($dur, 1)]);
                } catch (\Throwable) {}
            }

            $conversation->update(['last_message_at' => now(), 'status' => 'open']);

            // ── Mover card no CRM quando cliente responde (automação move_on_reply) ──
            if (!$fromMe && !$isGroup && $conversation->source_automation_id) {
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
                            Log::info('move_on_reply: card movido', [
                                'card' => $card->id, 'from' => $fromStage, 'to' => $toStage->name ?? '?',
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('move_on_reply falhou', ['error' => $e->getMessage()]);
                }
            }

            // ── Bot de atendimento ────────────────────────────────────────────
            // Recarrega conversa do banco para pegar waiting_human_reason atualizado
            $conversation->refresh();
            // Não dispara IA se conversa está aguardando atendente humano ou já tem agente
            if (!$fromMe && !$conversation->waiting_human_reason && !$conversation->assigned_to) {
                try {
                    $menuConfig = ChatbotMenuConfig::current();
                    $botConfig  = AiBotConfig::current();

                    $automationAi = false;
                    if (!$isGroup && $conversation->source_automation_id) {
                        $sourceAutomation = $conversation->sourceAutomation;
                        $automationAi = $sourceAutomation?->enable_ai_on_reply === true;
                    }

                    $skipMenu = $automationAi && $botConfig && $botConfig->is_active && $botConfig->hasKey();

                    // Não envia chatbot em grupos se reply_in_groups está desativado
                    $skipGroups = $isGroup && $menuConfig && !$menuConfig->reply_in_groups;

                    if ($menuConfig && $menuConfig->is_active && !$skipMenu && !$skipGroups) {
                        \App\Jobs\ProcessMenuBot::dispatch($conversation, $menuConfig, $botConfig, $message->id);
                    } elseif ($botConfig && $botConfig->is_active && $botConfig->hasKey()) {
                        \App\Jobs\ProcessBotResponse::dispatch($conversation, $botConfig, $message->id);
                    }
                } catch (\Throwable $e) {
                    Log::warning('ProcessEvolutionMessage: bot dispatch falhou', ['error' => $e->getMessage()]);
                }
            }

            // ── Broadcast ────────────────────────────────────────────────────
            try {
                broadcast(new MessageReceived($message))->toOthers();
            } catch (\Throwable $e) {
                Log::warning('ProcessEvolutionMessage: broadcast falhou', ['error' => $e->getMessage()]);
            }

        } catch (\Throwable $e) {
            Log::error('ProcessEvolutionMessage falhou', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'payload' => $this->payload,
            ]);
        }
    }

    private function processReaction(array $data, string $remoteJid): void
    {
        try {
            $reaction    = $data['message']['reactionMessage'] ?? [];
            $targetId    = $reaction['key']['id'] ?? null;
            $emoji       = $reaction['text'] ?? null;
            $reactorPhone = preg_replace('/\D/', '', preg_replace('/@.+/', '', $remoteJid));

            if (!$targetId || !$reactorPhone) {
                return;
            }

            $message = Message::where('zapi_message_id', $targetId)->first();
            if (!$message) {
                Log::info('ProcessEvolutionMessage reaction: mensagem não encontrada', ['targetId' => $targetId]);
                return;
            }

            $reactions = $message->reactions ?? [];
            $reactions = array_values(array_filter($reactions, fn($r) => $r['phone'] !== $reactorPhone));

            if ($emoji) {
                $reactions[] = ['emoji' => $emoji, 'phone' => $reactorPhone, 'at' => now()->toISOString()];
            }

            $message->update(['reactions' => $reactions]);

            broadcast(new MessageReceived($message));

            Log::info('ProcessEvolutionMessage reaction processada', [
                'message_id' => $message->id,
                'emoji'      => $emoji,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessEvolutionMessage processReaction falhou', ['error' => $e->getMessage()]);
        }
    }

    private function extractContent(array $data): array
    {
        $msg       = $data['message'] ?? [];
        $type      = $data['messageType'] ?? 'conversation';
        $messageId = $data['key']['id'] ?? null;

        // Texto puro
        if (!empty($msg['conversation'])) {
            return [$msg['conversation'], 'text', null, null];
        }

        // Texto extendido (links, bold, etc.)
        if (!empty($msg['extendedTextMessage']['text'])) {
            return [$msg['extendedTextMessage']['text'], 'text', null, null];
        }

        // Imagem
        if (!empty($msg['imageMessage'])) {
            $im   = $msg['imageMessage'];
            $mime = $im['mimetype'] ?? 'image/jpeg';
            $url  = $this->resolveMediaUrl($im, $messageId, $mime, $im['jpegThumbnail'] ?? null);
            return [$im['caption'] ?? null, 'image', $url, null];
        }

        // Áudio / PTT
        if (!empty($msg['audioMessage'])) {
            $am  = $msg['audioMessage'];
            $url = $this->resolveMediaUrl($am, $messageId, $am['mimetype'] ?? 'audio/ogg', null, 'audio');
            return [null, 'audio', $url, null];
        }

        // Vídeo
        if (!empty($msg['videoMessage'])) {
            $vm   = $msg['videoMessage'];
            $mime = $vm['mimetype'] ?? 'video/mp4';
            $url  = $this->resolveMediaUrl($vm, $messageId, $mime, null, 'video');

            // Salva thumbnail do vídeo (jpegThumbnail do WhatsApp) como _thumb.webp
            if ($url && !empty($vm['jpegThumbnail'])) {
                $this->saveVideoThumbnail($url, $vm['jpegThumbnail']);
            }

            return [$vm['caption'] ?? null, 'video', $url, null];
        }

        // Documento
        if (!empty($msg['documentMessage'])) {
            $dm  = $msg['documentMessage'];
            $url = $this->resolveMediaUrl($dm, $messageId, $dm['mimetype'] ?? 'application/octet-stream');
            return [$dm['caption'] ?? null, 'document', $url, $dm['fileName'] ?? 'documento'];
        }

        // Documento com legenda
        if (!empty($msg['documentWithCaptionMessage'])) {
            $dm  = $msg['documentWithCaptionMessage']['message']['documentMessage'] ?? [];
            $url = $this->resolveMediaUrl($dm, $messageId, $dm['mimetype'] ?? 'application/octet-stream');
            return [$dm['caption'] ?? null, 'document', $url, $dm['fileName'] ?? 'documento'];
        }

        // Sticker
        if (!empty($msg['stickerMessage'])) {
            $sm  = $msg['stickerMessage'];
            $url = $this->resolveMediaUrl($sm, $messageId, $sm['mimetype'] ?? 'image/webp', null, 'sticker');
            return [null, 'sticker', $url, null];
        }

        return [null, 'text', null, null];
    }

    /**
     * Resolve URL pública para uma mídia recebida via webhook.
     *
     * Ordem de tentativas:
     *   1. base64 já presente no payload (webhookBase64=true)
     *   2. download decifrado via /chat/getBase64FromMediaMessage
     *   3. jpegThumbnail (último recurso, só serve como preview minúsculo)
     *
     * Nunca devolve a URL .enc do mmg.whatsapp.net porque o browser não
     * consegue descriptografar — ela aparecia como ícone quebrado no chat.
     */
    private function resolveMediaUrl(array $node, ?string $messageId, string $mime, ?string $thumbnail = null, string $type = 'image'): ?string
    {
        // 1) Base64 já no payload
        if (!empty($node['base64'])) {
            $saved = $this->saveMedia($node['base64'], $mime, $type);
            if ($saved) return $saved;
        }

        // 2) Download decifrado via Evolution
        if ($messageId) {
            try {
                $fetched = app(\App\Services\EvolutionApiService::class)
                    ->getBase64FromMediaMessage($messageId);

                if ($fetched) {
                    $saved = $this->saveMedia($fetched['base64'], $fetched['mimetype'] ?? $mime, $type);
                    if ($saved) return $saved;
                }
            } catch (\Throwable $e) {
                Log::warning('ProcessEvolutionMessage: getBase64FromMediaMessage falhou', [
                    'message_id' => $messageId,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        // 3) Thumbnail como último recurso (apenas preview minúsculo)
        if ($thumbnail) {
            $saved = $this->saveMedia($thumbnail, $mime, $type);
            if ($saved) return $saved;
        }

        return null;
    }

    /**
     * Salva base64 de mídia em storage/public/media e retorna URL pública.
     * Retorna null se base64 não informado ou inválido.
     */
    private function saveMedia(?string $base64, string $mime, string $type = 'image'): ?string
    {
        if (!$base64) return null;

        // Remove prefixo data URL se presente (ex: "data:image/jpeg;base64,")
        if (str_contains($base64, ',')) {
            [, $base64] = explode(',', $base64, 2);
        }

        $decoded = base64_decode(trim($base64), strict: false);
        if (!$decoded || strlen($decoded) < 10) return null;

        $ext = match (true) {
            str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') => 'jpg',
            str_contains($mime, 'png')   => 'png',
            str_contains($mime, 'gif')   => 'gif',
            str_contains($mime, 'webp')  => 'webp',
            str_contains($mime, 'ogg')   => 'ogg',
            str_contains($mime, 'mpeg')  => 'mp3',
            str_contains($mime, 'mp4')   => 'mp4',
            str_contains($mime, 'webm')  => 'webm',
            str_contains($mime, 'pdf')   => 'pdf',
            default                      => 'bin',
        };

        $baseName = uniqid('msg_', true);
        $dir      = 'media/' . date('Y/m');

        // Tenta otimizar imagens (comprime + gera thumbnail)
        $optimizer = app(\App\Services\ImageOptimizer::class);
        $result    = $optimizer->tryOptimize($decoded, $mime, $type);

        if ($result) {
            // Salva versão otimizada como WebP
            $path = "{$dir}/{$baseName}.webp";
            MediaStorage::put($path, $result['optimized']);

            // Salva thumbnail ao lado
            $thumbPath = "{$dir}/{$baseName}_thumb.webp";
            MediaStorage::put($thumbPath, $result['thumbnail']);
        } else {
            // Não é imagem otimizável — salva original
            $path = "{$dir}/{$baseName}.{$ext}";
            MediaStorage::put($path, $decoded);
        }

        return MediaStorage::url($path);
    }

    /**
     * Salva o thumbnail de vídeo (jpegThumbnail do WhatsApp) ao lado do arquivo de vídeo.
     * Usa convenção _thumb pra que o accessor media_thumb_url no Message encontre.
     */
    private function saveVideoThumbnail(string $videoUrl, string $jpegThumbnail): void
    {
        try {
            $decoded = base64_decode(trim($jpegThumbnail), strict: false);
            if (!$decoded || strlen($decoded) < 10) return;

            // Otimiza o thumbnail pra WebP
            $optimizer = app(\App\Services\ImageOptimizer::class);
            $thumb     = $optimizer->thumbnailOnly($decoded);

            // Deriva o path do thumb a partir da URL do vídeo
            // Ex: media/2026/04/msg_xxx.mp4 → media/2026/04/msg_xxx_thumb.webp
            // A URL pode ser relativa (/storage/...) ou absoluta (https://r2.dev/...)
            $urlPath = parse_url($videoUrl, PHP_URL_PATH);
            $dotPos  = strrpos($urlPath, '.');
            if (!$dotPos) return;

            $thumbRelative = substr($urlPath, 0, $dotPos) . '_thumb.webp';

            // Remove /storage/ prefix se for URL local
            $thumbRelative = ltrim(str_replace('/storage/', '', $thumbRelative), '/');

            MediaStorage::put($thumbRelative, $thumb);
        } catch (\Throwable $e) {
            Log::warning('saveVideoThumbnail falhou', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Mapeia a instância Evolution → company_id consultando EvolutionApiConfig.
     * Usa withoutCompanyScope porque o lookup acontece ANTES de qualquer empresa
     * estar setada como tenant ativa.
     */
    private function resolveCompanyId(?string $instanceName): ?int
    {
        if (!$instanceName) return null;

        $companyId = EvolutionApiConfig::withoutCompanyScope()
            ->where('instance_name', $instanceName)
            ->value('company_id');

        return $companyId ? (int) $companyId : null;
    }
}
