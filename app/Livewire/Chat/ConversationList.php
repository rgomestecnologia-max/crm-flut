<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\Department;
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
    public bool   $showBulkTransfer = false;
    public ?int   $bulkTransferDept = null;

    public string $dddFilter = '';

    // Nova conversa
    public bool   $showNewConvModal = false;
    public string $newConvPhone = '';
    public string $newConvName = '';
    public ?int   $newConvDeptId = null;

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
        if (empty($this->selected) || !Auth::user()->canManageCompany()) return;

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

    public function resolveSelected(): void
    {
        if (empty($this->selected)) return;

        $count = 0;
        foreach ($this->selected as $id) {
            $conv = Conversation::find($id);
            if ($conv && $conv->status !== 'resolved') {
                $conv->update(['status' => 'resolved']);

                Message::create([
                    'conversation_id' => $conv->id,
                    'sender_type'     => 'system',
                    'content'         => 'Atendimento encerrado por ' . Auth::user()->name,
                    'type'            => 'text',
                    'delivery_status' => 'sent',
                ]);

                $count++;
            }
        }

        $this->selected   = [];
        $this->selectMode = false;

        if ($this->activeId) {
            $active = Conversation::find($this->activeId);
            if ($active && $active->status === 'resolved') {
                $this->activeId = null;
                $this->dispatch('conversation-deleted');
            }
        }

        $this->dispatch('toast', type: 'success', message: "{$count} conversa(s) resolvida(s).");
    }

    public function transferSelected(): void
    {
        if (empty($this->selected) || !$this->bulkTransferDept) return;

        $dept = Department::find($this->bulkTransferDept);
        if (!$dept) return;

        $user  = Auth::user();
        $count = 0;

        foreach ($this->selected as $id) {
            $conv = Conversation::find($id);
            if (!$conv || $conv->department_id === $dept->id) continue;

            TransferLog::create([
                'conversation_id'    => $conv->id,
                'from_department_id' => $conv->department_id,
                'to_department_id'   => $dept->id,
                'from_agent_id'      => $user->id,
                'to_agent_id'        => null,
            ]);

            $conv->update([
                'department_id' => $dept->id,
                'assigned_to'   => null,
                'status'        => 'transferred',
            ]);

            Message::create([
                'conversation_id' => $conv->id,
                'sender_type'     => 'system',
                'content'         => "Conversa transferida para o departamento {$dept->name} por {$user->name}.",
                'type'            => 'text',
                'delivery_status' => 'sent',
            ]);

            $count++;
        }

        $this->selected        = [];
        $this->selectMode      = false;
        $this->showBulkTransfer = false;
        $this->bulkTransferDept = null;

        if ($this->activeId) {
            $active = Conversation::find($this->activeId);
            if ($active && $active->department_id === $dept->id) {
                $this->activeId = null;
                $this->dispatch('conversation-deleted');
            }
        }

        $this->dispatch('toast', type: 'success', message: "{$count} conversa(s) transferida(s) para {$dept->name}.");
    }

    public function archiveConversation(int $id): void
    {
        $conv = Conversation::find($id);
        if (!$conv) return;
        $conv->update(['is_archived' => true]);
        if ($this->activeId === $id) {
            $this->activeId = null;
            $this->dispatch('conversation-deleted');
        }
        $this->dispatch('toast', type: 'success', message: 'Conversa arquivada.');
    }

    public function unarchiveConversation(int $id): void
    {
        $conv = Conversation::find($id);
        if (!$conv) return;
        $conv->update(['is_archived' => false]);
        $this->dispatch('toast', type: 'success', message: 'Conversa desarquivada.');
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
            'assigned_to'          => $user->id,
            'status'               => 'open',
            'waiting_human_reason' => null,
            'menu_awaiting'        => false,
        ]);

        // Troca pro filtro "Minhas Conversas" pra o agente ver a conversa assumida
        $this->filter   = 'mine';
        $this->activeId = $id;
        $this->dispatch('conversation-selected', id: $id);
        $this->dispatch('toast', type: 'success', message: 'Conversa atribuída a você.');
    }

    public function startNewConversation(): void
    {
        $this->validate([
            'newConvPhone' => 'required|string|min:8',
        ]);

        $user = Auth::user();
        $phone = preg_replace('/\D/', '', $this->newConvPhone);
        if (strlen($phone) <= 11 && !str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }

        // Busca ou cria contato
        $contact = \App\Models\Contact::where('phone', $phone)->first();
        if (!$contact) {
            $contact = \App\Models\Contact::create([
                'phone' => $phone,
                'name'  => $this->newConvName ?: null,
            ]);
            // Cadastra também como lead
            \App\Models\BroadcastContact::firstOrCreate(
                ['phone' => $phone],
                ['name' => $this->newConvName ?: null, 'tags' => ['manual'], 'is_active' => true]
            );
        } elseif ($this->newConvName && !$contact->name) {
            $contact->update(['name' => $this->newConvName]);
        }

        // Departamento selecionado ou primeiro do agente
        $deptId = $this->newConvDeptId;
        if (!$deptId) {
            $deptId = $user->departments->first()?->id ?? Department::active()->first()?->id;
        }
        $dept = Department::find($deptId);

        // Determina evolution_api_config_id
        $evoConfigId = $dept?->evolution_api_config_id ?? \App\Models\EvolutionApiConfig::first()?->id;

        // Busca conversa existente para esse contato (qualquer status, incluindo resolvida)
        $existingConv = Conversation::where('contact_id', $contact->id)
            ->where('is_group', false)
            ->when($evoConfigId, fn($q) => $q->where('evolution_api_config_id', $evoConfigId))
            ->latest()
            ->first();

        if ($existingConv) {
            // Reabre se estava resolvida
            if ($existingConv->status === 'resolved') {
                $existingConv->update([
                    'status'               => 'open',
                    'assigned_to'          => $user->id,
                    'waiting_human_reason' => null,
                ]);
            }
            $this->filter = 'mine';
            $this->activeId = $existingConv->id;
            $this->dispatch('conversation-selected', id: $existingConv->id);
            $this->dispatch('toast', type: 'info', message: 'Conversa existente selecionada.');
        } else {
            $conv = Conversation::create([
                'contact_id'             => $contact->id,
                'department_id'          => $deptId,
                'evolution_api_config_id' => $evoConfigId,
                'status'                 => 'open',
                'assigned_to'            => $user->id,
                'is_group'               => false,
                'last_message_at'        => now(),
            ]);

            $this->filter = 'mine';
            $this->activeId = $conv->id;
            $this->dispatch('conversation-selected', id: $conv->id);
            $this->dispatch('toast', type: 'success', message: 'Nova conversa criada.');
        }

        $this->showNewConvModal = false;
        $this->newConvPhone = '';
        $this->newConvName = '';
        $this->newConvDeptId = null;
    }

    public function render()
    {
        $user  = Auth::user();
        $query = Conversation::with(['contact.crmCards.stage.pipeline', 'department', 'assignedAgent', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn($q) => $q->where('sender_type', 'contact')->where('is_read', false)])
            ->forUser($user)
            ->latest('last_message_at');

        match ($this->filter) {
            // Meus atendimentos: atribuídas a mim, status open (não arquivadas)
            'mine'     => $query->where('is_archived', false)->where('assigned_to', $user->id)->where('status', 'open'),

            // Fila: conversas sem agente (exclui arquivadas, Aguardando, depts ocultos e grupos)
            'queue'    => $query->where('is_archived', false)->whereNull('waiting_human_reason')
                ->where('is_group', false)
                ->whereDoesntHave('department', fn($q) => $q->where('hide_from_main_queue', true))
                ->whereNull('assigned_to')->whereIn('status', ['open', 'pending', 'transferred']),

            // Aguardando: conversas onde a IA pediu handoff (não arquivadas)
            'waiting'  => $query->where('is_archived', false)->whereNotNull('waiting_human_reason'),

            // Todos: todas não arquivadas
            'all'      => $query->where('is_archived', false),

            // Arquivadas
            'archived' => $query->where('is_archived', true),

            // Messenger
            'messenger' => $query->where('is_archived', false)->where('channel', 'messenger'),

            // Instagram
            'instagram' => $query->where('is_archived', false)->where('channel', 'instagram'),

            // Grupos
            'groups' => $query->where('is_archived', false)->where('is_group', true)->whereIn('status', ['open', 'pending']),

            // Não lidas
            'unread' => $query->where('is_archived', false)
                ->whereHas('messages', fn($q) => $q->where('sender_type', 'contact')->where('is_read', false)),

            default    => null,
        };

        // Filtro por departamento (ex: 'queue_9' mostra todas conversas ativas do dept 9)
        if (str_starts_with($this->filter, 'queue_')) {
            $deptId = (int) substr($this->filter, 6);
            $query->where('is_archived', false)
                ->where('is_group', false)
                ->where('department_id', $deptId)
                ->whereIn('status', ['open', 'pending', 'transferred']);
        }

        // Filtro por tag (ex: 'tag_5' filtra pela tag ID 5)
        if (str_starts_with($this->filter, 'tag_')) {
            $tagId = (int) substr($this->filter, 4);
            $query->whereHas('tags', fn($q) => $q->where('tags.id', $tagId));
        }

        // Filtro por DDD
        if ($this->dddFilter) {
            $ddd = $this->dddFilter;
            $query->whereHas('contact', fn($q) => $q->where('phone', 'like', "55{$ddd}%"));
        }

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('contact', fn($cq) => $cq->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"))
                  ->orWhereHas('messages', fn($mq) => $mq->where('content', 'like', "%{$search}%"));
            });
        }

        $conversations = $query->take($this->perPage)->get();
        $this->hasMore = $conversations->count() >= $this->perPage;

        $baseQuery = Conversation::forUser($user);

        $counts = [
            'mine'     => (clone $baseQuery)->where('is_archived', false)->where('assigned_to', $user->id)->where('status', 'open')->count(),
            'queue'    => (clone $baseQuery)->where('is_archived', false)->whereNull('waiting_human_reason')
                ->where('is_group', false)
                ->whereDoesntHave('department', fn($q) => $q->where('hide_from_main_queue', true))
                ->whereNull('assigned_to')->whereIn('status', ['open', 'pending', 'transferred'])
                ->count(),
            'waiting'  => (clone $baseQuery)->where('is_archived', false)->whereNotNull('waiting_human_reason')->count(),
            'all'       => (clone $baseQuery)->where('is_archived', false)->count(),
            'archived'  => (clone $baseQuery)->where('is_archived', true)->count(),
            'messenger' => (clone $baseQuery)->where('is_archived', false)->where('channel', 'messenger')->count(),
            'instagram' => (clone $baseQuery)->where('is_archived', false)->where('channel', 'instagram')->count(),
            'groups'    => (clone $baseQuery)->where('is_archived', false)->where('is_group', true)->whereIn('status', ['open', 'pending'])->count(),
            'unread'    => (clone $baseQuery)->where('is_archived', false)
                ->whereHas('messages', fn($q) => $q->where('sender_type', 'contact')->where('is_read', false))->count(),
        ];

        $departments = Department::active()->orderBy('sort_order')->orderBy('name')->get();

        // Filas por departamento para supervisores/admins com múltiplos departamentos
        $deptQueueCounts = [];
        $currentCompanyId = app(\App\Services\CurrentCompany::class)->id();
        $isAdminViewingOtherCompany = $user->isAdmin() && $user->company_id !== $currentCompanyId;
        $userDeptIds = $user->departmentIds();
        // Admin visualizando outra empresa ou sem dept → usa todos os departamentos da empresa atual
        if ($user->isAdmin() && ($isAdminViewingOtherCompany || empty($userDeptIds))) {
            $userDeptIds = Department::active()->pluck('id')->all();
        }
        // Mostra abas por departamento quando: empresa tem multi-instância OU múltiplos depts
        $hasMultiInstance = Department::active()->whereNotNull('evolution_api_config_id')->count() > 0;
        $showDeptQueues = $hasMultiInstance || (($user->isSupervisor() || $user->isAdmin()) && count($userDeptIds) > 1);
        if ($showDeptQueues) {
            foreach ($userDeptIds as $deptId) {
                $deptQueueCounts[$deptId] = (clone $baseQuery)
                    ->where('is_archived', false)
                    ->where('is_group', false)
                    ->where('department_id', $deptId)
                    ->whereIn('status', ['open', 'pending', 'transferred'])
                    ->count();
            }
        }

        // Tags da empresa para filtros na sidebar
        $tags = \App\Models\Tag::orderBy('name')->get();
        $tagCounts = [];
        foreach ($tags as $tag) {
            $tagCounts[$tag->id] = (clone $baseQuery)
                ->whereHas('tags', fn($q) => $q->where('tags.id', $tag->id))
                ->count();
        }

        return view('livewire.chat.conversation-list', compact('conversations', 'counts', 'departments', 'tags', 'tagCounts', 'showDeptQueues', 'deptQueueCounts'));
    }
}
