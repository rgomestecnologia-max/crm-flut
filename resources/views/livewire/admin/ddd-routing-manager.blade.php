<div>
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
        <svg width="18" height="18" fill="none" stroke="#3b82f6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <h2 style="font-size:15px; font-weight:700; color:white; font-family:'Syne',sans-serif;">Roteamento por DDD</h2>
    </div>
    <p style="font-size:11px; color:rgba(255,255,255,0.3); margin-bottom:16px;">Atribua DDDs aos agentes. Quando um lead chegar com o DDD correspondente, será roteado automaticamente.</p>

    {{-- Add DDDs --}}
    <div style="display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap;">
        <select wire:model="selectedAgent"
                style="padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; min-width:200px;">
            <option value="">Selecione o agente...</option>
            @foreach($agents as $agent)
            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
            @endforeach
        </select>
        <select wire:model="selectedDepartment"
                style="padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; min-width:160px;">
            <option value="">Departamento...</option>
            @foreach($departments as $dept)
            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
            @endforeach
        </select>
        <input wire:model="newDdds" type="text" placeholder="DDDs separados por vírgula (ex: 11, 12, 13)"
               style="flex:1; min-width:200px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none;">
        <button wire:click="addDdds"
                style="padding:8px 16px; font-size:12px; font-weight:600; color:#111; background:linear-gradient(135deg, #b2ff00, #8fcc00); border:none; border-radius:8px; cursor:pointer;">
            Adicionar
        </button>
    </div>

    {{-- Rules grouped by agent --}}
    <div style="display:flex; flex-direction:column; gap:12px;">
        @forelse($grouped as $agentId => $ddds)
        @php $agent = $agents->firstWhere('id', $agentId); @endphp
        <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:12px; padding:14px 16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                <div style="width:28px; height:28px; border-radius:50%; background:rgba(59,130,246,0.15); border:1px solid rgba(59,130,246,0.3); display:flex; align-items:center; justify-content:center;">
                    <span style="font-size:11px; font-weight:700; color:#60a5fa;">{{ mb_substr($agent->name ?? '?', 0, 1) }}</span>
                </div>
                <p style="font-size:13px; font-weight:600; color:white;">{{ $agent->name ?? 'Agente #'.$agentId }}</p>
                @if(!empty($agentDepts[$agentId]))
                <span style="font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; background:rgba(59,130,246,0.1); color:#60a5fa; border:1px solid rgba(59,130,246,0.2);">{{ $agentDepts[$agentId] }}</span>
                @endif
                <span style="font-size:10px; color:rgba(255,255,255,0.3); margin-left:4px;">{{ count($ddds) }} DDDs</span>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:6px;">
                @php sort($ddds); @endphp
                @foreach($ddds as $ddd)
                <div style="display:inline-flex; align-items:center; gap:4px; padding:4px 10px; background:rgba(59,130,246,0.08); border:1px solid rgba(59,130,246,0.2); border-radius:8px;">
                    <span style="font-size:12px; font-weight:600; color:#60a5fa;">{{ $ddd }}</span>
                    <button wire:click="removeDdd('{{ $ddd }}')" wire:confirm="Remover DDD {{ $ddd }}?"
                            style="background:none; border:none; color:rgba(255,255,255,0.2); cursor:pointer; font-size:14px; line-height:1; padding:0 2px;"
                            onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='rgba(255,255,255,0.2)'">&times;</button>
                </div>
                @endforeach
            </div>
        </div>
        @empty
        <p style="text-align:center; font-size:12px; color:rgba(255,255,255,0.2); padding:20px;">Nenhuma regra de DDD configurada.</p>
        @endforelse
    </div>
</div>
