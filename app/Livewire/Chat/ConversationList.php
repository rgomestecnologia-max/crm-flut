<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\TransferLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ConversationList extends Component
{
    public string $filter      = 'mine';
    public string $search      = '';
    public ?int   $activeId    = null;
    public bool   $selectMode  = false;
    public array  $selected    = [];
    public int    $perPage     = 30;
    public bool   $hasMore     = true;

    public function mount(?int $activeId = null): void
    {
        $this->activeId = $activeId;
    }

    protected function getListeners(): array
    {
        $listeners = [
            'conversation-selected'  => 'setActive',
            'conversation-deleted'   => '$refresh',
        ];

        $user    = Auth::user();
        $deptIds = $user ? $user->departmentIds() : [];

        if (!empty($deptIds)) {
            // Um listener por departamento — agente em N setores recebe broadcasts de todos.
            foreach ($deptIds as $deptId) {
                $listeners["echo-private:department.{$deptId},message.received"] = '$refresh';
            }
        } else {
            $listeners['message-received-any'] = '$refresh';
        }

        return $listeners;
    }

    public function setActive(int $id): void
    {
        $this->activeId = $id;
    }

    public function selectConversation(int $id): void
    {
        if ($this->selectMode) {
            $this->toggleSelect($id);
            return;
        }
        $this->activeId = $id;
        $this->dispatch('conversation-selected', id: $id);
    }

    public function setFilter(string $filter): void
    {
        $this->filter   = $filter;
        $this->selected = [];
        $this->perPage  = 30;
        $this->hasMore  = true;
    }

    public function updatedSearch(): void
    {
        $this->perPage = 30;
        $this->hasMore = true;
    }

    public function loadMore(): void
    {
        $this->perPage += 30;
    }

    public function toggleSelectMode(): void
    {
        $this->selectMode = !$this->selectMode;
        $this->selected   = [];
    }

    public function toggleSelect(int $id): void
    {
        if (in_array($id, $this->selected)) {
            $this->selected = array_values(array_diff($this->selected, [$id]));
        } else {
            $this->selected[] = $id;
        }
    }

    public function selectAll(array $ids): void
    {
        $this->selected = $ids;
    }

    public function deselectAll(): void
    {
        $this->selected = [];
    }

    public function deleteSelected(): void
    {
        if (empty($this->selected) || !Auth::user()->isAdmin()) return;

        foreach ($this->selected as $id) {
            Message::where('conversation_id', $id)->delete();
            TransferLog::where('conversation_id', $id)->delete();
            Conversation::find($id)?->delete();
        }

        $count = count($this->selected);
        $this->selected   = [];
        $this->selectMode = false;

        if ($this->activeId && !Conversation::find($this->activeId)) {
            $this->activeId = null;
            $this->dispatch('conversation-deleted');
        }

        $this->dispatch('toast', type: 'success', message: "{$count} conversa(s) excluída(s).");
    }

    /**
     * Atribui a conversa ao agente logado (tirar da fila).
     */
    public function assignToMe(int $id): void
    {
        $user = Auth::user();
        $conv = Conversation::forUser($user)->find($id);
        if (!$conv) return;

        $conv->update([
            'assigned_to' => $user->id,
            'status'      => 'open',
        ]);

        // Troca pro filtro "Minhas Conversas" pra o agente ver a conversa assumida
        $this->filter   = 'mine';
        $this->activeId = $id;
        $this->dispatch('conversation-selected', id: $id);
        $this->dispatch('toast', type: 'success', message: 'Conversa atribuída a você.');
    }

    public function render()
    {
        $user  = Auth::user();
        $query = Conversation::with(['contact.crmCards.stage.pipeline', 'department', 'assignedAgent', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn($q) => $q->where('sender_type', 'contact')->where('is_read', false)])
            ->forUser($user)
            ->latest('last_message_at');

        match ($this->filter) {
            // Meus atendimentos: atribuídas a mim, status open
            'mine'     => $query->where('assigned_to', $user->id)->where('status', 'open'),

            // Fila: conversas sem agente + grupos do setor (grupos sempre ficam visíveis na fila)
            'queue'    => $query->where(function ($q) {
                $q->where(function ($q2) {
                    // Conversas individuais sem agente
                    $q2->whereNull('assigned_to')->whereIn('status', ['open', 'pending', 'transferred']);
                })->orWhere(function ($q2) {
                    // Grupos sempre aparecem na fila do setor
                    $q2->where('is_group', true)->whereIn('status', ['open', 'pending']);
                });
            }),

            // Resolvidos
            'resolved' => $query->where('status', 'resolved'),

            // Todos: só admin + supervisor
            'all'      => $user->canManageCompany() ? $query : $query->whereRaw('1 = 0'),

            default    => null,
        };

        if ($this->search) {
            $query->whereHas('contact', fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%"));
        }

        $conversations = $query->take($this->perPage)->get();
        $this->hasMore = $conversations->count() >= $this->perPage;

        $baseQuery = Conversation::forUser($user);

        $counts = [
            'mine'     => (clone $baseQuery)->where('assigned_to', $user->id)->where('status', 'open')->count(),
            'queue'    => (clone $baseQuery)->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('assigned_to')->whereIn('status', ['open', 'pending', 'transferred']);
                })->orWhere(function ($q2) {
                    $q2->where('is_group', true)->whereIn('status', ['open', 'pending']);
                });
            })->count(),
            'resolved' => (clone $baseQuery)->where('status', 'resolved')->count(),
            'all'      => $user->canManageCompany() ? (clone $baseQuery)->count() : null,
        ];

        return view('livewire.chat.conversation-list', compact('conversations', 'counts'));
    }
}
