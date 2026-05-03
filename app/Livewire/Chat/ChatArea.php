<?php

namespace App\Livewire\Chat;

use App\Events\ConversationStatusChanged;
use App\Events\MessageReceived;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\CrmCard;
use App\Models\CrmCardActivity;
use App\Models\CrmPipeline;
use App\Models\CrmStage;
use App\Models\Department;
use App\Models\Message;
use App\Models\QuickReply;
use App\Models\TransferLog;
use App\Models\User;
use App\Services\MediaStorage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChatArea extends Component
{
    use WithFileUploads;

    public ?int    $conversationId   = null;
    public string  $messageText      = '';
    public ?int    $editingMessageId = null;
    public bool    $showQuickReplies = false;
    public string  $quickReplySearch = '';
    public bool    $showTransfer     = false;
    public bool    $showAttachMenu   = false;
    public bool    $showEmojiPicker  = false;
    public ?int    $transferTo       = null;
    public ?int    $transferAgent    = null;
    public string  $transferReason   = '';

    // Group members
    public bool    $showGroupMembers = false;
    public array   $groupMembers     = [];

    // CRM
    public bool    $showCrmPanel     = false;
    public ?int    $crmPipelineId    = null;
    public ?int    $crmStageId       = null;

    // Responder mensagem (quote)
    public ?int $replyToId = null;

    // Upload de mídia
    public $pendingFile = null;

    public ?Conversation $conversation = null;

    public function mount(?int $conversationId = null): void
    {
        if ($conversationId) {
            $this->loadConversation($conversationId);
        }
    }

    protected function getListeners(): array
    {
        $listeners = [
            'conversation-selected' => 'loadConversation',
        ];

        if ($this->conversationId) {
            $listeners["echo-private:conversation.{$this->conversationId},message.received"] = 'handleNewMessage';
        }

        return $listeners;
    }

    public function loadConversation(int $id): void
    {
        $user = Auth::user();
        $conv = Conversation::with(['contact', 'department', 'assignedAgent'])->find($id);

        if (!$conv) return;
        if (!$user->isAdmin() && !$user->belongsToDepartment((int) $conv->department_id)) return;

        $this->conversationId = $id;
        $this->conversation   = $conv;

        // Marca mensagens como lidas
        Message::where('conversation_id', $id)
            ->where('sender_type', 'contact')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->dispatch('scroll-to-bottom');
    }

    public function handleNewMessage(array $data): void
    {
        $this->dispatch('$refresh');
        $this->dispatch('scroll-to-bottom');

        // Marca como lido automaticamente se a conversa está aberta
        if ($this->conversationId) {
            Message::where('conversation_id', $this->conversationId)
                ->where('sender_type', 'contact')
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }
    }

    // ─── Reações, edição e deleção ──────────────────────────────────────

    public function reactToMessage(int $messageId, string $emoji): void
    {
        $msg = Message::where('conversation_id', $this->conversationId)->find($messageId);
        if (!$msg || !$msg->zapi_message_id) return;

        $myPhone   = $this->resolveMyPhone();
        $reactions = $msg->reactions ?? [];
        $contact   = $this->conversation->contact;
        $remoteJid = $contact->chat_lid ?? $contact->phone;

        // Verifica se já tenho essa mesma reação (toggle)
        $existing = collect($reactions)->first(fn($r) => ($r['phone'] ?? '') === $myPhone && ($r['emoji'] ?? '') === $emoji);

        // Remove reação anterior minha
        $reactions = array_values(array_filter($reactions, fn($r) => ($r['phone'] ?? '') !== $myPhone));

        if ($existing) {
            $whatsappEmoji = '';
        } else {
            $reactions[] = ['emoji' => $emoji, 'phone' => $myPhone, 'at' => now()->toISOString()];
            $whatsappEmoji = $emoji;
        }

        $fromMe = $msg->sender_type === 'agent';
        $svc = \App\Services\WhatsAppProvider::service();
        if ($svc) {
            try {
                if ($svc instanceof \App\Services\EvolutionApiService) {
                    $result = $svc->sendReaction($msg->zapi_message_id, $remoteJid, $whatsappEmoji, $fromMe);
                } else {
                    $result = $svc->sendReaction($msg->zapi_message_id, $remoteJid, $whatsappEmoji);
                }
                Log::info('Reaction sent to WhatsApp', [
                    'messageId' => $msg->zapi_message_id,
                    'emoji'     => $whatsappEmoji ?: '(vazio/remover)',
                    'response'  => $result,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Reaction send failed', ['error' => $e->getMessage()]);
            }
        }

        $msg->update(['reactions' => $reactions]);
    }

    public function loadGroupMembers(): void
    {
        $this->showGroupMembers = !$this->showGroupMembers;
        if (!$this->showGroupMembers) return;

        $conv = $this->conversation;
        if (!$conv || !$conv->is_group) return;

        $contact = $conv->contact;
        $groupJid = $contact->chat_lid ?? $contact->phone . '@g.us';

        try {
            if (\App\Services\WhatsAppProvider::isMeta()) return; // Meta não suporta grupos

            $config = \App\Models\EvolutionApiConfig::current();
            if (!$config?->is_active) return;
            $http = \Illuminate\Support\Facades\Http::withHeaders([
                'apikey' => $config->instance_api_key ?: $config->global_api_key,
            ]);
            $result = $http->get($config->serverUrl() . "/group/findGroupInfos/{$config->instance_name}?groupJid={$groupJid}")->json();

            $participants = $result['participants'] ?? [];
            $members = [];

            foreach ($participants as $p) {
                $jid = $p['id'] ?? '';
                $phone = preg_replace('/@.*/', '', $jid);
                $role = $p['admin'] ?? 'member';

                // Try to find name from contacts or messages
                $name = null;
                $contactInfo = \App\Models\Contact::withoutGlobalScopes()
                    ->where(function ($q) use ($jid, $phone) {
                        $q->where('chat_lid', $jid)
                          ->orWhere('phone', $phone);
                    })->first();

                if ($contactInfo) {
                    $name = $contactInfo->name;
                    $phone = $contactInfo->phone;
                }

                // Try pushName from messages
                if (!$name) {
                    $msg = \App\Models\Message::where('conversation_id', $conv->id)
                        ->where('sender_phone', $phone)
                        ->whereNotNull('sender_name')
                        ->latest()->first();
                    if ($msg) $name = $msg->sender_name;
                }

                $members[] = [
                    'jid'   => $jid,
                    'phone' => $phone,
                    'name'  => $name ?: $phone,
                    'role'  => $role,
                ];
            }

            $this->groupMembers = $members;
        } catch (\Throwable $e) {
            Log::warning('loadGroupMembers failed', ['error' => $e->getMessage()]);
            $this->groupMembers = [];
        }
    }

    public function chatWithMember(string $phone): void
    {
        if (!$phone || strlen($phone) < 8) return;

        // Normaliza phone
        $realPhone = preg_replace('/\D/', '', preg_replace('/@.*/', '', $phone));
        if (strlen($realPhone) <= 11 && !str_starts_with($realPhone, '55')) {
            $realPhone = '55' . $realPhone;
        }

        // Busca ou cria contato
        $contact = \App\Models\Contact::where('phone', $realPhone)->first();
        if (!$contact) {
            $contact = \App\Models\Contact::create([
                'phone' => $realPhone,
                'chat_lid' => str_contains($phone, '@') ? $phone : null,
            ]);
        }

        // Busca ou cria conversa individual
        $conv = \App\Models\Conversation::where('contact_id', $contact->id)
            ->where('is_group', false)
            ->whereIn('status', ['open', 'pending', 'resolved'])
            ->latest()->first();

        if (!$conv) {
            $dept = \App\Models\Department::active()->first();
            $conv = \App\Models\Conversation::create([
                'contact_id'    => $contact->id,
                'department_id' => $dept->id,
                'status'        => 'open',
                'is_group'      => false,
            ]);
        }

        $this->showGroupMembers = false;
        $this->dispatch('conversation-selected', id: $conv->id);
    }

    public function startEditing(int $messageId): void
    {
        $msg = Message::where('conversation_id', $this->conversationId)
            ->where('sender_type', 'agent')
            ->find($messageId);

        if ($msg) {
            $this->editingMessageId = $messageId;
            $this->messageText = $msg->content ?? '';
            $this->dispatch('focus-message-input');
        }
    }

    public function cancelEditing(): void
    {
        $this->editingMessageId = null;
        $this->messageText = '';
    }

    public function editMessage(int $messageId, string $newText): void
    {
        if (!trim($newText)) return;

        $msg = Message::where('conversation_id', $this->conversationId)
            ->where('sender_type', 'agent')
            ->find($messageId);

        if (!$msg) return;

        // Edita no WhatsApp (se tiver zapi_message_id) — apenas Evolution suporta
        if ($msg->zapi_message_id && \App\Services\WhatsAppProvider::isEvolution()) {
            $contact = $this->conversation->contact;
            $remoteJid = $contact->chat_lid ?? $contact->phone;
            $svc = \App\Services\WhatsAppProvider::service();
            if ($svc) {
                try {
                    $svc->updateMessage($msg->zapi_message_id, $remoteJid, $newText);
                } catch (\Throwable $e) {
                    Log::warning('Edit message failed', ['error' => $e->getMessage()]);
                }
            }
        }

        $msg->update(['content' => $newText]);
        $this->dispatch('toast', type: 'success', message: 'Mensagem editada.');
    }

    public function deleteMessage(int $messageId): void
    {
        $msg = Message::where('conversation_id', $this->conversationId)
            ->where('sender_type', 'agent')
            ->find($messageId);

        if (!$msg) return;

        // Deleta no WhatsApp (se tiver zapi_message_id) — apenas Evolution suporta
        if ($msg->zapi_message_id && \App\Services\WhatsAppProvider::isEvolution()) {
            $contact = $this->conversation->contact;
            $remoteJid = $contact->chat_lid ?? $contact->phone;
            $svc = \App\Services\WhatsAppProvider::service();
            if ($svc) {
                try {
                    $svc->deleteMessage($msg->zapi_message_id, $remoteJid);
                } catch (\Throwable $e) {
                    Log::warning('Delete message failed', ['error' => $e->getMessage()]);
                }
            }
        }

        $msg->delete();
        $this->dispatch('toast', type: 'success', message: 'Mensagem excluída.');
    }

    // ─── Envio de mensagens ──────────────────────────────────────────────

    public function sendMessage(): void
    {
        if (!$this->conversationId || !trim($this->messageText)) return;

        // Se está editando, chama editMessage
        if ($this->editingMessageId) {
            $this->editMessage($this->editingMessageId, trim($this->messageText));
            $this->editingMessageId = null;
            $this->messageText = '';
            $this->dispatch('message-sent');
            return;
        }

        $this->validate(['messageText' => 'required|string|max:4096']);

        $message = Message::create([
            'conversation_id' => $this->conversationId,
            'sender_type'     => 'agent',
            'sender_id'       => Auth::id(),
            'content'         => $this->messageText,
            'type'            => 'text',
            'delivery_status' => 'pending',
            'reply_to_id'     => $this->replyToId,
        ]);

        // Auto-atribui a conversa ao agente que respondeu (tira da fila)
        $updates = ['last_message_at' => now()];
        if (!$this->conversation->assigned_to) {
            $updates['assigned_to'] = Auth::id();
            $updates['status']      = 'open';
        }
        $this->conversation->update($updates);

        try {
            SendWhatsAppMessage::dispatch($message);
        } catch (\Throwable $e) {
            Log::warning('SendWhatsAppMessage falhou', ['error' => $e->getMessage()]);
        }

        try {
            broadcast(new MessageReceived($message));
        } catch (\Throwable $e) {
            Log::warning('Broadcast falhou (Reverb offline?)', ['error' => $e->getMessage()]);
        }

        $this->messageText = '';
        $this->replyToId   = null;
        $this->dispatch('message-sent');
        $this->dispatch('scroll-to-bottom');
    }

    public function setReply(int $messageId): void
    {
        $this->replyToId = $messageId;
    }

    public function cancelReply(): void
    {
        $this->replyToId = null;
    }

    public function sendFile(): void
    {
        if (!$this->conversationId || !$this->pendingFile) return;

        // Validação de tamanho
        $this->validate([
            'pendingFile' => 'required|file|max:25600', // 25MB geral
        ]);

        $file = $this->pendingFile;
        $mime = $file->getMimeType() ?? 'application/octet-stream';
        $name = $file->getClientOriginalName();

        $type = match(true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'audio/') => 'audio',
            str_starts_with($mime, 'video/') => 'video',
            default                          => 'document',
        };

        $dir       = 'attachments/' . date('Y/m');
        $optimizer = app(\App\Services\ImageOptimizer::class);

        // Imagens: otimiza + gera thumbnail antes de salvar
        if ($type === 'image' && $optimizer->shouldOptimize($mime)) {
            $raw    = file_get_contents($file->getRealPath());
            $result = $optimizer->tryOptimize($raw, $mime);

            if ($result) {
                $baseName  = uniqid('img_', true);
                $path      = "{$dir}/{$baseName}.webp";
                $thumbPath = "{$dir}/{$baseName}_thumb.webp";

                MediaStorage::put($path, $result['optimized']);
                MediaStorage::put($thumbPath, $result['thumbnail']);

                $url     = MediaStorage::url($path);
                $content = $result['optimized'];
                $mime    = 'image/webp';
            } else {
                // Fallback: salva original se otimização falhar
                $path    = MediaStorage::store($file, $dir);
                $url     = MediaStorage::url($path);
                $content = MediaStorage::get($path);
            }
        } else {
            // Não-imagem ou não-otimizável: salva original
            $path    = MediaStorage::store($file, $dir);
            $url     = MediaStorage::url($path);
            $content = MediaStorage::get($path);
        }

        $base64  = 'data:' . $mime . ';base64,' . base64_encode($content);

        $message = Message::create([
            'conversation_id' => $this->conversationId,
            'sender_type'     => 'agent',
            'sender_id'       => Auth::id(),
            'content'         => in_array($type, ['document', 'video']) ? $name : null,
            'type'            => $type,
            'media_url'       => $url,
            'media_filename'  => $name,
            'delivery_status' => 'pending',
        ]);

        // Auto-atribui a conversa ao agente que respondeu (tira da fila)
        $fileUpdates = ['last_message_at' => now()];
        if (!$this->conversation->assigned_to) {
            $fileUpdates['assigned_to'] = Auth::id();
            $fileUpdates['status']      = 'open';
        }
        $this->conversation->update($fileUpdates);

        try {
            SendWhatsAppMessage::dispatch($message, $base64);
        } catch (\Throwable $e) {
            Log::warning('SendWhatsAppMessage falhou', ['error' => $e->getMessage()]);
        }

        try {
            broadcast(new MessageReceived($message));
        } catch (\Throwable $e) {
            Log::warning('Broadcast falhou (Reverb offline?)', ['error' => $e->getMessage()]);
        }

        $this->pendingFile    = null;
        $this->showAttachMenu = false;
        $this->dispatch('scroll-to-bottom');
    }

    public function cancelFile(): void
    {
        $this->pendingFile    = null;
        $this->showAttachMenu = false;
    }

    public function receiveAudioBlob(string $dataUrl): void
    {
        if (!$this->conversationId) return;

        // Extrai o MIME real do data URL (ex: audio/webm;codecs=opus ou audio/ogg;codecs=opus)
        [$header, $base64] = explode(',', $dataUrl, 2);

        preg_match('/data:([^;,]+)/i', $header, $mimeMatch);
        $actualMime = $mimeMatch[1] ?? 'audio/webm';

        $ext = match(true) {
            str_contains($actualMime, 'ogg')  => 'ogg',
            str_contains($actualMime, 'mp4')  => 'mp4',
            str_contains($actualMime, 'mpeg') => 'mp3',
            default                           => 'webm',
        };

        $rawContent = base64_decode($base64);
        $filename   = 'audio_' . time() . '.' . $ext;
        $path       = 'attachments/' . date('Y/m') . '/' . $filename;
        MediaStorage::put($path, $rawContent);
        $url = MediaStorage::url($path);

        // Tenta converter para ogg/opus via ffmpeg se o browser gravou em webm
        // WhatsApp exige ogg/opus para mensagens de voz
        $zapiMime = $actualMime;
        $zapiBase64 = $base64;
        if ($ext !== 'ogg' && $this->ffmpegAvailable()) {
            $converted = $this->convertToOgg($path);
            if ($converted) {
                $path     = $converted['path'];
                $filename = $converted['filename'];
                $url      = MediaStorage::url($path);
                $zapiMime = 'audio/ogg';
                $zapiBase64 = base64_encode(MediaStorage::get($path));
            }
        }

        $zapiB64 = 'data:' . $zapiMime . ';base64,' . $zapiBase64;

        // Calcula duração via ffprobe
        $audioDuration = \App\Services\AudioProbe::durationFromStorage($path);

        $message = Message::create([
            'conversation_id' => $this->conversationId,
            'sender_type'     => 'agent',
            'sender_id'       => Auth::id(),
            'type'            => 'audio',
            'media_url'       => $url,
            'media_filename'  => $filename,
            'media_duration'  => $audioDuration ? round($audioDuration, 1) : null,
            'delivery_status' => 'pending',
        ]);

        // Auto-atribui a conversa ao agente que respondeu (tira da fila)
        $audioUpdates = ['last_message_at' => now()];
        if (!$this->conversation->assigned_to) {
            $audioUpdates['assigned_to'] = Auth::id();
            $audioUpdates['status']      = 'open';
        }
        $this->conversation->update($audioUpdates);

        try {
            SendWhatsAppMessage::dispatch($message, $zapiB64);
        } catch (\Throwable $e) {
            Log::warning('SendWhatsAppMessage falhou', ['error' => $e->getMessage()]);
        }

        try {
            broadcast(new MessageReceived($message));
        } catch (\Throwable $e) {
            Log::warning('Broadcast falhou (Reverb offline?)', ['error' => $e->getMessage()]);
        }

        $this->dispatch('scroll-to-bottom');
    }

    private function resolveMyPhone(): string
    {
        if (\App\Services\WhatsAppProvider::isMeta()) {
            return \App\Models\MetaWhatsAppConfig::current()?->phone_display ?? 'crm';
        }
        return \App\Models\EvolutionApiConfig::current()?->phone_number ?? 'crm';
    }

    private function ffmpegAvailable(): bool
    {
        $candidates = ['/opt/homebrew/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/usr/bin/ffmpeg'];
        foreach ($candidates as $path) {
            if (file_exists($path)) return true;
        }
        return false;
    }

    private function convertToOgg(string $sourcePath): ?array
    {
        $ffmpeg = null;
        foreach (['/opt/homebrew/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/usr/bin/ffmpeg'] as $p) {
            if (file_exists($p)) { $ffmpeg = $p; break; }
        }
        if (!$ffmpeg) return null;

        // Se cloud: baixa pra temp local; se local: usa path direto
        if (MediaStorage::isCloud()) {
            $srcFull = MediaStorage::downloadToTemp($sourcePath);
            if (!$srcFull) return null;
        } else {
            $srcFull = storage_path('app/public/' . $sourcePath);
        }

        $oggName  = 'audio_' . time() . '_c.ogg';
        $oggRel   = 'attachments/' . date('Y/m') . '/' . $oggName;
        $oggFull  = sys_get_temp_dir() . '/' . $oggName;

        exec("{$ffmpeg} -y -i " . escapeshellarg($srcFull) . " -c:a libopus -b:a 32k " . escapeshellarg($oggFull) . " 2>/dev/null", $out, $code);

        if ($code === 0 && file_exists($oggFull)) {
            // Sobe o convertido pro disco de mídia
            MediaStorage::put($oggRel, file_get_contents($oggFull));
            // Remove originais (temp local + source no disco de mídia)
            @unlink($oggFull);
            if (MediaStorage::isCloud()) @unlink($srcFull);
            MediaStorage::delete($sourcePath);
            return ['path' => $oggRel, 'filename' => $oggName];
        }

        // Cleanup temp
        if (MediaStorage::isCloud() && $srcFull) @unlink($srcFull);
        if (file_exists($oggFull)) @unlink($oggFull);
        return null;
    }

    public function useQuickReply(string $content): void
    {
        $this->messageText      = $content;
        $this->showQuickReplies = false;
    }

    public function resolveConversation(): void
    {
        if (!$this->conversationId) return;

        $this->conversation->update(['status' => 'resolved']);

        try {
            broadcast(new ConversationStatusChanged($this->conversation));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcast falhou (Reverb offline?)', ['error' => $e->getMessage()]);
        }

        // Mensagem de sistema
        Message::create([
            'conversation_id' => $this->conversationId,
            'sender_type'     => 'system',
            'content'         => 'Atendimento encerrado por ' . Auth::user()->name,
            'type'            => 'text',
            'delivery_status' => 'sent',
        ]);

        // Limpa a tela e atualiza a lista — conversa vai pro filtro "Resolvidos"
        $this->conversationId = null;
        $this->conversation   = null;
        $this->dispatch('conversation-deleted');
    }

    public function reopenConversation(): void
    {
        if (!$this->conversationId) return;
        $this->conversation->update(['status' => 'open']);

        try {
            broadcast(new ConversationStatusChanged($this->conversation));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcast falhou (Reverb offline?)', ['error' => $e->getMessage()]);
        }

        $this->conversation->refresh();
    }

    public function deleteConversation(): void
    {
        if (!$this->conversationId || !Auth::user()->isAdmin()) return;

        $conv = $this->conversation;

        // Remove mensagens e logs de transferência antes de deletar a conversa
        Message::where('conversation_id', $conv->id)->delete();
        \App\Models\TransferLog::where('conversation_id', $conv->id)->delete();
        $conv->delete();

        $this->conversationId = null;
        $this->conversation   = null;

        $this->dispatch('conversation-deleted');
    }

    public function openCrmPanel(): void
    {
        $this->showCrmPanel  = true;
        $this->showTransfer  = false;

        // Pré-seleciona pipeline/etapa existente do contato (se já estiver em algum)
        $contactId = $this->conversation?->contact_id;
        if ($contactId) {
            $existing = CrmCard::where('contact_id', $contactId)->latest()->first();
            if ($existing) {
                $this->crmPipelineId = $existing->pipeline_id;
                $this->crmStageId    = $existing->stage_id;
                return;
            }
        }

        // Padrão: primeiro pipeline ativo
        $pipeline = CrmPipeline::active()->orderBy('sort_order')->first();
        if ($pipeline) {
            $this->crmPipelineId = $pipeline->id;
            $this->crmStageId    = CrmStage::where('pipeline_id', $pipeline->id)
                ->orderBy('sort_order')->first()?->id;
        }
    }

    public function updatedCrmPipelineId(?int $value): void
    {
        // Ao trocar pipeline, seleciona primeira etapa
        $this->crmStageId = CrmStage::where('pipeline_id', $value)
            ->orderBy('sort_order')->first()?->id;
    }

    public function saveCrmCard(): void
    {
        $this->validate([
            'crmPipelineId' => 'required|exists:crm_pipelines,id',
            'crmStageId'    => 'required|exists:crm_stages,id',
        ]);

        $contact  = $this->conversation->contact;
        $stage    = CrmStage::find($this->crmStageId);
        $pipeline = CrmPipeline::find($this->crmPipelineId);

        // Busca o único card existente do contato (independente do pipeline)
        $existing = CrmCard::where('contact_id', $contact->id)->latest()->first();

        if ($existing) {
            $oldPipeline = $existing->pipeline?->name ?? '—';
            $oldStage    = $existing->stage?->name    ?? '—';

            $existing->update([
                'pipeline_id' => $this->crmPipelineId,
                'stage_id'    => $this->crmStageId,
            ]);

            CrmCardActivity::create([
                'card_id' => $existing->id,
                'user_id' => auth()->id(),
                'type'    => 'stage_change',
                'content' => "Movido via Atendimento: {$oldPipeline} / {$oldStage} → {$pipeline->name} / {$stage->name}",
            ]);
            $this->dispatch('toast', type: 'success', message: "Contato movido para {$pipeline->name} · {$stage->name}.");
        } else {
            $card = CrmCard::create([
                'pipeline_id' => $this->crmPipelineId,
                'stage_id'    => $this->crmStageId,
                'contact_id'  => $contact->id,
                'assigned_to' => auth()->id(),
                'title'       => $contact->name ?: $contact->phone,
                'sort_order'  => CrmCard::where('stage_id', $this->crmStageId)->max('sort_order') + 1,
            ]);
            CrmCardActivity::create([
                'card_id' => $card->id,
                'user_id' => auth()->id(),
                'type'    => 'note',
                'content' => "Adicionado via Atendimento — conversa #{$this->conversation->protocol}",
            ]);
            $this->dispatch('toast', type: 'success', message: "Contato adicionado ao CRM em {$pipeline->name} · {$stage->name}.");
        }

        $this->showCrmPanel  = false;
        $this->crmPipelineId = null;
        $this->crmStageId    = null;
    }

    public function updatedTransferTo($value): void
    {
        // Limpa o agente selecionado quando o departamento muda
        $this->transferAgent = null;
    }

    /**
     * Abre (ou cria) uma conversa particular com o remetente de uma mensagem de grupo.
     */
    public function openPrivateChat(int $messageId): void
    {
        $msg = Message::find($messageId);
        if (!$msg || !$msg->sender_phone) {
            $this->dispatch('toast', type: 'error', message: 'Telefone do remetente não disponível.');
            return;
        }

        $phone = $msg->sender_phone;
        $name  = $msg->sender_name;

        // Busca ou cria o contato individual
        $contact = Contact::where('phone', $phone)->first();
        if (!$contact) {
            $contact = Contact::create([
                'phone' => $phone,
                'name'  => $name,
            ]);
        }

        // Busca conversa individual existente (não resolvida)
        $conv = Conversation::where('contact_id', $contact->id)
            ->where('is_group', false)
            ->whereIn('status', ['open', 'pending', 'transferred'])
            ->latest()
            ->first();

        if (!$conv) {
            // Cria nova conversa individual no mesmo departamento
            $conv = Conversation::create([
                'contact_id'    => $contact->id,
                'department_id' => $this->conversation->department_id ?? Department::active()->first()?->id,
                'assigned_to'   => Auth::id(),
                'status'        => 'open',
                'is_group'      => false,
            ]);
        }

        $this->dispatch('conversation-selected', id: $conv->id);
        $this->dispatch('toast', type: 'success', message: "Conversa particular com {$contact->name} aberta.");
    }

    public function transferConversation(): void
    {
        $this->validate([
            'transferTo'     => 'required|exists:departments,id',
            'transferAgent'  => 'nullable|exists:users,id',
            'transferReason' => 'nullable|string|max:255',
        ]);

        if (!$this->conversationId) return;

        // Garante que o agente escolhido (se houver) pertence ao departamento alvo.
        // Aceita tanto departamento principal quanto adicional (pivô).
        // Defesa em profundidade: filtra também por company_id da empresa atual.
        if ($this->transferAgent) {
            $candidate = User::where('id', $this->transferAgent)
                ->where('is_active', true)
                ->where('company_id', app(\App\Services\CurrentCompany::class)->id())
                ->first();

            if (!$candidate || !$candidate->belongsToDepartment((int) $this->transferTo)) {
                $this->transferAgent = null;
            }
        }

        $user = Auth::user();
        $conv = $this->conversation;

        TransferLog::create([
            'conversation_id'    => $conv->id,
            'from_department_id' => $conv->department_id,
            'to_department_id'   => $this->transferTo,
            'from_agent_id'      => $user->id,
            'to_agent_id'        => $this->transferAgent,
            'reason'             => $this->transferReason,
        ]);

        $targetAgent = $this->transferAgent ? User::find($this->transferAgent) : null;
        $department  = Department::find($this->transferTo);

        $conv->update([
            'department_id' => $this->transferTo,
            'assigned_to'   => $this->transferAgent,
            // Quando vai direto pra um agente, já entra como 'open' pra ele atender;
            // sem agente fica 'transferred' pra fila do setor.
            'status'        => $this->transferAgent ? 'open' : 'transferred',
        ]);

        $systemMsg = $targetAgent
            ? "Conversa transferida para {$department->name} → {$targetAgent->name}."
            : "Conversa transferida para o departamento {$department->name}.";

        Message::create([
            'conversation_id' => $conv->id,
            'sender_type'     => 'system',
            'content'         => $systemMsg,
            'type'            => 'text',
            'delivery_status' => 'sent',
        ]);

        $this->showTransfer   = false;
        $this->transferTo     = null;
        $this->transferAgent  = null;
        $this->transferReason = '';
        $this->conversationId = null;
        $this->conversation   = null;

        // Atualiza a lista de conversas pra refletir a transferência
        $this->dispatch('conversation-deleted');
    }

    public function render()
    {
        $messages       = [];
        $quickReplies   = [];
        $departments    = [];
        $transferAgents = collect();
        $crmPipelines   = collect();
        $crmStages      = collect();
        $crmCards       = collect();

        if ($this->conversationId) {
            $messages = Message::where('conversation_id', $this->conversationId)
                ->with(['sender', 'replyTo'])
                ->orderBy('created_at')
                ->get();

            if ($this->showQuickReplies) {
                $user    = Auth::user();
                $deptIds = $user->departmentIds();

                $quickReplies = QuickReply::where(function ($q) use ($deptIds) {
                    $q->whereNull('department_id');
                    if (!empty($deptIds)) {
                        $q->orWhereIn('department_id', $deptIds);
                    }
                })->when($this->quickReplySearch, fn($q) =>
                    $q->where('title', 'like', "%{$this->quickReplySearch}%")
                      ->orWhere('content', 'like', "%{$this->quickReplySearch}%")
                )->get();
            }

            if ($this->showTransfer) {
                $departments = Department::active()->get();

                if ($this->transferTo) {
                    $transferAgents = User::active()
                        ->where('company_id', app(\App\Services\CurrentCompany::class)->id())
                        ->byDepartment($this->transferTo)
                        ->whereIn('role', ['agent', 'supervisor'])
                        ->orderBy('name')
                        ->get(['id', 'name', 'role', 'avatar']);
                }
            }

            // CRM cards do contato — sempre carregados para o badge no header
            $contactId = $this->conversation?->contact_id;
            if ($contactId) {
                $crmCards = CrmCard::where('contact_id', $contactId)
                    ->with(['pipeline', 'stage'])
                    ->get();
            }

            if ($this->showCrmPanel) {
                $crmPipelines = CrmPipeline::active()->orderBy('sort_order')->get();
                if ($this->crmPipelineId) {
                    $crmStages = CrmStage::where('pipeline_id', $this->crmPipelineId)
                        ->orderBy('sort_order')->get();
                }
            }
        }

        $myReactionPhone = $this->resolveMyPhone();
        $replyToMessage  = $this->replyToId ? Message::find($this->replyToId) : null;

        return view('livewire.chat.chat-area', compact(
            'messages', 'quickReplies', 'departments', 'transferAgents',
            'crmPipelines', 'crmStages', 'crmCards', 'myReactionPhone', 'replyToMessage'
        ));
    }
}
