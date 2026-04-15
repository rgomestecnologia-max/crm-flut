@php
$inputStyle = "width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:9px 14px; font-size:13px; color:white; outline:none; transition:all 0.2s; font-family:inherit; box-sizing:border-box;";
$inputFocus = "onfocus=\"this.style.borderColor='rgba(178,255,0,0.5)'; this.style.boxShadow='0 0 0 3px rgba(178,255,0,0.07)'\" onblur=\"this.style.borderColor='rgba(255,255,255,0.08)'; this.style.boxShadow='none'\"";
$labelStyle = "display:block; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;";
@endphp

<div>
    {{-- Header --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h2 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif; letter-spacing:-0.02em;">Agentes</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:2px;">Gerencie os logins dos atendentes</p>
        </div>
        <button wire:click="openCreate"
                style="display:flex; align-items:center; gap:8px; padding:9px 18px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-size:12px; font-weight:700; border-radius:10px; border:none; cursor:pointer; transition:all 0.2s; box-shadow:0 2px 12px rgba(178,255,0,0.25);"
                onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 20px rgba(178,255,0,0.35)'"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 12px rgba(178,255,0,0.25)'">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Agente
        </button>
    </div>

    @if($showForm)
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9) 0%, rgba(11,15,28,0.95) 100%); border:1px solid rgba(255,255,255,0.07); border-radius:16px; padding:24px; margin-bottom:24px; position:relative; overflow:hidden;">
        {{-- Top accent --}}
        <div style="position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, #b2ff0080, #b2ff0020, transparent); border-radius:16px 16px 0 0;"></div>

        <div style="display:flex; align-items:center; gap:8px; margin-bottom:20px;">
            <div style="width:2px; height:16px; background:#b2ff00; border-radius:2px;"></div>
            <h3 style="font-size:13px; font-weight:700; color:white;">{{ $editingId ? 'Editar Agente' : 'Novo Agente' }}</h3>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;" class="mobile-grid-1">
            <div>
                <label style="{{ $labelStyle }}">Nome *</label>
                <input wire:model="name" type="text" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                @error('name') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div>
                <label style="{{ $labelStyle }}">E-mail *</label>
                <input wire:model="email" type="email" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                @error('email') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div>
                <label style="{{ $labelStyle }}">Senha {{ $editingId ? '(em branco = manter)' : '*' }}</label>
                <input wire:model="password" type="password" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                @error('password') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div>
                <label style="{{ $labelStyle }}">Perfil *</label>
                <select wire:model="role" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                    <option value="agent">Agente</option>
                    <option value="supervisor">Supervisor</option>
                </select>
            </div>
            <div>
                <label style="{{ $labelStyle }}">Departamento principal *</label>
                <select wire:model.live="department_id" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                    <option value="">Selecionar...</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
                @error('department_id') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Departamentos adicionais --}}
        <div style="margin-top:18px;">
            <label style="{{ $labelStyle }}">Departamentos adicionais</label>
            <p style="font-size:11px; color:rgba(255,255,255,0.3); margin:-2px 0 10px;">
                Marque outros departamentos cujas conversas este agente também poderá ver e atender.
            </p>
            @php $availableExtras = $departments->filter(fn($d) => (int) $d->id !== (int) $department_id); @endphp
            @if($availableExtras->isEmpty())
                <p style="font-size:11px; color:rgba(255,255,255,0.25);">
                    {{ $department_id ? 'Nenhum outro departamento disponível.' : 'Selecione o departamento principal primeiro.' }}
                </p>
            @else
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    @foreach($availableExtras as $dept)
                        @php $checked = in_array((int) $dept->id, array_map('intval', $extra_department_ids), true); @endphp
                        <label style="display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:9px; cursor:pointer; transition:all 0.15s;
                                      background:{{ $checked ? $dept->color.'14' : 'rgba(255,255,255,0.03)' }};
                                      border:1px solid {{ $checked ? $dept->color.'55' : 'rgba(255,255,255,0.07)' }};">
                            <input type="checkbox"
                                   value="{{ $dept->id }}"
                                   wire:model="extra_department_ids"
                                   style="width:14px; height:14px; accent-color:{{ $dept->color }}; cursor:pointer;">
                            <span style="font-size:11px; font-weight:600; color:{{ $checked ? $dept->color : 'rgba(255,255,255,0.55)' }};">
                                {{ $dept->name }}
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
            @error('extra_department_ids.*') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
        </div>

        {{-- Módulos do agente --}}
        @if($role === 'agent' && !empty($companyPrincipalModules))
        <div style="margin-top:18px;">
            <label style="{{ $labelStyle }}">Menus que o agente pode acessar</label>
            <p style="font-size:11px; color:rgba(255,255,255,0.3); margin:-2px 0 10px;">
                Selecione quais menus principais este agente terá acesso. Supervisores acessam todos automaticamente.
            </p>
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                @foreach($companyPrincipalModules as $key => $label)
                    @php $checked = in_array($key, $agent_modules, true); @endphp
                    <label style="display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:9px; cursor:pointer; transition:all 0.15s;
                                  background:{{ $checked ? 'rgba(178,255,0,0.1)' : 'rgba(255,255,255,0.03)' }};
                                  border:1px solid {{ $checked ? 'rgba(178,255,0,0.4)' : 'rgba(255,255,255,0.07)' }};">
                        <input type="checkbox"
                               value="{{ $key }}"
                               wire:model.live="agent_modules"
                               style="width:14px; height:14px; accent-color:#b2ff00; cursor:pointer;">
                        <span style="font-size:11px; font-weight:600; color:{{ $checked ? '#b2ff00' : 'rgba(255,255,255,0.55)' }};">
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
        @endif

        <div style="display:flex; align-items:center; gap:10px; margin-top:20px;">
            <button wire:click="save"
                    style="padding:8px 20px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-size:12px; font-weight:700; border-radius:9px; border:none; cursor:pointer; transition:all 0.2s;"
                    onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                {{ $editingId ? 'Atualizar' : 'Criar' }}
            </button>
            <button wire:click="$set('showForm', false)"
                    style="padding:8px 16px; background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.4); font-size:12px; border-radius:9px; border:1px solid rgba(255,255,255,0.07); cursor:pointer; transition:all 0.2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.color='rgba(255,255,255,0.7)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.color='rgba(255,255,255,0.4)'">
                Cancelar
            </button>
        </div>
    </div>
    @endif

    {{-- Table --}}
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.8) 0%, rgba(11,15,28,0.9) 100%); border:1px solid rgba(255,255,255,0.06); border-radius:16px; overflow:auto; -webkit-overflow-scrolling:touch;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                    <th style="text-align:left; padding:12px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Agente</th>
                    <th style="text-align:left; padding:12px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Departamento</th>
                    <th style="text-align:left; padding:12px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Perfil</th>
                    <th style="text-align:left; padding:12px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Status</th>
                    <th style="text-align:right; padding:12px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agents as $agent)
                <tr style="border-bottom:1px solid rgba(255,255,255,0.03); transition:background 0.15s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.02)'"
                    onmouseout="this.style.background='transparent'">
                    <td style="padding:14px 20px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="position:relative; flex-shrink:0;">
                                <img src="{{ $agent->avatar_url }}" alt=""
                                     style="width:34px; height:34px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.08);">
                                <span style="position:absolute; bottom:-1px; right:-1px; width:9px; height:9px; border-radius:50%; border:2px solid #0B0F1C;
                                             background:{{ $agent->status === 'online' ? '#22c55e' : ($agent->status === 'busy' ? '#eab308' : '#6b7280') }};">
                                </span>
                            </div>
                            <div>
                                <p style="font-size:13px; font-weight:600; color:rgba(255,255,255,0.85);">{{ $agent->name }}</p>
                                <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:1px;">{{ $agent->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 20px;">
                        @php
                            $allDepts = $agent->departments->keyBy('id');
                            if ($agent->department && !$allDepts->has($agent->department->id)) {
                                $allDepts->put($agent->department->id, $agent->department);
                            }
                            // Coloca o principal primeiro
                            $sorted = $allDepts->sortBy(fn($d) => $d->id === ($agent->department_id ?? 0) ? 0 : 1)->values();
                        @endphp
                        @if($sorted->isEmpty())
                            <span style="font-size:12px; color:rgba(255,255,255,0.15);">—</span>
                        @else
                            <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                @foreach($sorted as $dept)
                                    @php $isPrimary = $dept->id === ($agent->department_id ?? 0); @endphp
                                    <span title="{{ $isPrimary ? 'Principal' : 'Adicional' }}"
                                          style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px; background:{{ $dept->color }}18; color:{{ $dept->color }}; border:1px solid {{ $dept->color }}{{ $isPrimary ? '55' : '25' }};">
                                        @if($isPrimary)
                                            <svg width="9" height="9" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.39 7.36H22l-6.19 4.5L18.2 22 12 17.27 5.8 22l2.39-8.14L2 9.36h7.61z"/></svg>
                                        @endif
                                        {{ $dept->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td style="padding:14px 20px;">
                        <span style="font-size:11px; color:rgba(255,255,255,0.45); text-transform:capitalize;">{{ $agent->role }}</span>
                    </td>
                    <td style="padding:14px 20px;">
                        <span style="font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px;
                                     background:{{ $agent->is_active ? 'rgba(34,197,94,0.12)' : 'rgba(107,114,128,0.12)' }};
                                     color:{{ $agent->is_active ? '#4ade80' : '#6b7280' }};
                                     border:1px solid {{ $agent->is_active ? 'rgba(34,197,94,0.2)' : 'rgba(107,114,128,0.2)' }};">
                            {{ $agent->is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </td>
                    <td style="padding:14px 20px; text-align:right;">
                        <div style="display:flex; align-items:center; justify-content:flex-end; gap:12px;">
                            <button wire:click="openEdit({{ $agent->id }})"
                                    style="font-size:11px; font-weight:600; color:#b2ff00; background:transparent; border:none; cursor:pointer; transition:opacity 0.15s;"
                                    onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">Editar</button>
                            <button wire:click="toggleActive({{ $agent->id }})"
                                    style="font-size:11px; font-weight:600; color:{{ $agent->is_active ? '#fbbf24' : '#4ade80' }}; background:transparent; border:none; cursor:pointer; transition:opacity 0.15s;"
                                    onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                                {{ $agent->is_active ? 'Desativar' : 'Ativar' }}
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding:48px 20px; text-align:center;">
                        <div style="display:flex; flex-direction:column; align-items:center; gap:10px; color:rgba(255,255,255,0.15);">
                            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity:0.4;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p style="font-size:13px;">Nenhum agente cadastrado.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
