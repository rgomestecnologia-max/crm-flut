@php
$inputStyle = "width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:9px 14px; font-size:13px; color:white; outline:none; transition:all 0.2s; font-family:inherit; box-sizing:border-box;";
$inputFocus = "onfocus=\"this.style.borderColor='rgba(178,255,0,0.5)'; this.style.boxShadow='0 0 0 3px rgba(178,255,0,0.07)'\" onblur=\"this.style.borderColor='rgba(255,255,255,0.08)'; this.style.boxShadow='none'\"";
$labelStyle = "display:block; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;";
@endphp

<div>
    {{-- Header --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h2 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif; letter-spacing:-0.02em;">Empresas</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:2px;">Gerencie os tenants do sistema</p>
        </div>
        <button wire:click="openCreate"
                style="display:flex; align-items:center; gap:8px; padding:9px 18px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-size:12px; font-weight:700; border-radius:10px; border:none; cursor:pointer; transition:all 0.2s; box-shadow:0 2px 12px rgba(178,255,0,0.25);"
                onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 20px rgba(178,255,0,0.35)'"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 12px rgba(178,255,0,0.25)'">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Nova Empresa
        </button>
    </div>

    @if($showForm)
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9) 0%, rgba(11,15,28,0.95) 100%); border:1px solid rgba(255,255,255,0.07); border-radius:16px; padding:24px; margin-bottom:24px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, #b2ff0080, #b2ff0020, transparent); border-radius:16px 16px 0 0;"></div>

        <div style="display:flex; align-items:center; gap:8px; margin-bottom:20px;">
            <div style="width:2px; height:16px; background:#b2ff00; border-radius:2px;"></div>
            <h3 style="font-size:13px; font-weight:700; color:white;">{{ $editingId ? 'Editar Empresa' : 'Nova Empresa' }}</h3>
        </div>

        <div style="display:grid; grid-template-columns:1fr 140px; gap:14px;">
            <div>
                <label style="{{ $labelStyle }}">Nome *</label>
                <input wire:model="name" type="text" style="{{ $inputStyle }}" {!! $inputFocus !!} placeholder="Ex: Acme Ltda">
                @error('name') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div>
                <label style="{{ $labelStyle }}">Cor</label>
                <div style="display:flex; align-items:center; gap:8px;">
                    <input wire:model.live="color" type="color" style="width:42px; height:38px; border:1px solid rgba(255,255,255,0.08); border-radius:10px; background:transparent; cursor:pointer; padding:2px;">
                    <input wire:model.live="color" type="text" style="{{ $inputStyle }} flex:1;" placeholder="#b2ff00">
                </div>
            </div>
        </div>

        {{-- Logo --}}
        <div style="margin-top:14px;">
            <label style="{{ $labelStyle }}">Logo (opcional)</label>
            <div style="display:flex; align-items:center; gap:14px;">
                {{-- Preview --}}
                <div style="width:64px; height:64px; border-radius:14px; overflow:hidden; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.02); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    @if($logoUpload)
                        <img src="{{ $logoUpload->temporaryUrl() }}" alt="Preview" style="width:100%; height:100%; object-fit:cover;">
                    @elseif($existingLogo)
                        <img src="{{ \App\Services\MediaStorage::url($existingLogo) }}" alt="Logo atual" style="width:100%; height:100%; object-fit:cover;">
                    @else
                        <svg width="22" height="22" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    @endif
                </div>
                <div style="flex:1;">
                    <input wire:model="logoUpload" type="file" accept="image/*"
                           style="font-size:11px; color:rgba(255,255,255,0.5); width:100%;">
                    <p style="font-size:10px; color:rgba(255,255,255,0.25); margin-top:6px;">PNG, JPG ou WebP até 2&nbsp;MB. Aparece no menu de troca de empresa.</p>
                    @error('logoUpload') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                </div>
                @if($existingLogo && !$logoUpload)
                <button type="button" wire:click="removeExistingLogo"
                        wire:confirm="Remover a logo atual?"
                        style="font-size:11px; font-weight:600; color:#f87171; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); border-radius:8px; padding:6px 12px; cursor:pointer; transition:all 0.15s; flex-shrink:0;"
                        onmouseover="this.style.background='rgba(239,68,68,0.16)'"
                        onmouseout="this.style.background='rgba(239,68,68,0.08)'">
                    Remover
                </button>
                @endif
            </div>
        </div>

        @if(!$editingId)
        <div style="margin-top:18px; padding:12px 14px; background:rgba(178,255,0,0.05); border:1px solid rgba(178,255,0,0.18); border-radius:10px;">
            <div style="display:flex; align-items:flex-start; gap:10px;">
                <svg width="16" height="16" fill="none" stroke="#b2ff00" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0; margin-top:1px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p style="font-size:12px; font-weight:600; color:#b2ff00; margin-bottom:3px;">Template inicial</p>
                    <p style="font-size:11px; color:rgba(255,255,255,0.55); line-height:1.5;">
                        A nova empresa será criada com 1 departamento "Geral" e 1 pipeline "Vendas" com as etapas <em>Novo → Em negociação → Fechado</em>. Tudo o resto começa vazio.
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- Módulos --}}
        <div style="margin-top:20px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:14px;">
                <div style="width:2px; height:14px; background:#3b82f6; border-radius:2px;"></div>
                <h4 style="font-size:11px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Módulos contratados</h4>
            </div>
            <p style="font-size:11px; color:rgba(255,255,255,0.35); margin:-6px 0 14px 10px;">
                Selecione quais funcionalidades esta empresa terá acesso. Supervisores só verão os menus habilitados aqui.
            </p>

            @foreach(\App\Models\Company::AVAILABLE_MODULES as $section => $items)
                <p style="font-size:9px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin:12px 0 8px 2px;">
                    {{ $section === 'principal' ? 'Principal' : 'Gestão' }}
                </p>
                <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:4px;">
                    @foreach($items as $key => $label)
                        @php $checked = in_array($key, $modules, true); @endphp
                        <label style="display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:9px; cursor:pointer; transition:all 0.15s;
                                      background:{{ $checked ? 'rgba(59,130,246,0.1)' : 'rgba(255,255,255,0.03)' }};
                                      border:1px solid {{ $checked ? 'rgba(59,130,246,0.4)' : 'rgba(255,255,255,0.07)' }};">
                            <input type="checkbox"
                                   value="{{ $key }}"
                                   wire:model.live="modules"
                                   style="width:14px; height:14px; accent-color:#3b82f6; cursor:pointer;">
                            <span style="font-size:11px; font-weight:600; color:{{ $checked ? '#60a5fa' : 'rgba(255,255,255,0.55)' }};">
                                {{ $label }}
                            </span>
                        </label>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div style="display:flex; align-items:center; gap:14px; margin-top:18px;">
            <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer;">
                <input type="checkbox" wire:model="is_active" style="width:14px; height:14px; accent-color:#b2ff00; cursor:pointer;">
                <span style="font-size:12px; color:rgba(255,255,255,0.7);">Ativa</span>
            </label>
        </div>

        <div style="display:flex; align-items:center; gap:10px; margin-top:20px;">
            <button wire:click="save"
                    wire:loading.attr="disabled"
                    style="padding:8px 20px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-size:12px; font-weight:700; border-radius:9px; border:none; cursor:pointer; transition:all 0.2s;"
                    onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                {{ $editingId ? 'Atualizar' : 'Criar empresa' }}
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
                    <th style="text-align:left; padding:12px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Empresa</th>
                    <th style="text-align:left; padding:12px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Slug</th>
                    <th style="text-align:left; padding:12px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Usuários</th>
                    <th style="text-align:left; padding:12px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Status</th>
                    <th style="text-align:right; padding:12px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $company)
                <tr style="border-bottom:1px solid rgba(255,255,255,0.03); transition:background 0.15s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.02)'"
                    onmouseout="this.style.background='transparent'">
                    <td style="padding:14px 20px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center; background:{{ $company->color }}1a; border:1px solid {{ $company->color }}40; flex-shrink:0;">
                                @if($company->logo_url)
                                    <img src="{{ $company->logo_url }}" alt="" style="width:32px; height:32px; border-radius:9px; object-fit:cover;">
                                @else
                                    <span style="font-size:13px; font-weight:800; color:{{ $company->color }};">
                                        {{ strtoupper(substr($company->name, 0, 1)) }}
                                    </span>
                                @endif
                            </div>
                            <p style="font-size:13px; font-weight:600; color:rgba(255,255,255,0.85);">{{ $company->name }}</p>
                        </div>
                    </td>
                    <td style="padding:14px 20px;">
                        <span style="font-size:11px; font-family:monospace; color:rgba(255,255,255,0.35);">{{ $company->slug }}</span>
                    </td>
                    <td style="padding:14px 20px;">
                        <span style="font-size:12px; color:rgba(255,255,255,0.5);">{{ $company->users_count }}</span>
                    </td>
                    <td style="padding:14px 20px;">
                        <span style="font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px;
                                     background:{{ $company->is_active ? 'rgba(34,197,94,0.12)' : 'rgba(107,114,128,0.12)' }};
                                     color:{{ $company->is_active ? '#4ade80' : '#6b7280' }};
                                     border:1px solid {{ $company->is_active ? 'rgba(34,197,94,0.2)' : 'rgba(107,114,128,0.2)' }};">
                            {{ $company->is_active ? 'Ativa' : 'Inativa' }}
                        </span>
                    </td>
                    <td style="padding:14px 20px; text-align:right;">
                        <div style="display:flex; align-items:center; justify-content:flex-end; gap:12px;">
                            <button wire:click="openEdit({{ $company->id }})"
                                    style="font-size:11px; font-weight:600; color:#b2ff00; background:transparent; border:none; cursor:pointer; transition:opacity 0.15s;"
                                    onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">Editar</button>
                            <button wire:click="toggleActive({{ $company->id }})"
                                    style="font-size:11px; font-weight:600; color:{{ $company->is_active ? '#fbbf24' : '#4ade80' }}; background:transparent; border:none; cursor:pointer; transition:opacity 0.15s;"
                                    onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                                {{ $company->is_active ? 'Desativar' : 'Ativar' }}
                            </button>
                            <button wire:click="openDelete({{ $company->id }})"
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/>
                            </svg>
                            <p style="font-size:13px;">Nenhuma empresa cadastrada.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal de exclusão --}}
    @if($deletingId)
        @php $companyToDelete = $companies->firstWhere('id', $deletingId); @endphp
        @if($companyToDelete)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9000; display:flex; align-items:center; justify-content:center; padding:24px;"
             wire:click.self="cancelDelete">
            <div style="background:linear-gradient(145deg, #11182F 0%, #0B0F1C 100%); border:1px solid rgba(239,68,68,0.25); border-radius:16px; padding:28px; max-width:480px; width:100%; box-shadow:0 24px 80px rgba(0,0,0,0.6);">

                <div style="display:flex; align-items:flex-start; gap:14px; margin-bottom:20px;">
                    <div style="width:42px; height:42px; border-radius:12px; background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.3); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <svg width="20" height="20" fill="none" stroke="#f87171" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div style="flex:1;">
                        <h3 style="font-size:15px; font-weight:800; color:white; margin-bottom:6px;">Excluir empresa permanentemente</h3>
                        <p style="font-size:12px; color:rgba(255,255,255,0.55); line-height:1.55;">
                            Esta ação <strong style="color:#f87171;">remove a empresa "{{ $companyToDelete->name }}" e TODOS os dados ligados a ela</strong>: contatos, conversas, mensagens, departamentos, pipelines, agentes, tokens, configurações, etc. <strong>Não pode ser desfeito.</strong>
                        </p>
                    </div>
                </div>

                <div style="background:rgba(239,68,68,0.05); border:1px solid rgba(239,68,68,0.18); border-radius:10px; padding:14px; margin-bottom:18px;">
                    <p style="font-size:11px; color:rgba(255,255,255,0.55); margin-bottom:8px;">
                        Para confirmar, digite o nome exato da empresa abaixo:
                    </p>
                    <p style="font-size:12px; font-weight:700; color:white; margin-bottom:10px; font-family:monospace;">{{ $companyToDelete->name }}</p>
                    <input wire:model="deleteConfirmName" type="text"
                           placeholder="Digite o nome aqui..."
                           style="width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:9px 12px; font-size:12px; color:white; outline:none; font-family:monospace; box-sizing:border-box;">
                    @error('deleteConfirmName') <p style="font-size:11px; color:#f87171; margin-top:6px;">{{ $message }}</p> @enderror
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button wire:click="cancelDelete"
                            style="padding:9px 18px; background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.6); font-size:12px; font-weight:600; border-radius:9px; border:1px solid rgba(255,255,255,0.07); cursor:pointer; transition:all 0.2s;"
                            onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.color='white'"
                            onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.color='rgba(255,255,255,0.6)'">
                        Cancelar
                    </button>
                    <button wire:click="confirmDelete"
                            wire:loading.attr="disabled"
                            style="padding:9px 18px; background:#ef4444; color:white; font-size:12px; font-weight:700; border-radius:9px; border:none; cursor:pointer; transition:all 0.2s;"
                            onmouseover="this.style.background='#dc2626'"
                            onmouseout="this.style.background='#ef4444'">
                        Excluir empresa
                    </button>
                </div>
            </div>
        </div>
        @endif
    @endif
</div>
