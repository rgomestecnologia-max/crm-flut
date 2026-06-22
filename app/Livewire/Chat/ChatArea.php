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
    public array   $mentionJids      = [];

    // CRM
    public bool    $showCrmPanel     = false;
    public ?int    $crmPipelineId    = null;
    public ?int    $crmStageId       = null;

    // Responder mensagem (quote)
    public ?int $replyToId = null;

    // Upload de mídia
    public $pendingFile = null;
    public $pendingFiles = [];


    // Enviar contato (vCard)
    public bool   $showContactPicker = false;
    public string $contactSearch     = '';

    // Encaminhar mensagem
    public bool   $showForwardPicker = false;
    public ?int   $forwardMessageId  = null;
    public array  $forwardMessageIds = [];
    public bool   $msgSelectMode     = false;
    public string $forwardSearch     = '';
    public array  $forwardSelected   = [];
    public array  $forwardInternalTargets = []; // 'user_5', 'group_3'
    public string $forwardCaption    = '';

    public ?Conversation $conversation = null;

    // FlutChat ao vivo
    public ?int $flutChatConvId = null;

    // Paginação de mensagens
    public int $messageLimit = 100;
    public bool $hasOlderMessages = false;

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
            'flutchat-selected'     => 'loadFlutChat',
        ];

        if ($this->conversationId) {
            $listeners["echo-private:conversation.{$this->conversationId},message.received"] = 'handleNewMessage';
        }

        return $listeners;
    }

    public function loadFlutChat(int $id): void
    {
        $this->flutChatConvId = $id;
        $this->conversationId = null;
        $this->conversation   = null;
        $this->messageText    = '';
        $this->dispatch('scroll-to-bottom');
    }

    public function sendFlutChatReply(): void
    {
        if (!$this->flutChatConvId || !trim($this->messageText)) return;

        $conv = \App\Models\FlutChatConversation::find($this->flutChatConvId);
        if (!$conv) return;

        \App\Models\FlutChatMessage::create([
            'conversation_id' => $conv->id,
            'sender_type'     => 'agent',
            'sender_id'       => Auth::id(),
            'content'         => trim($this->messageText),
        ]);

        $conv->update([
            'last_message_at' => now(),
            'assigned_to'     => $conv->assigned_to ?? Auth::id(),
        ]);

        $this->messageText = '';
        $this->dispatch('message-sent');
        $this->dispatch('scroll-to-bottom');
    }

    public function receiveFlutChatAudio(string $dataUrl): void
    {
        if (!$this->flutChatConvId) return;

        $conv = \App\Models\FlutChatConversation::find($this->flutChatConvId);
        if (!$conv) return;

        [$header, $base64] = explode(',', $dataUrl, 2);
        $raw = base64_decode($base64);
        $ext = str_contains($header, 'ogg') ? 'ogg' : 'webm';

        $dir = 'attachments/' . date('Y/m');
        $filename = 'audio_' . uniqid() . '.' . $ext;
        $path = $dir . '/' . $filename;
        \App\Services\MediaStorage::put($path, $raw);
        $url = \App\Services\MediaStorage::url($path);

        \App\Models\FlutChatMessage::create([
            'conversation_id' => $conv->id,
            'sender_type'     => 'agent',
            'sender_id'       => Auth::id(),
            'content'         => '',
            'media_url'       => $url,
            'media_type'      => 'audio',
            'media_filename'  => $filename,
        ]);

        $conv->update(['last_message_at' => now(), 'assigned_to' => Auth::id()]);
        $this->dispatch('scroll-to-bottom');
    }

    public function sendFlutChatFile(): void
    {
        if (!$this->flutChatConvId || empty($this->pendingFiles)) return;

        $conv = \App\Models\FlutChatConversation::find($this->flutChatConvId);
        if (!$conv) return;

        foreach ($this->pendingFiles as $file) {
            $mime = $file->getMimeType() ?? 'application/octet-stream';
            $name = $file->getClientOriginalName();
            $type = match(true) {
                str_starts_with($mime, 'image/') => 'image',
                str_starts_with($mime, 'audio/') => 'audio',
                str_starts_with($mime, 'video/') => 'video',
                default                          => 'document',
            };

            $dir  = 'attachments/' . date('Y/m');
            $path = \App\Services\MediaStorage::store($file, $dir);
            $url  = \App\Services\MediaStorage::url($path);

            \App\Models\FlutChatMessage::create([
                'conversation_id' => $conv->id,
                'sender_type'     => 'agent',
                'sender_id'       => Auth::id(),
                'content'         => $type === 'document' ? $name : '',
                'media_url'       => $url,
                'media_type'      => $type,
                'media_filename'  => $name,
            ]);
        }

        $conv->update(['last_message_at' => now(), 'assigned_to' => Auth::id()]);
        $this->pendingFiles = [];
        $this->dispatch('scroll-to-bottom');
        $this->dispatch('toast', type: 'success', message: 'Arquivo(s) enviado(s).');
    }

    public function closeFlutChat(): void
    {
        if ($this->flutChatConvId) {
            // Envia aviso de encerramento para o visitante
            \App\Models\FlutChatMessage::create([
                'conversation_id' => $this->flutChatConvId,
                'sender_type'     => 'system',
                'content'         => 'Atendimento encerrado. Obrigado pelo contato!',
            ]);

            \App\Models\FlutChatConversation::find($this->flutChatConvId)?->update(['status' => 'closed']);
            $this->flutChatConvId = null;
            $this->dispatch('conversation-deleted');
            $this->dispatch('toast', type: 'success', message: 'Conversa FlutChat encerrada.');
        }
    }

    public function deleteFlutChat(int $id): void
    {
        $conv = \App\Models\FlutChatConversation::find($id);
        if ($conv) {
            \App\Models\FlutChatMessage::where('conversation_id', $id)->delete();
            $conv->delete();
            if ($this->flutChatConvId === $id) $this->flutChatConvId = null;
            $this->dispatch('conversation-deleted');
            $this->dispatch('toast', type: 'success', message: 'Conversa excluída.');
        }
    }

    public function loadMoreMessages(): void
    {
        // Guarda o ID da mensagem mais antiga atualmente visível (será a âncora do scroll)
        $oldestVisibleId = Message::where('conversation_id', $this->conversationId)
            ->orderBy('created_at', 'desc')
            ->take($this->messageLimit)
            ->get()
            ->last()?->id;

        $this->messageLimit += 100;

        // Após o wire:key substituir o container, rola até a mensagem âncora
        if ($oldestVisibleId) {
            $this->dispatch('scroll-to-message', id: $oldestVisibleId);
        }
    }

    public function loadConversation(int $id): void
    {
        $this->messageLimit = 100; // Reset ao trocar de conversa
        $this->flutChatConvId = null; // Sai do modo FlutChat
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
                } elseif ($svc instanceof \App\Services\ZapiService) {
                    $result = $svc->sendReaction($remoteJid, $msg->zapi_message_id, $whatsappEmoji);
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

    public function loadGroupMembersForMention(): void
    {
        if (!empty($this->groupMembers)) return;
        $this->fetchGroupMembers();
    }

    public function loadGroupMembers(): void
    {
        $this->showGroupMembers = !$this->showGroupMembers;
        if (!$this->showGroupMembers) return;
        $this->fetchGroupMembers();
    }

    private function fetchGroupMembers(): void
    {

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
                $isLid = str_contains($jid, '@lid');
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
                    // Se o contato tem um phone real, usar em vez do LID
                    if ($contactInfo->phone && preg_match('/^55\d{10,11}$/', $contactInfo->phone)) {
                        $phone = $contactInfo->phone;
                        $isLid = false;
                    }
                }

                // Se ainda é LID, tentar resolver via Evolution API
                if ($isLid && $config) {
                    try {
                        $resolved = $http->post($config->serverUrl() . "/chat/findContacts/{$config->instance_name}", [
                            'where' => ['id' => $jid],
                        ])->json();
                        $resolvedId = $resolved[0]['id'] ?? null;
                        if ($resolvedId && str_contains($resolvedId, '@s.whatsapp.net')) {
                            $phone = preg_replace('/\D/', '', preg_replace('/@.*/', '', $resolvedId));
                            $isLid = false;
                        }
                    } catch (\Throwable) {}
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
                    'is_lid' => $isLid,
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
        if (str_starts_with($realPhone, '0') && !str_starts_with($realPhone, '00')) {
            $realPhone = substr($realPhone, 1);
        }
        if (strlen($realPhone) <= 11 && !str_starts_with($realPhone, '55')) {
            $realPhone = '55' . $realPhone;
        }

        // Valida que não é um LID
        if (!preg_match('/^55\d{10,11}$/', $realPhone)) {
            $this->dispatch('toast', type: 'error', message: 'Não foi possível identificar o número real deste membro.');
            return;
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

        // Edita no WhatsApp (se tiver zapi_message_id)
        if ($msg->zapi_message_id) {
            $contact = $this->conversation->contact;
            $remoteJid = $contact->chat_lid ?? ($contact->phone . '@s.whatsapp.net');
            $specificConfig = $this->conversation->evolution_api_config_id
                ? \App\Models\EvolutionApiConfig::find($this->conversation->evolution_api_config_id)
                : null;
            $svc = \App\Services\WhatsAppProvider::service($specificConfig);
            try {
                if ($svc instanceof \App\Services\EvolutionApiService) {
                    $svc->updateMessage($msg->zapi_message_id, $remoteJid, $newText);
                } elseif ($svc instanceof \App\Services\ZapiService) {
                    $svc->editMessage($remoteJid, $msg->zapi_message_id, $newText);
                }
            } catch (\Throwable $e) {
                Log::warning('Edit message failed', ['error' => $e->getMessage()]);
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

        // Deleta no WhatsApp (se tiver zapi_message_id)
        if ($msg->zapi_message_id) {
            $contact = $this->conversation->contact;
            $remoteJid = $contact->chat_lid ?? ($contact->phone . '@s.whatsapp.net');
            $specificConfig = $this->conversation->evolution_api_config_id
                ? \App\Models\EvolutionApiConfig::find($this->conversation->evolution_api_config_id)
                : null;
            $svc = \App\Services\WhatsAppProvider::service($specificConfig);
            try {
                if ($svc instanceof \App\Services\EvolutionApiService) {
                    $svc->deleteMessage($msg->zapi_message_id, $remoteJid);
                } elseif ($svc instanceof \App\Services\ZapiService) {
                    $svc->deleteMessage($msg->zapi_message_id);
                }
            } catch (\Throwable $e) {
                Log::warning('Delete message failed', ['error' => $e->getMessage()]);
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

        // Auto-atribui a conversa ao agente que respondeu (reatribui se outro agente responder)
        $updates = ['last_message_at' => now(), 'menu_awaiting' => false, 'waiting_human_reason' => null, 'assigned_to' => Auth::id(), 'status' => 'open'];
        $this->conversation->update($updates);

        $mentions = !empty($this->mentionJids) ? $this->mentionJids : null;

        try {
            SendWhatsAppMessage::dispatch($message, null, $mentions);
        } catch (\Throwable $e) {
            Log::warning('SendWhatsAppMessage falhou', ['error' => $e->getMessage()]);
        }

        try {
            broadcast(new MessageReceived($message));
        } catch (\Throwable $e) {
            Log::warning('Broadcast falhou (Reverb offline?)', ['error' => $e->getMessage()]);
        }

        $this->messageText  = '';
        $this->replyToId    = null;
        $this->mentionJids  = [];
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

        // Validação de tamanho (180MB para vídeos, 25MB para o resto)
        $mime = $this->pendingFile->getMimeType() ?? '';
        $maxSize = str_starts_with($mime, 'video/') ? 184320 : 25600;
        $this->validate([
            'pendingFile' => 'required|file|max:' . $maxSize,
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
                $path      = "{$dir}/{$baseName}.jpg";
                $thumbPath = "{$dir}/{$baseName}_thumb.jpg";

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
        } elseif ($type === 'video') {
            // Vídeo: comprimir com ffmpeg antes de salvar
            $compressed = $this->compressVideo($file->getRealPath());
            $videoSource = $compressed ?: $file->getRealPath();
            $baseName = uniqid('vid_', true);

            if ($compressed) {
                $path = "{$dir}/{$baseName}.mp4";
                MediaStorage::put($path, file_get_contents($compressed));
                $url = MediaStorage::url($path);
                $mime = 'video/mp4';
                $name = pathinfo($name, PATHINFO_FILENAME) . '.mp4';
            } else {
                $path = MediaStorage::store($file, $dir);
                $url = MediaStorage::url($path);
                $baseName = pathinfo($path, PATHINFO_FILENAME);
            }

            // Gera thumbnail do vídeo
            $thumbPath = $this->generateVideoThumbnail($videoSource);
            if ($thumbPath) {
                $thumbStoragePath = "{$dir}/{$baseName}_thumb.jpg";
                MediaStorage::put($thumbStoragePath, file_get_contents($thumbPath));
                @unlink($thumbPath);
            }
            if ($compressed) @unlink($compressed);
        } else {
            // Não-imagem ou não-otimizável: salva original
            $path    = MediaStorage::store($file, $dir);
            $url     = MediaStorage::url($path);
        }

        // Base64 apenas para imagens (pequenas). Vídeos e documentos usam URL diretamente.
        $base64 = null;
        if ($type === 'image' && isset($content)) {
            $base64 = 'data:' . $mime . ';base64,' . base64_encode($content);
        }

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

        // Auto-atribui a conversa ao agente que respondeu
        $this->conversation->update(['last_message_at' => now(), 'assigned_to' => Auth::id(), 'status' => 'open']);

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
        $this->pendingFiles   = [];
        $this->showAttachMenu = false;
        $this->dispatch('scroll-to-bottom');
    }

    public function sendFiles(): void
    {
        if (!$this->conversationId || empty($this->pendingFiles)) return;

        foreach ($this->pendingFiles as $file) {
            $this->pendingFile = $file;
            $this->sendFile();
        }

        $this->pendingFiles = [];
    }

    public function sendPastedImage(string $dataUrl): void
    {
        if (!$this->conversationId) return;

        try {
            \Log::info('sendPastedImage: início', ['dataUrl_len' => strlen($dataUrl), 'conv' => $this->conversationId]);

            $parts = explode(',', $dataUrl, 2);
            if (count($parts) < 2) {
                \Log::error('sendPastedImage: dataUrl sem vírgula');
                return;
            }
            [$header, $base64] = $parts;
            $raw = base64_decode($base64);
            if (!$raw || strlen($raw) < 100) {
                \Log::error('sendPastedImage: decode falhou ou muito pequeno', ['raw_len' => strlen($raw ?? '')]);
                return;
            }

            \Log::info('sendPastedImage: imagem decodificada', ['raw_bytes' => strlen($raw), 'header' => $header]);

            $dir       = 'attachments/' . date('Y/m');
            $baseName  = uniqid('paste_', true);
            $optimizer = app(\App\Services\ImageOptimizer::class);
            $mime      = str_contains($header, 'image/jpeg') ? 'image/jpeg' : 'image/png';
            $result    = $optimizer->tryOptimize($raw, $mime);

            if ($result) {
                $path = "{$dir}/{$baseName}.jpg";
                \App\Services\MediaStorage::put($path, $result['optimized']);
                $thumbPath = "{$dir}/{$baseName}_thumb.jpg";
                \App\Services\MediaStorage::put($thumbPath, $result['thumbnail']);
            } else {
                $path = "{$dir}/{$baseName}.jpg";
                \App\Services\MediaStorage::put($path, $raw);
            }

            $url = \App\Services\MediaStorage::url($path);

            $message = Message::create([
                'conversation_id' => $this->conversationId,
                'sender_type'     => 'agent',
                'sender_id'       => Auth::id(),
                'type'            => 'image',
                'media_url'       => $url,
                'delivery_status' => 'pending',
            ]);

            $this->conversation->update(['last_message_at' => now(), 'assigned_to' => Auth::id(), 'status' => 'open']);

            try { SendWhatsAppMessage::dispatch($message); } catch (\Throwable) {}
            try { broadcast(new MessageReceived($message)); } catch (\Throwable) {}

            $this->dispatch('scroll-to-bottom');

            \Log::info('sendPastedImage: sucesso', ['message_id' => $message->id, 'url' => $url]);
        } catch (\Throwable $e) {
            \Log::error('sendPastedImage: ERRO', [
                'error'   => $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
                'trace'   => substr($e->getTraceAsString(), 0, 500),
            ]);
            throw $e;
        }
    }

    public function cancelFile(): void
    {
        $this->pendingFile    = null;
        $this->pendingFiles   = [];
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

        // Auto-atribui a conversa ao agente que respondeu
        $this->conversation->update(['last_message_at' => now(), 'assigned_to' => Auth::id(), 'status' => 'open']);

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

    private function syncConversationTagFromPipeline(CrmCard $card): void
    {
        if (!$card->contact_id || !$this->conversationId) return;

        $pipelineName = $card->pipeline?->name;
        if (!$pipelineName) return;

        $tag = \App\Models\Tag::where('name', $pipelineName)->first();
        if (!$tag) return;

        if (!$this->conversation->tags()->where('tags.id', $tag->id)->exists()) {
            $this->conversation->tags()->attach($tag->id);
        }
    }

    public function useQuickReply(int $id): void
    {
        $qr = \App\Models\QuickReply::find($id);
        if ($qr) {
            $this->messageText = $qr->content;
        }
        $this->showQuickReplies = false;

        // Se FlutChat ativo, envia direto
        if ($this->flutChatConvId && $qr) {
            $this->sendFlutChatReply();
            return;
        }

        $this->dispatch('focus-message-input');
    }

    public function sendContact(int $contactId): void
    {
        if (!$this->conversationId) return;

        $contact = \App\Models\BroadcastContact::find($contactId);
        if (!$contact || !$contact->phone) return;

        $name  = $contact->name ?: 'Contato';
        $phone = preg_replace('/\D/', '', $contact->phone);

        $message = Message::create([
            'conversation_id' => $this->conversationId,
            'sender_type'     => 'agent',
            'sender_id'       => \Illuminate\Support\Facades\Auth::id(),
            'type'            => 'contact',
            'content'         => "📇 *{$name}*\n📱 +{$phone}",
            'media_filename'  => $name,
            'media_url'       => $phone,
            'delivery_status' => 'pending',
        ]);

        \App\Jobs\SendWhatsAppMessage::dispatch($message);

        $this->showContactPicker = false;
        $this->contactSearch = '';

        $this->conversation->update(['last_message_at' => now()]);
        $this->dispatch('toast', type: 'success', message: 'Contato enviado.');
    }

    public function openForward(int $messageId): void
    {
        $this->forwardMessageId  = $messageId;
        $this->forwardMessageIds = [$messageId];
        $this->showForwardPicker = true;
        $this->forwardSearch     = '';
        $this->forwardSelected   = [];
        $this->forwardInternalTargets = [];
        $this->forwardCaption    = '';
    }

    public function toggleMessageSelect(int $msgId): void
    {
        if (in_array($msgId, $this->forwardMessageIds)) {
            $this->forwardMessageIds = array_values(array_diff($this->forwardMessageIds, [$msgId]));
        } else {
            $this->forwardMessageIds[] = $msgId;
        }
    }

    public function enterSelectMode(int $msgId): void
    {
        $this->msgSelectMode = true;
        $this->forwardMessageIds = [$msgId];
    }

    public function cancelSelectMode(): void
    {
        $this->msgSelectMode = false;
        $this->forwardMessageIds = [];
    }

    public function toggleForwardInternal(string $key): void
    {
        if (in_array($key, $this->forwardInternalTargets)) {
            $this->forwardInternalTargets = array_values(array_diff($this->forwardInternalTargets, [$key]));
        } else {
            $this->forwardInternalTargets[] = $key;
        }
    }

    public function openForwardSelected(): void
    {
        if (empty($this->forwardMessageIds)) return;
        $this->forwardMessageId = $this->forwardMessageIds[0];
        $this->showForwardPicker = true;
        $this->forwardSearch     = '';
        $this->forwardSelected   = [];
        $this->forwardInternalTargets = [];
        $this->forwardCaption    = '';
    }

    public function toggleForwardSelect(int $convId): void
    {
        if (in_array($convId, $this->forwardSelected)) {
            $this->forwardSelected = array_values(array_diff($this->forwardSelected, [$convId]));
        } else {
            $this->forwardSelected[] = $convId;
        }
    }

    public function sendForward()
    {
        $messageIds = !empty($this->forwardMessageIds) ? $this->forwardMessageIds : ($this->forwardMessageId ? [$this->forwardMessageId] : []);
        if (empty($messageIds) || (empty($this->forwardSelected) && empty($this->forwardInternalTargets))) return;

        // Ordena mensagens pela ordem original
        $sources = Message::whereIn('id', $messageIds)->orderBy('id')->get();
        if ($sources->isEmpty()) return;

        $userId = \Illuminate\Support\Facades\Auth::id();
        $caption = trim($this->forwardCaption);
        $lastTargetId = null;
        $count = 0;

        foreach ($this->forwardSelected as $targetId) {
            $target = Conversation::find($targetId);
            if (!$target) continue;

            foreach ($sources as $source) {
                $content = $source->content;
                if ($caption && in_array($source->type, ['image', 'video', 'document'])) {
                    $content = $caption;
                }

                $fwd = Message::create([
                    'conversation_id' => $target->id,
                    'sender_type'     => 'agent',
                    'sender_id'       => $userId,
                    'content'         => $content,
                    'type'            => $source->type,
                    'media_url'       => $source->media_url,
                    'media_filename'  => $source->media_filename,
                    'media_duration'  => $source->media_duration,
                    'delivery_status' => 'pending',
                ]);

                $target->update(['last_message_at' => now()]);
                try { \App\Jobs\SendWhatsAppMessage::dispatch($fwd); } catch (\Throwable) {}
                try { broadcast(new \App\Events\MessageReceived($fwd)); } catch (\Throwable) {}
            }

            // Legenda como mensagem separada (só uma vez por destino)
            if ($caption && $sources->first()->type === 'text') {
                $captionMsg = Message::create([
                    'conversation_id' => $target->id,
                    'sender_type'     => 'agent',
                    'sender_id'       => $userId,
                    'content'         => $caption,
                    'type'            => 'text',
                    'delivery_status' => 'pending',
                ]);
                try { \App\Jobs\SendWhatsAppMessage::dispatch($captionMsg); } catch (\Throwable) {}
            }

            $lastTargetId = $targetId;
            $count++;
        }

        // Encaminhar para Chat Interno (agentes e grupos)
        $internalCount = 0;
        foreach ($this->forwardInternalTargets as $target) {
            [$type, $id] = explode('_', $target, 2);
            foreach ($sources as $source) {
                $content = $source->type === 'text' ? $source->content : ($source->media_filename ?? $source->content ?? '[Mídia]');
                \App\Models\InternalMessage::create([
                    'sender_id'    => $userId,
                    'recipient_id' => $type === 'user' ? (int) $id : null,
                    'group_id'     => $type === 'group' ? (int) $id : null,
                    'content'      => $content,
                    'type'         => $source->media_url ? $source->type : 'text',
                    'media_url'    => $source->media_url,
                    'media_filename' => $source->media_filename,
                ]);
            }
            $internalCount++;
        }

        $this->showForwardPicker = false;
        $this->forwardMessageId  = null;
        $this->forwardMessageIds = [];
        $this->msgSelectMode     = false;
        $this->forwardSearch     = '';
        $this->forwardSelected   = [];
        $this->forwardInternalTargets = [];
        $this->forwardCaption    = '';

        $msgCount = $sources->count();
        $totalTargets = $count + $internalCount;

        // Se encaminhou para chat interno, redireciona para lá
        if ($internalCount > 0 && $count === 0) {
            $this->dispatch('toast', type: 'success', message: "{$msgCount} mensagem(ns) encaminhada(s) para {$internalCount} destino(s) no Chat Interno.");
            return $this->redirect('/internal-chat');
        }

        if ($lastTargetId) {
            $this->loadConversation($lastTargetId);
            $this->dispatch('conversation-selected', id: $lastTargetId);
        }

        $this->dispatch('toast', type: 'success', message: "{$msgCount} mensagem(ns) encaminhada(s) para {$totalTargets} destino(s).");
    }

    public function toggleTag(int $tagId): void
    {
        if (!$this->conversationId) return;
        $conv = $this->conversation;
        if ($conv->tags()->where('tags.id', $tagId)->exists()) {
            $conv->tags()->detach($tagId);
        } else {
            $conv->tags()->attach($tagId);
        }
        $this->conversation->refresh();
    }

    public function editContactName(string $name): void
    {
        $name = trim($name);
        if (!$this->conversationId || !$name) return;

        $contact = $this->conversation->contact;
        if (!$contact) return;

        $contact->update(['name' => $name]);

        // Sincronizar com leads
        \App\Models\BroadcastContact::where('phone', $contact->phone)->update(['name' => $name]);

        // Sincronizar título dos cards do CRM
        \App\Models\CrmCard::where('contact_id', $contact->id)->update(['title' => $name]);

        $this->conversation->refresh();
        $this->dispatch('toast', type: 'success', message: 'Nome atualizado.');
    }

    public function archiveConversation(): void
    {
        if (!$this->conversationId) return;
        $this->conversation->update(['is_archived' => true]);
        $this->conversationId = null;
        $this->conversation   = null;
        $this->dispatch('conversation-deleted');
        $this->dispatch('toast', type: 'success', message: 'Conversa arquivada.');
    }

    public function resolveConversation(): void
    {
        if (!$this->conversationId) return;

        $this->conversation->update(['status' => 'resolved', 'waiting_human_reason' => null]);

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

    public function openContactConversation(string $phone): void
    {
        if (!$phone) return;

        $phone = preg_replace('/\D/', '', $phone);

        // Busca contato pelo telefone
        $contact = Contact::where('phone', $phone)->first();

        if (!$contact) {
            $contact = Contact::create([
                'phone' => $phone,
                'name'  => $phone,
            ]);
        }

        // Busca conversa existente (aberta ou resolvida)
        $conv = Conversation::where('contact_id', $contact->id)
            ->where('is_group', false)
            ->whereIn('status', ['open', 'pending', 'resolved', 'transferred'])
            ->latest()
            ->first();

        if ($conv) {
            $this->dispatch('conversation-selected', id: $conv->id);
        } else {
            // Cria nova conversa
            $department = Department::active()->first();
            if (!$department) return;

            $conv = Conversation::create([
                'contact_id'    => $contact->id,
                'department_id' => $department->id,
                'status'        => 'open',
                'is_group'      => false,
            ]);
            $this->dispatch('conversation-selected', id: $conv->id);
        }
    }

    public function deleteConversation(): void
    {
        if (!$this->conversationId || !Auth::user()->canManageCompany()) return;

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
            $this->syncConversationTagFromPipeline($existing);
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
            $this->syncConversationTagFromPipeline($card);
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

        // Valida que o telefone é um número real (não um LID do WhatsApp)
        if (!$phone || !preg_match('/^55\d{10,11}$/', $phone)) {
            $this->dispatch('toast', type: 'error', message: 'Não foi possível identificar o número real deste membro. Pode ser um contato com identificador interno (LID) do WhatsApp.');
            return;
        }

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

        // Multi-número: dept destino usa instância diferente?
        $deptUsesOtherNumber = $department->evolution_api_config_id
            && $department->evolution_api_config_id !== $conv->evolution_api_config_id;

        if ($deptUsesOtherNumber) {
            // ── MULTI-NÚMERO: encerra conversa atual e cria/reabre na outra instância ──
            $conv->update(['status' => 'resolved', 'waiting_human_reason' => null]);

            Message::create([
                'conversation_id' => $conv->id,
                'sender_type'     => 'system',
                'content'         => "Conversa transferida para {$department->name} (outro número).",
                'type'            => 'text',
                'delivery_status' => 'sent',
            ]);

            // Busca conversa existente na outra instância ou cria nova
            $newConv = Conversation::where('contact_id', $conv->contact_id)
                ->where('evolution_api_config_id', $department->evolution_api_config_id)
                ->where('is_group', false)
                ->latest()
                ->first();

            if ($newConv) {
                $newConv->update([
                    'status'        => 'open',
                    'department_id' => $department->id,
                    'assigned_to'   => $this->transferAgent,
                    'waiting_human_reason' => null,
                ]);
            } else {
                $newConv = Conversation::create([
                    'contact_id'             => $conv->contact_id,
                    'department_id'          => $department->id,
                    'evolution_api_config_id' => $department->evolution_api_config_id,
                    'status'                 => 'open',
                    'assigned_to'            => $this->transferAgent,
                    'is_group'               => false,
                    'last_message_at'        => now(),
                ]);
            }

            // Mensagem de boas-vindas na nova conversa
            $companyName = app(\App\Services\CurrentCompany::class)->model()?->name ?? config('app.name');
            $welcomeMsg = Message::create([
                'conversation_id' => $newConv->id,
                'sender_type'     => 'agent',
                'sender_id'       => null,
                'content'         => "Olá! Sou do setor de *{$department->name}* da {$companyName}. Como posso ajudar? 😊",
                'type'            => 'text',
                'delivery_status' => 'pending',
            ]);
            $newConv->update(['last_message_at' => now()]);
            \App\Jobs\SendWhatsAppMessage::dispatch($welcomeMsg);

            $systemMsg = $targetAgent
                ? "Transferido de {$conv->department?->name} → {$department->name} ({$targetAgent->name}) [outro número]"
                : "Transferido de {$conv->department?->name} → {$department->name} [outro número]";
            if ($this->transferReason) $systemMsg .= "\nMotivo: {$this->transferReason}";
            Message::create([
                'conversation_id' => $newConv->id,
                'sender_type'     => 'system',
                'content'         => $systemMsg,
                'type'            => 'text',
                'delivery_status' => 'sent',
            ]);
        } else {
            // ── MESMO NÚMERO: transfere conversa normalmente ──
            $conv->update([
                'department_id' => $this->transferTo,
                'assigned_to'   => $this->transferAgent,
                'status'        => $this->transferAgent ? 'open' : 'transferred',
            ]);

            $systemMsg = $targetAgent
                ? "Conversa transferida para {$department->name} → {$targetAgent->name}."
                : "Conversa transferida para o departamento {$department->name}.";
            if ($this->transferReason) $systemMsg .= "\nMotivo: {$this->transferReason}";

            Message::create([
                'conversation_id' => $conv->id,
                'sender_type'     => 'system',
                'content'         => $systemMsg,
                'type'            => 'text',
                'delivery_status' => 'sent',
            ]);
        }

        $this->showTransfer   = false;
        $this->transferTo     = null;
        $this->transferAgent  = null;
        $this->transferReason = '';
        $this->conversationId = null;
        $this->conversation   = null;

        // Atualiza a lista de conversas pra refletir a transferência
        $this->dispatch('conversation-deleted');
    }

    private function compressVideo(string $inputPath): ?string
    {
        try {
            $outputPath = sys_get_temp_dir() . '/' . uniqid('vid_compressed_') . '.mp4';

            // Comprime: H.264, CRF 28 (boa qualidade, arquivo menor), max 720p, audio AAC 128k
            $cmd = sprintf(
                'ffmpeg -i %s -vcodec libx264 -crf 28 -preset fast -vf "scale=min(1280\\,iw):min(720\\,ih):force_original_aspect_ratio=decrease" -acodec aac -b:a 128k -movflags +faststart -y %s 2>&1',
                escapeshellarg($inputPath),
                escapeshellarg($outputPath)
            );

            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($outputPath)) {
                Log::warning('Compressão de vídeo falhou', ['code' => $returnCode, 'output' => implode("\n", array_slice($output, -5))]);
                @unlink($outputPath);
                return null;
            }

            $originalSize = filesize($inputPath);
            $compressedSize = filesize($outputPath);
            Log::info('Vídeo comprimido', [
                'original' => round($originalSize / 1024 / 1024, 1) . 'MB',
                'compressed' => round($compressedSize / 1024 / 1024, 1) . 'MB',
                'reduction' => round((1 - $compressedSize / $originalSize) * 100) . '%',
            ]);

            return $outputPath;
        } catch (\Throwable $e) {
            Log::warning('Compressão de vídeo: exceção', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function generateVideoThumbnail(string $videoPath): ?string
    {
        try {
            $thumbPath = sys_get_temp_dir() . '/' . uniqid('vid_thumb_') . '.jpg';
            $cmd = sprintf(
                'ffmpeg -i %s -ss 00:00:01 -vframes 1 -vf "scale=320:-1" -q:v 5 -y %s 2>&1',
                escapeshellarg($videoPath),
                escapeshellarg($thumbPath)
            );
            exec($cmd, $output, $returnCode);
            if ($returnCode === 0 && file_exists($thumbPath) && filesize($thumbPath) > 0) {
                return $thumbPath;
            }
            @unlink($thumbPath);
        } catch (\Throwable) {}
        return null;
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

        // Respostas rápidas — disponíveis tanto para WhatsApp quanto FlutChat
        if ($this->showQuickReplies && ($this->conversationId || $this->flutChatConvId)) {
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

        if ($this->conversationId) {
            $totalMessages = Message::where('conversation_id', $this->conversationId)->count();
            $this->hasOlderMessages = $totalMessages > $this->messageLimit;

            $messages = Message::where('conversation_id', $this->conversationId)
                ->with(['sender', 'replyTo'])
                ->orderBy('created_at', 'desc')
                ->take($this->messageLimit)
                ->get()
                ->reverse()
                ->values();

            if ($this->showTransfer) {
                $departments = Department::active()->get();

                if ($this->transferTo) {
                    $transferAgents = User::where('is_active', true)
                        ->where('company_id', app(\App\Services\CurrentCompany::class)->id())
                        ->byDepartment($this->transferTo)
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

        $contactList = collect();
        if ($this->showContactPicker) {
            $contactList = \App\Models\BroadcastContact::where('is_active', true)
                ->when($this->contactSearch, fn($q) =>
                    $q->where('name', 'like', "%{$this->contactSearch}%")
                      ->orWhere('phone', 'like', "%{$this->contactSearch}%")
                )->orderBy('name')->take(50)->get(['id', 'name', 'phone']);
        }

        $forwardConversations = collect();
        $internalAgents = collect();
        $internalGroups = collect();
        if ($this->showForwardPicker) {
            $forwardConversations = Conversation::with(['contact', 'department'])
                ->where('is_archived', false)
                ->where('id', '!=', $this->conversationId)
                ->when($this->forwardSearch, fn($q) =>
                    $q->whereHas('contact', fn($cq) =>
                        $cq->where('name', 'like', "%{$this->forwardSearch}%")
                            ->orWhere('phone', 'like', "%{$this->forwardSearch}%")
                    )
                )->latest('last_message_at')->take(50)->get();

            // Chat interno (agentes + grupos) — só se módulo ativo
            $company = app(\App\Services\CurrentCompany::class)->model();
            if ($company && $company->hasModule('internal-chat')) {
                $me = \Illuminate\Support\Facades\Auth::user();
                $internalAgents = \App\Models\User::where('company_id', $company->id)
                    ->where('id', '!=', $me->id)->where('is_active', true)
                    ->when($this->forwardSearch, fn($q) => $q->where('name', 'like', "%{$this->forwardSearch}%"))
                    ->orderBy('name')->get(['id', 'name', 'avatar']);
                $internalGroups = \App\Models\InternalGroup::whereHas('members', fn($q) => $q->where('user_id', $me->id))
                    ->when($this->forwardSearch, fn($q) => $q->where('name', 'like', "%{$this->forwardSearch}%"))
                    ->orderBy('name')->get();
            }
        }

        $flutChatMessages = collect();
        $flutChatConv = null;
        if ($this->flutChatConvId) {
            $flutChatConv = \App\Models\FlutChatConversation::with('widget')->find($this->flutChatConvId);
            $flutChatMessages = \App\Models\FlutChatMessage::where('conversation_id', $this->flutChatConvId)
                ->with('sender')->orderBy('id')->get();
        }

        return view('livewire.chat.chat-area', compact(
            'messages', 'quickReplies', 'departments', 'transferAgents',
            'crmPipelines', 'crmStages', 'crmCards', 'myReactionPhone', 'replyToMessage',
            'contactList', 'forwardConversations', 'internalAgents', 'internalGroups', 'flutChatMessages', 'flutChatConv'
        ));
    }
}
