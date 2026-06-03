@php
$inputStyle = "width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; box-sizing:border-box;";
$labelStyle = "font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.06em; display:block;";
@endphp
<div style="display:flex; flex-direction:column; height:100%; overflow:hidden;">
    {{-- Header --}}
    <div style="height:60px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:space-between; padding:0 24px; flex-shrink:0; background:rgba(11,15,28,0.5); backdrop-filter:blur(6px);">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <h1 style="font-size:15px; font-weight:800; color:white; font-family:Syne,sans-serif; letter-spacing:-0.02em;">Leads</h1>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <button wire:click="openImport" style="display:flex; align-items:center; gap:6px; padding:6px 14px; font-size:11px; font-weight:600; color:#10b981; background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.2); border-radius:8px; cursor:pointer;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Importar CSV
            </button>
            <button wire:click="openCreate" style="display:flex; align-items:center; gap:6px; padding:6px 14px; font-size:11px; font-weight:600; color:#b2ff00; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:8px; cursor:pointer;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Novo Lead
            </button>
        </div>
    </div>

    <div style="padding:20px 24px; flex:1; overflow-y:auto;">
        {{-- Filtros --}}
        <div style="display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap;">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por nome, telefone, empresa, CNPJ..."
                   style="flex:1; min-width:200px; padding:8px 14px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none;">
            <select wire:model.live="filterType" style="padding:8px 14px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white;">
                <option value="">Todos os tipos</option>
                <option value="person">Pessoa</option>
                <option value="company">Empresa</option>
            </select>
            <select wire:model.live="filterTag" style="padding:8px 14px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white;">
                <option value="">Todas as tags</option>
                @foreach($allTags as $tag)
                <option value="{{ $tag }}">{{ $tag }}</option>
                @endforeach
            </select>
        </div>

        {{-- Tabela --}}
        <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:12px; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.06);">
                        <th style="padding:10px 16px; text-align:left; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Tipo</th>
                        <th style="padding:10px 16px; text-align:left; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Nome</th>
                        <th style="padding:10px 16px; text-align:left; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Telefone</th>
                        <th style="padding:10px 16px; text-align:left; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Email</th>
                        <th style="padding:10px 16px; text-align:left; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Tags</th>
                        <th style="padding:10px 16px; text-align:center; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Status</th>
                        <th style="padding:10px 16px; text-align:right; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.03);" class="hover:bg-white/[0.02]">
                        <td style="padding:10px 16px;">
                            @if($lead->type === 'company')
                            <span style="font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; background:rgba(139,92,246,0.1); color:#a78bfa; border:1px solid rgba(139,92,246,0.2);">Empresa</span>
                            @else
                            <span style="font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; background:rgba(96,165,250,0.1); color:#60a5fa; border:1px solid rgba(96,165,250,0.2);">Pessoa</span>
                            @endif
                        </td>
                        <td style="padding:10px 16px;">
                            <p style="font-size:12px; color:white; font-weight:500;">{{ $lead->name ?: '—' }}</p>
                            @if($lead->company_name)
                            <p style="font-size:10px; color:rgba(255,255,255,0.35);">{{ $lead->company_name }}</p>
                            @endif
                        </td>
                        <td style="padding:10px 16px; font-size:12px; color:rgba(255,255,255,0.5);">{{ $lead->phone }}</td>
                        <td style="padding:10px 16px; font-size:11px; color:rgba(255,255,255,0.35);">{{ $lead->email ?? '—' }}</td>
                        <td style="padding:10px 16px;">
                            @foreach(($lead->tags ?? []) as $tag)
                            <span style="display:inline-block; padding:2px 8px; font-size:10px; font-weight:600; border-radius:20px; background:rgba(59,130,246,0.1); color:#60a5fa; border:1px solid rgba(59,130,246,0.2); margin-right:4px;">{{ $tag }}</span>
                            @endforeach
                        </td>
                        <td style="padding:10px 16px; text-align:center;">
                            <button wire:click="toggleActive({{ $lead->id }})" style="cursor:pointer; background:none; border:none;">
                                @if($lead->is_active)
                                <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">Ativo</span>
                                @else
                                <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(239,68,68,0.12); color:#f87171; border:1px solid rgba(239,68,68,0.2);">Inativo</span>
                                @endif
                            </button>
                        </td>
                        <td style="padding:10px 16px; text-align:right;">
                            <div style="display:flex; justify-content:flex-end; gap:6px;">
                                <button wire:click="openEdit({{ $lead->id }})" style="padding:4px 10px; font-size:11px; color:rgba(255,255,255,0.4); background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:6px; cursor:pointer;">Editar</button>
                                <button wire:click="delete({{ $lead->id }})" wire:confirm="Remover este lead?" style="padding:4px 10px; font-size:11px; color:#f87171; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); border-radius:6px; cursor:pointer;">Remover</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="padding:40px 16px; text-align:center; font-size:13px; color:rgba(255,255,255,0.3);">Nenhum lead cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px;">{{ $leads->links() }}</div>
    </div>

    {{-- Modal Form --}}
    @if($showForm)
    <div style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px);" wire:click.self="$set('showForm', false)">
        <div style="background:#0f1320; border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:24px; width:100%; max-width:560px; max-height:90vh; overflow-y:auto;">
            <h2 style="font-size:15px; font-weight:700; color:white; margin-bottom:16px; font-family:Syne,sans-serif;">{{ $editingId ? 'Editar Lead' : 'Novo Lead' }}</h2>

            {{-- Toggle Pessoa/Empresa --}}
            <div style="display:flex; gap:8px; margin-bottom:16px;">
                <button wire:click="$set('type', 'person')" style="flex:1; padding:8px; font-size:12px; font-weight:600; border-radius:8px; cursor:pointer; border:1px solid {{ $type === 'person' ? 'rgba(96,165,250,0.3)' : 'rgba(255,255,255,0.08)' }}; background:{{ $type === 'person' ? 'rgba(96,165,250,0.1)' : 'transparent' }}; color:{{ $type === 'person' ? '#60a5fa' : 'rgba(255,255,255,0.3)' }};">
                    Pessoa Física
                </button>
                <button wire:click="$set('type', 'company')" style="flex:1; padding:8px; font-size:12px; font-weight:600; border-radius:8px; cursor:pointer; border:1px solid {{ $type === 'company' ? 'rgba(139,92,246,0.3)' : 'rgba(255,255,255,0.08)' }}; background:{{ $type === 'company' ? 'rgba(139,92,246,0.1)' : 'transparent' }}; color:{{ $type === 'company' ? '#a78bfa' : 'rgba(255,255,255,0.3)' }};">
                    Empresa
                </button>
            </div>

            <div style="display:flex; flex-direction:column; gap:12px;">
                {{-- Dados básicos --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <div>
                        <label style="{{ $labelStyle }}">Nome *</label>
                        <input wire:model="name" type="text" placeholder="Nome do contato" style="{{ $inputStyle }}">
                    </div>
                    <div>
                        <label style="{{ $labelStyle }}">Telefone *</label>
                        <input wire:model="phone" type="tel" placeholder="(11) 99999-9999" style="{{ $inputStyle }}">
                        @error('phone') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <div>
                        <label style="{{ $labelStyle }}">Email</label>
                        <input wire:model="email" type="email" placeholder="contato@email.com" style="{{ $inputStyle }}">
                        @error('email') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="{{ $labelStyle }}">{{ $type === 'company' ? 'CNPJ' : 'CPF' }}</label>
                        <input wire:model="document" type="text" placeholder="{{ $type === 'company' ? '00.000.000/0001-00' : '000.000.000-00' }}" style="{{ $inputStyle }}">
                    </div>
                </div>

                {{-- Campos Empresa --}}
                @if($type === 'company')
                <div style="border-top:1px solid rgba(139,92,246,0.1); padding-top:12px;">
                    <p style="font-size:10px; font-weight:700; color:#a78bfa; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">Dados da Empresa</p>
                    <div>
                        <label style="{{ $labelStyle }}">Razão Social / Nome Fantasia</label>
                        <input wire:model="company_name" type="text" placeholder="Empresa LTDA" style="{{ $inputStyle }}">
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr 80px; gap:10px; margin-top:10px;">
                        <div>
                            <label style="{{ $labelStyle }}">Endereço</label>
                            <input wire:model="address" type="text" placeholder="Rua, número, bairro" style="{{ $inputStyle }}">
                        </div>
                        <div>
                            <label style="{{ $labelStyle }}">Cidade</label>
                            <input wire:model="city" type="text" placeholder="São Paulo" style="{{ $inputStyle }}">
                        </div>
                        <div>
                            <label style="{{ $labelStyle }}">UF</label>
                            <input wire:model="state" type="text" placeholder="SP" maxlength="2" style="{{ $inputStyle }}">
                        </div>
                    </div>
                </div>
                @endif

                {{-- Campos CRM --}}
                @if($customFields->isNotEmpty())
                <div style="border-top:1px solid rgba(178,255,0,0.1); padding-top:12px;">
                    <p style="font-size:10px; font-weight:700; color:#b2ff00; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">Campos do CRM</p>
                    @if($pipelines->count() > 1)
                    <div style="margin-bottom:10px;">
                        <label style="{{ $labelStyle }}">Pipeline</label>
                        <select wire:model="pipelineId" style="{{ $inputStyle }}">
                            @foreach($pipelines as $pl)
                            <option value="{{ $pl->id }}">{{ $pl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        @foreach($customFields as $cf)
                        <div>
                            <label style="{{ $labelStyle }}">{{ $cf->name }}</label>
                            @if($cf->type === 'textarea')
                            <textarea wire:model="customFieldValues.{{ $cf->id }}" rows="2" placeholder="{{ $cf->name }}" style="{{ $inputStyle }} resize:none;"></textarea>
                            @elseif(in_array($cf->type, ['date', 'datetime', 'time']))
                            <input wire:model="customFieldValues.{{ $cf->id }}" type="{{ $cf->type === 'time' ? 'time' : ($cf->type === 'datetime' ? 'datetime-local' : 'date') }}" style="{{ $inputStyle }}">
                            @elseif($cf->type === 'number' || $cf->type === 'currency')
                            <input wire:model="customFieldValues.{{ $cf->id }}" type="number" step="{{ $cf->type === 'currency' ? '0.01' : '1' }}" placeholder="{{ $cf->name }}" style="{{ $inputStyle }}">
                            @else
                            <input wire:model="customFieldValues.{{ $cf->id }}" type="text" placeholder="{{ $cf->name }}" style="{{ $inputStyle }}">
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Tags e Observações --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <div>
                        <label style="{{ $labelStyle }}">Tags (separar por vírgula)</label>
                        <input wire:model="tags" type="text" placeholder="cliente, vip" style="{{ $inputStyle }}">
                    </div>
                    <div>
                        <label style="{{ $labelStyle }}">Observações</label>
                        <input wire:model="notes" type="text" placeholder="Anotações sobre o lead" style="{{ $inputStyle }}">
                    </div>
                </div>

                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input wire:model="is_active" type="checkbox" style="accent-color:#b2ff00;">
                    <span style="font-size:12px; color:rgba(255,255,255,0.6);">Ativo</span>
                </label>
            </div>

            <div style="display:flex; gap:10px; margin-top:18px;">
                <button wire:click="save" style="flex:1; padding:8px; font-size:12px; font-weight:700; color:#111; background:linear-gradient(135deg, #b2ff00, #8fcc00); border:none; border-radius:8px; cursor:pointer;">{{ $editingId ? 'Atualizar' : 'Salvar' }}</button>
                <button wire:click="$set('showForm', false)" style="padding:8px 16px; font-size:12px; color:rgba(255,255,255,0.4); background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; cursor:pointer;">Cancelar</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Import CSV --}}
    @if($showImport)
    <div style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px);" wire:click.self="$set('showImport', false)">
        <div style="background:#0f1320; border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:24px; width:100%; max-width:440px;">
            <h2 style="font-size:15px; font-weight:700; color:white; margin-bottom:16px; font-family:Syne,sans-serif;">Importar CSV</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.35); margin-bottom:12px;">Formato: <strong style="color:rgba(255,255,255,0.6);">Nome, Telefone</strong> (uma linha por contato).</p>
            <input wire:model="csvFile" type="file" accept=".csv,.txt" style="width:100%; padding:8px; font-size:12px; color:rgba(255,255,255,0.6); background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px;">
            @error('csvFile') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
            <div style="display:flex; gap:10px; margin-top:16px;">
                <button wire:click="importCsv" style="flex:1; padding:8px; font-size:12px; font-weight:700; color:#111; background:linear-gradient(135deg, #b2ff00, #8fcc00); border:none; border-radius:8px; cursor:pointer;">Importar</button>
                <button wire:click="$set('showImport', false)" style="padding:8px 16px; font-size:12px; color:rgba(255,255,255,0.4); background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; cursor:pointer;">Cancelar</button>
            </div>
        </div>
    </div>
    @endif
</div>
