@php
$inputStyle = "width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:9px 14px; font-size:13px; color:white; outline:none; transition:all 0.2s; font-family:inherit; box-sizing:border-box;";
$inputFocus = "onfocus=\"this.style.borderColor='rgba(178,255,0,0.5)'; this.style.boxShadow='0 0 0 3px rgba(178,255,0,0.07)'\" onblur=\"this.style.borderColor='rgba(255,255,255,0.08)'; this.style.boxShadow='none'\"";
$labelStyle = "display:block; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;";
@endphp

<div>
    {{-- Header --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h2 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif; letter-spacing:-0.02em;">Departamentos</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:2px;">Gerencie os setores da empresa</p>
        </div>
        <button wire:click="openCreate"
                style="display:flex; align-items:center; gap:8px; padding:9px 18px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-size:12px; font-weight:700; border-radius:10px; border:none; cursor:pointer; transition:all 0.2s; box-shadow:0 2px 12px rgba(178,255,0,0.25);"
                onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 20px rgba(178,255,0,0.35)'"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 12px rgba(178,255,0,0.25)'">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Departamento
        </button>
    </div>

    {{-- Form --}}
    @if($showForm)
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9) 0%, rgba(11,15,28,0.95) 100%); border:1px solid rgba(255,255,255,0.07); border-radius:16px; padding:24px; margin-bottom:24px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, #b2ff0080, #b2ff0020, transparent); border-radius:16px 16px 0 0;"></div>

        <div style="display:flex; align-items:center; gap:8px; margin-bottom:20px;">
            <div style="width:2px; height:16px; background:#b2ff00; border-radius:2px;"></div>
            <h3 style="font-size:13px; font-weight:700; color:white;">{{ $editingId ? 'Editar Departamento' : 'Novo Departamento' }}</h3>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
            <div>
                <label style="{{ $labelStyle }}">Nome *</label>
                <input wire:model="name" type="text" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                @error('name') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div>
                <label style="{{ $labelStyle }}">Descrição</label>
                <input wire:model="description" type="text" style="{{ $inputStyle }}" {!! $inputFocus !!}>
            </div>
            <div>
                <label style="{{ $labelStyle }}">Cor</label>
                <div style="display:flex; align-items:center; gap:8px;">
                    <input wire:model.live="color" type="color"
                           style="width:42px; height:38px; border-radius:8px; cursor:pointer; background:transparent; border:1px solid rgba(255,255,255,0.1); padding:3px; flex-shrink:0;">
                    <input wire:model="color" type="text" style="{{ $inputStyle }} font-family:monospace;" {!! $inputFocus !!}>
                </div>
            </div>
            <div>
                <label style="{{ $labelStyle }}">Ícone</label>
                <input wire:model="icon" type="text" placeholder="ex: chat-bubble-left-right" style="{{ $inputStyle }}" {!! $inputFocus !!}>
            </div>
        </div>

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
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.8) 0%, rgba(11,15,28,0.9) 100%); border:1px solid rgba(255,255,255,0.06); border-radius:16px; overflow:hidden;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                    <th style="text-align:left; padding:12px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Departamento</th>
                    <th style="text-align:left; padding:12px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Agentes</th>
                    <th style="text-align:left; padding:12px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Conversas</th>
                    <th style="text-align:left; padding:12px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Status</th>
                    <th style="text-align:right; padding:12px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $dept)
                <tr style="border-bottom:1px solid rgba(255,255,255,0.03); transition:background 0.15s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.02)'"
                    onmouseout="this.style.background='transparent'">
                    <td style="padding:14px 20px;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:{{ $dept->color }}15; border:1px solid {{ $dept->color }}30;">
                                <div style="width:10px; height:10px; border-radius:50%; background:{{ $dept->color }};"></div>
                            </div>
                            <div>
                                <p style="font-size:13px; font-weight:600; color:rgba(255,255,255,0.85);">{{ $dept->name }}</p>
                                @if($dept->description)
                                <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:1px;">{{ $dept->description }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 20px;">
                        <span style="font-size:13px; font-weight:600; color:rgba(255,255,255,0.5);">{{ $dept->users_count }}</span>
                    </td>
                    <td style="padding:14px 20px;">
                        <span style="font-size:13px; font-weight:600; color:rgba(255,255,255,0.5);">{{ $dept->conversations_count }}</span>
                    </td>
                    <td style="padding:14px 20px;">
                        <span style="font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px;
                                     background:{{ $dept->is_active ? 'rgba(34,197,94,0.12)' : 'rgba(107,114,128,0.12)' }};
                                     color:{{ $dept->is_active ? '#4ade80' : '#6b7280' }};
                                     border:1px solid {{ $dept->is_active ? 'rgba(34,197,94,0.2)' : 'rgba(107,114,128,0.2)' }};">
                            {{ $dept->is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </td>
                    <td style="padding:14px 20px; text-align:right;">
                        <div style="display:flex; align-items:center; justify-content:flex-end; gap:12px;">
                            <button wire:click="openEdit({{ $dept->id }})"
                                    style="font-size:11px; font-weight:600; color:#b2ff00; background:transparent; border:none; cursor:pointer; transition:opacity 0.15s;"
                                    onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">Editar</button>
                            <button wire:click="delete({{ $dept->id }})" wire:confirm="Excluir este departamento?"
                                    style="font-size:11px; font-weight:600; color:#f87171; background:transparent; border:none; cursor:pointer; transition:opacity 0.15s;"
                                    onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">Excluir</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding:48px 20px; text-align:center;">
                        <div style="display:flex; flex-direction:column; align-items:center; gap:10px; color:rgba(255,255,255,0.15);">
                            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity:0.4;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <p style="font-size:13px;">Nenhum departamento cadastrado.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
