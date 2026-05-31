<?php

namespace App\Livewire\Chat;

use App\Models\FlutChatConversation;
use App\Models\FlutChatMessage;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FlutChatLive extends Component
{
    public ?int $activeConversationId = null;
    public string $replyText = '';

    public function selectConversation(int $id): void
    {
        $this->activeConversationId = $id;
        $this->replyText = '';
    }

    public function sendReply(): void
    {
        if (!$this->activeConversationId || !trim($this->replyText)) return;

        $conv = FlutChatConversation::find($this->activeConversationId);
        if (!$conv) return;

        FlutChatMessage::create([
            'conversation_id' => $conv->id,
            'sender_type'     => 'agent',
            'sender_id'       => Auth::id(),
            'content'         => trim($this->replyText),
        ]);

        $conv->update([
            'last_message_at' => now(),
            'assigned_to'     => $conv->assigned_to ?? Auth::id(),
        ]);

        $this->replyText = '';
    }

    public function closeConversation(int $id): void
    {
        FlutChatConversation::find($id)?->update(['status' => 'closed']);
        if ($this->activeConversationId === $id) {
            $this->activeConversationId = null;
        }
        $this->dispatch('toast', type: 'success', message: 'Conversa encerrada.');
    }

    public function render()
    {
        $conversations = FlutChatConversation::with(['widget', 'latestMessage', 'assignedAgent'])
            ->where('status', 'active')
            ->latest('last_message_at')
            ->get();

        $messages = collect();
        if ($this->activeConversationId) {
            $messages = FlutChatMessage::where('conversation_id', $this->activeConversationId)
                ->with('sender')
                ->orderBy('id')
                ->get();
        }

        return view('livewire.chat.flut-chat-live', compact('conversations', 'messages'));
    }
}
