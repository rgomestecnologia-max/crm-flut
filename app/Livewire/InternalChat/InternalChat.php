<?php

namespace App\Livewire\InternalChat;

use App\Events\InternalMessageSent;
use App\Models\InternalGroup;
use App\Models\InternalMessage;
use App\Models\User;
use App\Services\MediaStorage;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class InternalChat extends Component
{
    use WithFileUploads;

    public ?int   $selectedUserId = null;
    public ?int   $selectedGroupId = null;
    public string $messageText    = '';
    public        $attachment     = null;

    // Criar grupo
    public bool   $showGroupModal = false;
    public string $groupName = '';
    public array  $groupMemberIds = [];

    protected function getListeners(): array
    {
        return [
            "echo-private:internal-chat." . Auth::id() . ",.internal.message" => '$refresh',
        ];
    }

    public function selectUser(int $userId): void
    {
        $this->selectedUserId = $userId;
        $this->selectedGroupId = null;
        $this->messageText    = '';

        InternalMessage::where('sender_id', $userId)
            ->where('recipient_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function selectGroup(int $groupId): void
    {
        $this->selectedGroupId = $groupId;
        $this->selectedUserId = null;
        $this->messageText = '';

        // Marcar msgs do grupo como lidas
        InternalMessage::where('group_id', $groupId)
            ->where('sender_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function createGroup(): void
    {
        if (!trim($this->groupName) || empty($this->groupMemberIds)) return;

        $group = InternalGroup::create([
            'name'       => trim($this->groupName),
            'created_by' => Auth::id(),
        ]);

        $memberIds = array_unique(array_merge($this->groupMemberIds, [Auth::id()]));
        $group->members()->attach($memberIds);

        $this->showGroupModal = false;
        $this->groupName = '';
        $this->groupMemberIds = [];
        $this->selectedGroupId = $group->id;
        $this->selectedUserId = null;
        $this->dispatch('toast', type: 'success', message: 'Grupo criado.');
    }

    public function deleteGroup(int $groupId): void
    {
        $group = InternalGroup::find($groupId);
        if (!$group) return;
        $group->delete();
        if ($this->selectedGroupId === $groupId) {
            $this->selectedGroupId = null;
        }
        $this->dispatch('toast', type: 'success', message: 'Grupo excluído.');
    }

    public function toggleGroupMember(int $userId): void
    {
        if (in_array($userId, $this->groupMemberIds)) {
            $this->groupMemberIds = array_values(array_diff($this->groupMemberIds, [$userId]));
        } else {
            $this->groupMemberIds[] = $userId;
        }
    }

    public function sendMessage(): void
    {
        if ((!$this->selectedUserId && !$this->selectedGroupId) || !trim($this->messageText)) return;

        $msg = InternalMessage::create([
            'sender_id'    => Auth::id(),
            'recipient_id' => $this->selectedUserId,
            'group_id'     => $this->selectedGroupId,
            'content'      => trim($this->messageText),
            'type'         => 'text',
        ]);

        try { broadcast(new InternalMessageSent($msg)); } catch (\Throwable) {}

        $this->messageText = '';
        $this->dispatch('internal-scroll-bottom');
    }

    public function sendFile(): void
    {
        if ((!$this->selectedUserId && !$this->selectedGroupId) || !$this->attachment) return;

        $this->validate(['attachment' => 'required|file|max:10240']);

        $file = $this->attachment;
        $mime = $file->getMimeType() ?? 'application/octet-stream';
        $name = $file->getClientOriginalName();

        $type = match(true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'audio/') => 'audio',
            default                          => 'document',
        };

        $path = MediaStorage::store($file, 'internal-chat/' . date('Y/m'));
        $url  = MediaStorage::url($path);

        $msg = InternalMessage::create([
            'sender_id'      => Auth::id(),
            'recipient_id'   => $this->selectedUserId,
            'group_id'       => $this->selectedGroupId,
            'content'        => $name,
            'type'           => $type,
            'media_url'      => $url,
            'media_filename' => $name,
        ]);

        try { broadcast(new InternalMessageSent($msg)); } catch (\Throwable) {}

        $this->attachment = null;
        $this->dispatch('internal-scroll-bottom');
    }

    public function cancelFile(): void
    {
        $this->attachment = null;
    }

    public function editInternalMessage(int $messageId, string $newText): void
    {
        if (!trim($newText)) return;
        $msg = InternalMessage::where('sender_id', Auth::id())->find($messageId);
        if ($msg) {
            $msg->update(['content' => trim($newText)]);
        }
    }

    public function deleteInternalMessage(int $messageId): void
    {
        $msg = InternalMessage::where('sender_id', Auth::id())->find($messageId);
        if ($msg) {
            $msg->delete();
        }
    }

    public function receiveAudioBlob(string $dataUrl): void
    {
        if (!$this->selectedUserId && !$this->selectedGroupId) return;

        $parts = explode(',', $dataUrl, 2);
        if (count($parts) < 2) return;
        [$header, $base64] = $parts;
        $raw = base64_decode($base64);
        if (!$raw || strlen($raw) < 100) return;

        $mime = str_contains($header, 'audio/ogg') ? 'ogg'
             : (str_contains($header, 'audio/mp4') ? 'mp4' : 'webm');

        $dir  = 'internal-chat/' . date('Y/m');
        $name = uniqid('audio_', true) . '.' . $mime;
        $path = "{$dir}/{$name}";

        \App\Services\MediaStorage::put($path, $raw);
        $url = \App\Services\MediaStorage::url($path);

        $msg = InternalMessage::create([
            'sender_id'      => Auth::id(),
            'recipient_id'   => $this->selectedUserId,
            'group_id'       => $this->selectedGroupId,
            'content'        => null,
            'type'           => 'audio',
            'media_url'      => $url,
            'media_filename' => $name,
        ]);

        try { broadcast(new InternalMessageSent($msg)); } catch (\Throwable) {}
        $this->dispatch('internal-scroll-bottom');
    }

    public function render()
    {
        $me    = Auth::user();
        $companyId = app(\App\Services\CurrentCompany::class)->id();

        // Agentes da mesma empresa (exceto eu)
        $agents = User::where('company_id', $companyId)
            ->where('id', '!=', $me->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($agent) use ($me) {
                $agent->unread_count = InternalMessage::where('sender_id', $agent->id)
                    ->where('recipient_id', $me->id)
                    ->where('is_read', false)
                    ->count();
                $agent->last_internal_msg = InternalMessage::where(function ($q) use ($agent, $me) {
                        $q->where(function ($q2) use ($agent, $me) {
                            $q2->where('sender_id', $me->id)->where('recipient_id', $agent->id);
                        })->orWhere(function ($q2) use ($agent, $me) {
                            $q2->where('sender_id', $agent->id)->where('recipient_id', $me->id);
                        });
                    })->latest()->first();
                return $agent;
            })
            ->sortByDesc(fn($a) => $a->last_internal_msg?->created_at ?? '0');

        // Grupos do usuário
        $groups = InternalGroup::whereHas('members', fn($q) => $q->where('user_id', $me->id))
            ->with('latestMessage')
            ->get()
            ->map(function ($group) use ($me) {
                $group->unread_count = InternalMessage::where('group_id', $group->id)
                    ->where('sender_id', '!=', $me->id)
                    ->where('is_read', false)
                    ->count();
                return $group;
            })
            ->sortByDesc(fn($g) => $g->latestMessage?->created_at ?? $g->created_at);

        // Mensagens da conversa selecionada
        $messages = collect();
        if ($this->selectedUserId) {
            $messages = InternalMessage::where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('sender_id', Auth::id())->where('recipient_id', $this->selectedUserId);
                    })->orWhere(function ($q2) {
                        $q2->where('sender_id', $this->selectedUserId)->where('recipient_id', Auth::id());
                    });
                })
                ->whereNull('group_id')
                ->orderBy('created_at')
                ->get();
        } elseif ($this->selectedGroupId) {
            $messages = InternalMessage::with('sender')
                ->where('group_id', $this->selectedGroupId)
                ->orderBy('created_at')
                ->get();
        }

        $selectedUser  = $this->selectedUserId ? User::find($this->selectedUserId) : null;
        $selectedGroup = $this->selectedGroupId ? InternalGroup::with('members')->find($this->selectedGroupId) : null;
        $totalUnread   = InternalMessage::where('recipient_id', $me->id)->where('is_read', false)->count();

        return view('livewire.internal-chat.internal-chat', compact('agents', 'groups', 'messages', 'selectedUser', 'selectedGroup', 'totalUnread'));
    }
}
