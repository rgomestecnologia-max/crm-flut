<div style="display:flex; flex-direction:column; height:100vh;" wire:poll.5s>

    {{-- Header --}}
    <div style="height:64px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; padding:0 16px; gap:10px; flex-shrink:0; background:rgba(11,15,28,0.6); backdrop-filter:blur(8px);">
        <div style="flex:1; min-width:0;">
            <h2 style="font-size:13px; font-weight:700; color:white; letter-spacing:-0.01em;">Conversas</h2>
            <p style="font-size:10px; color:rgba(255,255,255,0.25); margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                @if(auth()->user()->department)
                    {{ auth()->user()->department->name }}
                @else
                    Todos os departamentos
                @endif
            </p>
        </div>

        {{-- Botão selecionar (admin + supervisor) --}}
        @if(auth()->user()->canManageCompany())
        <button wire:click="toggleSelectMode"
                title="{{ $selectMode ? 'Cancelar seleção' : 'Selecionar conversas' }}"
                style="width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; transition:all 0.2s; border:none; cursor:pointer;
                       {{ $selectMode ? 'background:rgba(239,68,68,0.15); color:#f87171;' : 'background:rgba(255,255,255,0.04); color:rgba(255,255,255,0.3);' }}">
            @if($selectMode)
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            @else
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            @endif
        </button>
        @endif
    </div>

    {{-- Barra de ações em modo seleção --}}
    @if($selectMode)
    <div style="padding:8px 12px; background:rgba(239,68,68,0.06); border-bottom:1px solid rgba(239,68,68,0.15); display:flex; align-items:center; gap:8px; flex-shrink:0;">
        <span style="font-size:11px; color:#f87171; flex:1;">{{ count($selected) }} selecionada(s)</span>
        <button wire:click="selectAll({{ json_encode($conversations->pluck('id')->toArray()) }})"
                style="font-size:11px; color:rgba(255,255,255,0.4); padding:4px 8px; border-radius:6px; border:none; background:transparent; cursor:pointer; transition:all 0.15s;"
                onmouseover="this.style.background='rgba(255,255,255,0.06)'; this.style.color='white'"
                onmouseout="this.style.background='transparent'; this.style.color='rgba(255,255,255,0.4)'">
            Todas
        </button>
        <button wire:click="deselectAll"
                style="font-size:11px; color:rgba(255,255,255,0.4); padding:4px 8px; border-radius:6px; border:none; background:transparent; cursor:pointer; transition:all 0.15s;"
                onmouseover="this.style.background='rgba(255,255,255,0.06)'; this.style.color='white'"
                onmouseout="this.style.background='transparent'; this.style.color='rgba(255,255,255,0.4)'">
            Limpar
        </button>
        @if(count($selected) > 0)
        <button wire:click="$toggle('showBulkTransfer')"
                style="display:flex; align-items:center; gap:5px; font-size:11px; font-weight:600; background:#3b82f6; color:white; padding:5px 10px; border-radius:7px; border:none; cursor:pointer; transition:all 0.15s;"
                onmouseover="this.style.background='#2563eb'"
                onmouseout="this.style.background='#3b82f6'">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            Transferir
        </button>
        <button wire:click="resolveSelected"
                wire:confirm="Resolver {{ count($selected) }} conversa(s)? Elas serão movidas para Resolvidos."
                style="display:flex; align-items:center; gap:5px; font-size:11px; font-weight:600; background:#22c55e; color:white; padding:5px 10px; border-radius:7px; border:none; cursor:pointer; transition:all 0.15s;"
                onmouseover="this.style.background='#16a34a'"
                onmouseout="this.style.background='#22c55e'">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Resolver
        </button>
        <button x-on:click="if(confirm('Excluir {{ count($selected) }} conversa(s) e todas as mensagens?')) { $wire.deleteSelected() }"
                style="display:flex; align-items:center; gap:5px; font-size:11px; font-weight:600; background:#ef4444; color:white; padding:5px 10px; border-radius:7px; border:none; cursor:pointer; transition:all 0.15s;"
                onmouseover="this.style.background='#dc2626'"
                onmouseout="this.style.background='#ef4444'">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Excluir
        </button>
        @endif
    </div>

    {{-- Painel de transferência em lote --}}
    @if($showBulkTransfer && count($selected) > 0)
    <div style="padding:10px 12px; background:rgba(59,130,246,0.06); border-bottom:1px solid rgba(59,130,246,0.15); flex-shrink:0;">
        <p style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;">Transferir {{ count($selected) }} conversa(s) para:</p>
        <div style="display:flex; align-items:center; gap:8px;">
            <select wire:model="bulkTransferDept"
                    style="flex:1; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:7px 10px; font-size:12px; color:white; outline:none; cursor:pointer;">
                <option value="">Selecione o departamento</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
            <button wire:click="transferSelected"
                    @if(!$bulkTransferDept) disabled @endif
                    style="display:flex; align-items:center; gap:5px; font-size:11px; font-weight:600; padding:7px 14px; border-radius:8px; border:none; cursor:pointer; transition:all 0.15s;
                           {{ $bulkTransferDept ? 'background:#3b82f6; color:white;' : 'background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.2); cursor:not-allowed;' }}">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Confirmar
            </button>
            <button wire:click="$set('showBulkTransfer', false)"
                    style="font-size:11px; color:rgba(255,255,255,0.4); padding:5px 8px; border-radius:6px; border:none; background:transparent; cursor:pointer;"
                    onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.4)'">
                Cancelar
            </button>
        </div>
    </div>
    @endif
    @endif

    {{-- Search --}}
    <div style="padding:10px 12px; border-bottom:1px solid rgba(255,255,255,0.04); flex-shrink:0;">
        <div style="position:relative;">
            <svg style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:rgba(255,255,255,0.2); pointer-events:none;" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input wire:model.live.debounce.300ms="search"
                   type="text"
                   placeholder="Buscar contato..."
                   style="width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:8px 12px 8px 32px; font-size:12px; color:white; outline:none; transition:all 0.2s; font-family:inherit; box-sizing:border-box;"
                   onfocus="this.style.borderColor='rgba(178,255,0,0.4)'; this.style.background='rgba(178,255,0,0.04)'; this.style.boxShadow='0 0 0 3px rgba(178,255,0,0.06)'"
                   onblur="this.style.borderColor='rgba(255,255,255,0.07)'; this.style.background='rgba(255,255,255,0.04)'; this.style.boxShadow='none'"
                   >
        </div>
    </div>

    {{-- Filter tabs --}}
    <div style="display:flex; padding:8px 10px; gap:4px; border-bottom:1px solid rgba(255,255,255,0.04); flex-shrink:0; overflow-x:auto;">
        @php
        $aiActive = \App\Models\AiBotConfig::current()?->is_active ?? false;
        $tabs = [
            ['key' => 'mine',     'label' => 'Minhas Conversas', 'count' => $counts['mine'],    'color' => '#b2ff00', 'activeBg' => 'rgba(178,255,0,0.12)', 'activeColor' => '#b2ff00'],
            ['key' => 'queue',    'label' => 'Fila',      'count' => $counts['queue'],   'color' => '#f59e0b', 'activeBg' => 'rgba(245,158,11,0.12)',  'activeColor' => '#fbbf24'],
        ];
        if ($aiActive) {
            $tabs[] = ['key' => 'waiting', 'label' => 'Aguardando', 'count' => $counts['waiting'] ?? 0, 'color' => '#ef4444', 'activeBg' => 'rgba(239,68,68,0.12)', 'activeColor' => '#f87171'];
        }
        $tabs[] = ['key' => 'all', 'label' => 'Todos', 'count' => $counts['all'], 'color' => '#6b7280', 'activeBg' => 'rgba(255,255,255,0.08)', 'activeColor' => 'white'];
        @endphp
        @foreach($tabs as $tab)
        <button wire:click="setFilter('{{ $tab['key'] }}')"
                style="flex-shrink:0; display:flex; align-items:center; gap:5px; padding:5px 9px; border-radius:7px; font-size:11px; font-weight:{{ $filter === $tab['key'] ? '600' : '400' }}; border:none; cursor:pointer; transition:all 0.15s; white-space:nowrap;
                       background:{{ $filter === $tab['key'] ? $tab['activeBg'] : 'transparent' }};
                       color:{{ $filter === $tab['key'] ? $tab['activeColor'] : 'rgba(255,255,255,0.3)' }};">
            {{ $tab['label'] }}
            @if($tab['count'] !== null && $tab['count'] > 0)
                <span style="display:inline-flex; align-items:center; justify-content:center; min-width:16px; height:16px; padding:0 4px; border-radius:20px; font-size:9px; font-weight:700; background:{{ $tab['color'] }}; color:white; line-height:1;">
                    {{ $tab['count'] }}
                </span>
            @endif
        </button>
        @endforeach
    </div>

    {{-- List --}}
    <div style="flex:1; overflow-y:auto; max-height:calc(100vh - 180px);">
        @forelse($conversations as $conv)
            @php $isSelected = in_array($conv->id, $selected); @endphp
            <div wire:key="conv-{{ $conv->id }}"
                 style="position:relative; border-bottom:1px solid rgba(255,255,255,0.03); transition:background 0.15s;
                        {{ $isSelected ? 'background:rgba(239,68,68,0.06);' : '' }}
                        {{ $activeId === $conv->id && !$selectMode ? 'background:rgba(178,255,0,0.05); border-left:2px solid #b2ff00;' : '' }}"
                 @if(!$isSelected && $activeId !== $conv->id)
                 onmouseover="this.style.background='rgba(255,255,255,0.02)'"
                 onmouseout="this.style.background='transparent'"
                 @endif>

                {{-- Checkbox de seleção --}}
                @if($selectMode)
                <div style="position:absolute; left:12px; top:50%; transform:translateY(-50%); z-index:10;">
                    <button wire:click="toggleSelect({{ $conv->id }})"
                            style="width:20px; height:20px; border-radius:5px; display:flex; align-items:center; justify-content:center; transition:all 0.15s; cursor:pointer;
                                   {{ $isSelected ? 'background:#ef4444; border:2px solid #ef4444;' : 'background:transparent; border:2px solid rgba(255,255,255,0.2);' }}">
                        @if($isSelected)
                            <svg width="10" height="10" fill="none" stroke="white" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        @endif
                    </button>
                </div>
                @endif

                <button wire:click="selectConversation({{ $conv->id }})"
                        style="width:100%; text-align:left; padding:11px 14px {{ $selectMode ? '11px 42px' : '11px 14px' }}; background:transparent; border:none; cursor:pointer;">
                    <div style="display:flex; align-items:flex-start; gap:10px;">
                        {{-- Avatar --}}
                        <div style="position:relative; flex-shrink:0;">
                            <img src="{{ $conv->contact->avatar }}" alt=""
                                 style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid {{ $activeId === $conv->id ? '#b2ff00' : 'rgba(255,255,255,0.06)' }};">
                            @if($conv->unread_count > 0)
                                <span style="position:absolute; top:-3px; right:-3px; min-width:18px; height:18px; background:#b2ff00; color:#111; font-size:9px; font-weight:800; border-radius:20px; display:flex; align-items:center; justify-content:center; padding:0 3px; border:2px solid #0B0F1C;">
                                    {{ $conv->unread_count > 9 ? '9+' : $conv->unread_count }}
                                </span>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:2px;">
                                <div style="display:flex; align-items:center; gap:5px; min-width:0; overflow:hidden;">
                                    <span style="font-size:12px; font-weight:600; color:{{ $conv->unread_count > 0 ? 'white' : 'rgba(255,255,255,0.8)' }}; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        {{ $conv->is_group ? ($conv->group_name ?: $conv->contact->display_name) : $conv->contact->display_name }}
                                    </span>
                                    @if($conv->is_group)
                                    <span style="flex-shrink:0; display:inline-flex; align-items:center; gap:2px; font-size:8px; font-weight:700; padding:2px 5px; border-radius:20px; background:rgba(168,85,247,0.15); color:#c084fc; border:1px solid rgba(168,85,247,0.25);">
                                        <svg width="8" height="8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75M9 7a4 4 0 100 8 4 4 0 000-8z"/>
                                        </svg>
                                        GRUPO
                                    </span>
                                    @endif
                                </div>
                                <span style="font-size:10px; color:rgba(255,255,255,0.2); flex-shrink:0; margin-left:4px;">
                                    {{ $conv->last_message_at?->diffForHumans(short: true) }}
                                </span>
                            </div>

                            <div style="display:flex; align-items:center; justify-content:space-between; gap:4px;">
                                <p style="font-size:11px; color:rgba(255,255,255,0.3); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex:1;">
                                    @php $last = $conv->latestMessage->first(); @endphp
                                    @if($last)
                                        @if($last->sender_type === 'agent') <span style="color:#b2ff00;">Você: </span> @endif
                                        {{ $last->type === 'text' ? \Illuminate\Support\Str::limit($last->content, 38) : '📎 ' . ucfirst($last->type) }}
                                    @else
                                        <span style="color:rgba(255,255,255,0.12);">Sem mensagens</span>
                                    @endif
                                </p>

                                {{-- Status badge --}}
                                @php
                                    $statusMap = [
                                        'open'      => ['label' => 'Aberto',   'bg' => 'rgba(178,255,0,0.12)',   'color' => '#b2ff00'],
                                        'resolved'  => ['label' => 'Resolvido','bg' => 'rgba(107,114,128,0.12)',  'color' => '#6b7280'],
                                        'pending'   => ['label' => 'Transf.',  'bg' => 'rgba(59,130,246,0.12)',   'color' => '#60a5fa'],
                                    ];
                                    $st = $statusMap[$conv->status] ?? $statusMap['pending'];
                                @endphp
                                <span style="font-size:9px; font-weight:600; padding:2px 6px; border-radius:20px; flex-shrink:0; background:{{ $st['bg'] }}; color:{{ $st['color'] }}; letter-spacing:0.02em;">
                                    {{ $st['label'] }}
                                </span>
                            </div>

                            {{-- Department + agente atribuído (admin/supervisor) --}}
                            @if(auth()->user()->canManageCompany())
                                <div style="display:flex; align-items:center; gap:6px; margin-top:3px;">
                                    <p style="font-size:10px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:{{ $conv->department->color }}; opacity:0.8;">
                                        {{ $conv->department->name }}
                                    </p>
                                    @if($conv->assignedAgent)
                                        <span style="font-size:9px; color:rgba(255,255,255,0.35);">·</span>
                                        <p style="font-size:10px; color:rgba(255,255,255,0.35); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            {{ $conv->assignedAgent->name }}
                                        </p>
                                    @endif
                                </div>
                            @endif

                            {{-- CRM badge --}}
                            @php $crmCard = $conv->contact->crmCards->first(); @endphp
                            @if($crmCard && $crmCard->pipeline && $crmCard->stage)
                                <div style="display:flex; margin-top:4px;">
                                    <span style="display:inline-flex; align-items:center; gap:3px; font-size:10px; font-weight:500; padding:2px 6px; border-radius:5px; background:{{ $crmCard->stage->color }}18; color:{{ $crmCard->stage->color }}; border:1px solid {{ $crmCard->stage->color }}33;">
                                        <svg width="8" height="8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                                        </svg>
                                        {{ $crmCard->pipeline->name }} · {{ $crmCard->stage->name }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </button>

                {{-- Botão Assumir (fora do <button> de seleção pra não gerar HTML inválido) --}}
                @if(!$conv->assigned_to && $filter === 'queue')
                    <div style="padding:0 14px 8px;">
                        <button wire:click="assignToMe({{ $conv->id }})"
                                style="font-size:10px; font-weight:700; color:#fbbf24; background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.3); border-radius:6px; padding:3px 10px; cursor:pointer; transition:all 0.15s;"
                                onmouseover="this.style.background='rgba(245,158,11,0.2)'"
                                onmouseout="this.style.background='rgba(245,158,11,0.1)'">
                            Assumir
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:160px; color:rgba(255,255,255,0.2);">
                <div style="width:48px; height:48px; border-radius:14px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center; margin-bottom:12px;">
                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity:0.4;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <p style="font-size:12px; font-weight:500; color:rgba(255,255,255,0.25);">Nenhuma conversa encontrada</p>
            </div>
        @endforelse

        {{-- Scroll infinito: carrega mais quando visível --}}
        @if($hasMore)
            <div x-data x-intersect="$wire.loadMore()"
                 style="padding:16px; text-align:center;">
                <div wire:loading.delay wire:target="loadMore" style="display:inline-flex; align-items:center; gap:6px; color:rgba(255,255,255,0.3); font-size:11px;">
                    <svg style="animation:spin 1s linear infinite; width:14px; height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 2v4m0 12v4m-7.07-3.93l2.83-2.83m8.48-8.48l2.83-2.83M2 12h4m12 0h4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83" stroke-linecap="round"/>
                    </svg>
                    Carregando...
                </div>
            </div>
        @endif
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
