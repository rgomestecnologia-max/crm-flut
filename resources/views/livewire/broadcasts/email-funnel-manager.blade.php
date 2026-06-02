@php
$inputStyle = "width:100%; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none; box-sizing:border-box;";
$labelStyle = "font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;";
$stepIcons = ['email' => '📧', 'delay' => '⏱', 'condition' => '🔀'];
$stepLabels = ['email' => 'Email', 'delay' => 'Delay', 'condition' => 'Condição'];
@endphp
<div>
    {{-- Tabs --}}
    <div style="display:flex; gap:8px; margin-bottom:16px;">
        @foreach(['list' => 'Funis', 'editor' => 'Editor de Steps', 'subscribers' => 'Contatos'] as $k => $l)
        <button wire:click="$set('tab', '{{ $k }}')" style="padding:5px 14px; font-size:11px; font-weight:{{ $tab === $k ? '600' : '400' }}; border-radius:7px; cursor:pointer; border:1px solid {{ $tab === $k ? 'rgba(139,92,246,0.3)' : 'rgba(255,255,255,0.08)' }}; background:{{ $tab === $k ? 'rgba(139,92,246,0.1)' : 'transparent' }}; color:{{ $tab === $k ? '#a78bfa' : 'rgba(255,255,255,0.4)' }};">{{ $l }}</button>
        @endforeach
    </div>

    {{-- ═══ LISTA ═══ --}}
    @if($tab === 'list')
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
        <h3 style="font-size:14px; font-weight:700; color:white;">Funis de Email</h3>
        <button wire:click="$set('showForm', true)" style="padding:6px 14px; font-size:11px; font-weight:600; color:#111; background:#a78bfa; border:none; border-radius:8px; cursor:pointer;">+ Novo Funil</button>
    </div>

    @if($showForm)
    <div style="background:rgba(139,92,246,0.04); border:1px solid rgba(139,92,246,0.15); border-radius:12px; padding:16px; margin-bottom:14px;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <div><label style="{{ $labelStyle }}">Nome do funil *</label><input wire:model="name" type="text" placeholder="Ex: Boas-vindas novos leads" style="{{ $inputStyle }}"></div>
            <div><label style="{{ $labelStyle }}">Gatilho de entrada</label>
                <select wire:model="triggerType" style="{{ $inputStyle }}">
                    <option value="manual">Manual</option>
                    <option value="tag">Tag adicionada</option>
                    <option value="landing_page">Lead da Landing Page</option>
                    <option value="flutchat">Lead do FlutChat</option>
                    <option value="crm_stage">Card movido no CRM</option>
                </select>
            </div>
            @if($triggerType !== 'manual')
            <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Valor do gatilho (nome da tag, ID da página, etc.)</label><input wire:model="triggerValue" type="text" placeholder="Ex: lead-quente" style="{{ $inputStyle }}"></div>
            @endif
        </div>
        <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:12px;">
            <button wire:click="$set('showForm', false)" style="padding:6px 14px; font-size:11px; color:rgba(255,255,255,0.4); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:7px; cursor:pointer;">Cancelar</button>
            <button wire:click="saveFunnel" style="padding:6px 16px; font-size:11px; font-weight:700; color:#111; background:#a78bfa; border:none; border-radius:7px; cursor:pointer;">Salvar</button>
        </div>
    </div>
    @endif

    @foreach($funnels as $f)
    <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:10px; padding:12px 16px; margin-bottom:6px; display:flex; align-items:center; gap:12px;">
        <div style="width:10px; height:10px; border-radius:50%; background:{{ $f->status === 'active' ? '#4ade80' : ($f->status === 'paused' ? '#f59e0b' : '#6b7280') }}; flex-shrink:0;"></div>
        <div style="flex:1; min-width:0;">
            <p style="font-size:13px; font-weight:700; color:white;">{{ $f->name }}</p>
            <p style="font-size:10px; color:rgba(255,255,255,0.3);">{{ $f->subscribers_count }} contatos ({{ $f->active_count ?? 0 }} ativos, {{ $f->completed_count ?? 0 }} completos) · {{ $f->trigger_type }} · {{ ucfirst($f->status) }}</p>
        </div>
        <div style="display:flex; gap:6px; flex-shrink:0;">
            <button wire:click="openEditor({{ $f->id }})" style="padding:4px 10px; font-size:10px; font-weight:600; color:#a78bfa; background:rgba(167,139,250,0.1); border:1px solid rgba(167,139,250,0.2); border-radius:6px; cursor:pointer;">Steps</button>
            <button wire:click="openSubscribers({{ $f->id }})" style="padding:4px 10px; font-size:10px; color:#60a5fa; background:rgba(96,165,250,0.1); border:1px solid rgba(96,165,250,0.2); border-radius:6px; cursor:pointer;">Contatos</button>
            <button wire:click="toggleFunnelStatus({{ $f->id }})" style="padding:4px 10px; font-size:10px; color:{{ $f->status === 'active' ? '#f59e0b' : '#4ade80' }}; background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:6px; cursor:pointer;">{{ $f->status === 'active' ? 'Pausar' : 'Ativar' }}</button>
            <button wire:click="editFunnel({{ $f->id }})" style="padding:4px 10px; font-size:10px; color:rgba(255,255,255,0.4); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:6px; cursor:pointer;">Config</button>
            <button wire:click="deleteFunnel({{ $f->id }})" wire:confirm="Excluir funil e todos os dados?" style="padding:4px 10px; font-size:10px; color:#f87171; background:transparent; border:1px solid rgba(239,68,68,0.2); border-radius:6px; cursor:pointer;">✕</button>
        </div>
    </div>
    @endforeach
    @endif

    {{-- ═══ EDITOR DE STEPS ═══ --}}
    @if($tab === 'editor')
    @if(!$editingFunnelId)
    <p style="color:rgba(255,255,255,0.3); font-size:13px; text-align:center; padding:40px;">Selecione um funil e clique em "Steps".</p>
    @else
    @php $currentFunnel = $funnels->firstWhere('id', $editingFunnelId); @endphp
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
        <h3 style="font-size:14px; font-weight:700; color:white;">Steps: {{ $currentFunnel?->name }}</h3>
        <div style="display:flex; gap:6px; align-items:center;">
            <select wire:model="addStepType" style="padding:4px 8px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:white; outline:none;">
                <option value="email">📧 Email</option>
                <option value="delay">⏱ Delay</option>
                <option value="condition">🔀 Condição</option>
            </select>
            <button wire:click="addStep" style="padding:4px 12px; font-size:11px; font-weight:600; color:#111; background:#a78bfa; border:none; border-radius:6px; cursor:pointer;">+ Step</button>
        </div>
    </div>

    @foreach($steps as $step)
    @php $cfg = $step->config ?? []; @endphp
    <div style="display:flex; align-items:stretch; margin-bottom:4px;">
        {{-- Linha vertical --}}
        <div style="width:30px; flex-shrink:0; display:flex; flex-direction:column; align-items:center;">
            <div style="width:24px; height:24px; border-radius:50%; background:{{ $step->type === 'email' ? 'rgba(139,92,246,0.2)' : ($step->type === 'delay' ? 'rgba(245,158,11,0.2)' : 'rgba(59,130,246,0.2)') }}; display:flex; align-items:center; justify-content:center; font-size:12px; flex-shrink:0;">{{ $stepIcons[$step->type] ?? '?' }}</div>
            @if(!$loop->last)<div style="flex:1; width:2px; background:rgba(255,255,255,0.06);"></div>@endif
        </div>
        {{-- Card --}}
        <div style="flex:1; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:8px; padding:10px 12px; margin-left:8px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <span style="font-size:11px; font-weight:700; color:{{ $step->type === 'email' ? '#a78bfa' : ($step->type === 'delay' ? '#fbbf24' : '#60a5fa') }};">{{ $stepLabels[$step->type] ?? $step->type }} #{{ $step->sort_order }}</span>
                <span style="flex:1;"></span>
                <button wire:click="moveStepUp({{ $step->id }})" style="font-size:9px; color:rgba(255,255,255,0.3); background:none; border:none; cursor:pointer;">▲</button>
                <button wire:click="moveStepDown({{ $step->id }})" style="font-size:9px; color:rgba(255,255,255,0.3); background:none; border:none; cursor:pointer;">▼</button>
                <button wire:click="deleteStep({{ $step->id }})" wire:confirm="Excluir step?" style="font-size:9px; color:#f87171; background:none; border:none; cursor:pointer;">✕</button>
            </div>
            @if($step->type === 'email')
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Assunto</label>
                    <input type="text" value="{{ $cfg['subject'] ?? '' }}" wire:change="updateStepConfig({{ $step->id }}, 'subject', $event.target.value)" style="{{ $inputStyle }}">
                </div>
                <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Conteúdo HTML (use {nome}, {email})</label>
                    <textarea wire:change="updateStepConfig({{ $step->id }}, 'html_content', $event.target.value)" rows="4" style="{{ $inputStyle }} min-height:80px; font-family:monospace; font-size:11px;">{{ $cfg['html_content'] ?? '' }}</textarea>
                </div>
            </div>
            @elseif($step->type === 'delay')
            <div style="display:flex; gap:8px; align-items:center;">
                <label style="{{ $labelStyle }} margin-bottom:0;">Aguardar</label>
                <select wire:change="updateStepConfig({{ $step->id }}, 'seconds', $event.target.value)" style="padding:4px 8px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:white; outline:none;">
                    @foreach([3600 => '1 hora', 21600 => '6 horas', 43200 => '12 horas', 86400 => '1 dia', 172800 => '2 dias', 259200 => '3 dias', 432000 => '5 dias', 604800 => '7 dias'] as $sec => $lbl)
                    <option value="{{ $sec }}" {{ ($cfg['seconds'] ?? 86400) == $sec ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            @elseif($step->type === 'condition')
            <div style="display:flex; gap:8px; align-items:center;">
                <label style="{{ $labelStyle }} margin-bottom:0;">Se o contato</label>
                <select wire:change="updateStepConfig({{ $step->id }}, 'field', $event.target.value)" style="padding:4px 8px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:white; outline:none;">
                    <option value="opened" {{ ($cfg['field'] ?? '') === 'opened' ? 'selected' : '' }}>Abriu o email anterior</option>
                    <option value="clicked" {{ ($cfg['field'] ?? '') === 'clicked' ? 'selected' : '' }}>Clicou no email anterior</option>
                    <option value="not_opened" {{ ($cfg['field'] ?? '') === 'not_opened' ? 'selected' : '' }}>NÃO abriu o email anterior</option>
                </select>
            </div>
            <p style="font-size:10px; color:rgba(255,255,255,0.25); margin-top:4px;">✅ SIM → próximo step | ❌ NÃO → pula para o step seguinte</p>
            @endif
        </div>
    </div>
    @endforeach
    @endif
    @endif

    {{-- ═══ CONTATOS ═══ --}}
    @if($tab === 'subscribers')
    @if(!$editingFunnelId)
    <p style="color:rgba(255,255,255,0.3); font-size:13px; text-align:center; padding:40px;">Selecione um funil e clique em "Contatos".</p>
    @else
    @php $currentFunnel = $funnels->firstWhere('id', $editingFunnelId); @endphp
    <h3 style="font-size:14px; font-weight:700; color:white; margin-bottom:14px;">Contatos: {{ $currentFunnel?->name }}</h3>

    {{-- Adicionar contatos --}}
    <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:10px; padding:12px; margin-bottom:14px;">
        <div style="display:flex; gap:8px; margin-bottom:8px;">
            <input wire:model.live.debounce.300ms="contactSearch" type="text" placeholder="Buscar contato por nome, email ou telefone..."
                   style="flex:1; {{ $inputStyle }}">
            @if(!empty($selectedContactIds))
            <button wire:click="addSubscribers" style="padding:5px 12px; font-size:11px; font-weight:600; color:#111; background:#4ade80; border:none; border-radius:6px; cursor:pointer;">Adicionar {{ count($selectedContactIds) }}</button>
            @endif
        </div>
        @if($contactSearch)
        <div style="max-height:150px; overflow-y:auto;">
            @foreach($contacts as $c)
            @php $selected = in_array($c->id, $selectedContactIds); @endphp
            <button wire:click="toggleContactSelect({{ $c->id }})" style="width:100%; text-align:left; padding:6px 10px; background:{{ $selected ? 'rgba(74,222,128,0.08)' : 'transparent' }}; border:none; border-bottom:1px solid rgba(255,255,255,0.04); cursor:pointer; display:flex; align-items:center; gap:8px; color:white; font-size:11px;"
                    onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background='{{ $selected ? 'rgba(74,222,128,0.08)' : 'transparent' }}'">
                <div style="width:16px; height:16px; border-radius:3px; border:2px solid {{ $selected ? '#4ade80' : 'rgba(255,255,255,0.15)' }}; background:{{ $selected ? '#4ade80' : 'transparent' }}; display:flex; align-items:center; justify-content:center;">
                    @if($selected)<svg width="10" height="10" fill="white" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>@endif
                </div>
                <span style="flex:1;">{{ $c->name ?: 'Sem nome' }}</span>
                <span style="color:rgba(255,255,255,0.3);">{{ $c->email ?: $c->phone }}</span>
            </button>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Lista de subscribers --}}
    @foreach($subscribers as $sub)
    <div style="display:flex; align-items:center; gap:10px; padding:8px 12px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:8px; margin-bottom:4px;">
        <div style="width:8px; height:8px; border-radius:50%; background:{{ $sub->status === 'active' ? '#4ade80' : ($sub->status === 'completed' ? '#60a5fa' : '#6b7280') }}; flex-shrink:0;"></div>
        <div style="flex:1; min-width:0;">
            <p style="font-size:12px; font-weight:600; color:white;">{{ $sub->contact?->name ?: 'Sem nome' }}</p>
            <p style="font-size:10px; color:rgba(255,255,255,0.3);">{{ $sub->contact?->email ?: $sub->contact?->phone }} · Step: {{ $sub->currentStep?->sort_order ?? '-' }} · {{ ucfirst($sub->status) }}</p>
        </div>
        <span style="font-size:9px; color:rgba(255,255,255,0.2);">{{ $sub->entered_at?->format('d/m H:i') }}</span>
        <button wire:click="removeSubscriber({{ $sub->id }})" style="font-size:10px; color:#f87171; background:none; border:none; cursor:pointer;">✕</button>
    </div>
    @endforeach
    @if($subscribers->isEmpty())
    <p style="color:rgba(255,255,255,0.2); font-size:12px; text-align:center; padding:20px;">Nenhum contato neste funil.</p>
    @endif
    @endif
    @endif
</div>
