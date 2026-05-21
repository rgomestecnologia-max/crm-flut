<?php

namespace App\Livewire\InternalChat;

use App\Events\InternalMessageSent;
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
    public string $messageText    = '';
    public        $attachment     = null;

    protected function getListeners(): array
    {
        return [
            "echo-private:internal-chat." . Auth::id() . ",.internal.message" => '$refresh',
        ];
    }

    public function selectUser(int $userId): void
    {
        $this->selectedUserId = $userId;
        $this->messageText    = '';

        // Marcar como lidas
        InternalMessage::where('sender_id', $userId)
            ->where('recipient_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function sendMessage(): void
    {
        if (!$this->selectedUserId || !trim($this->messageText)) return;

        $msg = InternalMessage::create([
            'sender_id'    => Auth::id(),
            'recipient_id' => $this->selectedUserId,
            'content'      => trim($this->messageText),
            'type'         => 'text',
        ]);

        try { broadcast(new InternalMessageSent($msg)); } catch (\Throwable) {}

        $this->messageText = '';
        $this->dispatch('internal-scroll-bottom');
    }

    public function sendFile(): void
    {
        if (!$this->selectedUserId || !$this->attachment) return;

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
            'content'        => $name,
            'type'           => $type,
            'media_url'      => $url,
            'media_filename' => $name,
        ]);

        try { broadcast(new InternalMessageSent($msg)); } catch (\Throwable) {}

        $this->attachment = null;
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
                ->orderBy('created_at')
                ->get();
        }

        $selectedUser = $this->selectedUserId ? User::find($this->selectedUserId) : null;
        $totalUnread  = InternalMessage::where('recipient_id', $me->id)->where('is_read', false)->count();

        return view('livewire.internal-chat.internal-chat', compact('agents', 'messages', 'selectedUser', 'totalUnread'));
    }
}
