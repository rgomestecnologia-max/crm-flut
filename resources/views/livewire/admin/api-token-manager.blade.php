@php
$inputStyle = "width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:10px 14px; font-size:13px; color:white; outline:none; transition:all 0.2s; font-family:inherit; box-sizing:border-box;";
$inputFocus = "onfocus=\"this.style.borderColor='rgba(178,255,0,0.5)'; this.style.boxShadow='0 0 0 3px rgba(178,255,0,0.07)'\" onblur=\"this.style.borderColor='rgba(255,255,255,0.08)'; this.style.boxShadow='none'\"";
$labelStyle = "display:block; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;";
$cardStyle = "background:linear-gradient(145deg, rgba(17,24,39,0.9) 0%, rgba(11,15,28,0.95) 100%); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:24px; margin-bottom:14px;";
$preStyle = "background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.06); border-radius:10px; padding:12px 16px; font-family:monospace; font-size:12px; color:rgba(255,255,255,0.6); overflow-x:auto; line-height:1.6;";
@endphp

<div style="display:flex; flex-direction:column; gap:14px;">

    {{-- Token gerado (exibido apenas uma vez) --}}
    @if($generatedToken)
    <div style="background:rgba(34,197,94,0.06); border:1px solid rgba(34,197,94,0.2); border-radius:14px; padding:18px 20px;">
        <div style="display:flex; align-items:flex-start; gap:10px; margin-bottom:12px;">
            <svg width="16" height="16" fill="none" stroke="#4ade80" viewBox="0 0 24 24" style="flex-shrink:0; margin-top:2px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p style="font-size:13px; font-weight:700; color:#4ade80;">Token gerado com sucesso!</p>
                <p style="font-size:11px; color:rgba(255,255,255,0.35); margin-top:2px;">Copie agora. Este valor não será exibido novamente por segurança.</p>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:8px;" x-data="{ copied: false }">
            <code style="flex:1; background:rgba(0,0,0,0.4); border:1px solid rgba(34,197,94,0.2); border-radius:8px; padding:10px 14px; font-size:12px; color:#4ade80; font-family:monospace; word-break:break-all; user-select:all;">
                {{ $generatedToken }}
            </code>
            <button @click="navigator.clipboard.writeText('{{ $generatedToken }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                    style="flex-shrink:0; padding:10px 14px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:8px; font-size:11px; font-weight:600; cursor:pointer; transition:all 0.15s;"
                    :style="copied ? 'color:#4ade80;' : 'color:rgba(255,255,255,0.4);'">
                <span x-show="!copied">Copiar</span>
                <span x-show="copied">Copiado!</span>
            </button>
        </div>
        <p style="font-size:10px; color:rgba(255,255,255,0.25); margin-top:10px;">
            Use no header: <code style="color:rgba(255,255,255,0.45); font-family:monospace;">Authorization: Bearer {{ $generatedToken }}</code>
        </p>
    </div>
    @endif

    {{-- Header --}}
    <div style="display:flex; align-items:center; justify-content:space-between;">
        <div>
            <h3 style="font-size:16px; font-weight:800; color:white; font-family:'Syne',sans-serif; letter-spacing:-0.01em;">Tokens de API</h3>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:2px;">Integre formulários externos com o CRM via API REST.</p>
        </div>
        @if(!$showForm)
        <button wire:click="openForm"
                style="display:flex; align-items:center; gap:8px; padding:9px 18px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-size:12px; font-weight:700; border-radius:10px; border:none; cursor:pointer; transition:all 0.2s; box-shadow:0 2px 12px rgba(178,255,0,0.25);"
                onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 20px rgba(178,255,0,0.35)'"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 12px rgba(178,255,0,0.25)'">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Gerar token
        </button>
        @endif
    </div>

    {{-- Formulário de criação --}}
    @if($showForm)
    <div style="{{ $cardStyle }} position:relative; overflow:hidden;">
        <div style="position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, #b2ff0080, #b2ff0020, transparent); border-radius:16px 16px 0 0;"></div>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:18px;">
            <div style="width:2px; height:16px; background:#b2ff00; border-radius:2px;"></div>
            <h4 style="font-size:13px; font-weight:700; color:white;">Novo token</h4>
        </div>

        <div style="margin-bottom:14px;">
            <label style="{{ $labelStyle }}">Nome / Identificação *</label>
            <input wire:model="token_name" type="text" placeholder="ex: Site Institucional, Landing Page Vendas..." style="{{ $inputStyle }}" {!! $inputFocus !!}>
            @error('token_name') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:18px;">
            <div>
                <label style="{{ $labelStyle }}">Pipeline padrão</label>
                <select wire:model.live="pipeline_id" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                    <option value="">— Primeiro ativo —</option>
                    @foreach($pipelines as $pl)
                    <option value="{{ $pl->id }}">{{ $pl->name }}</option>
                    @endforeach
                </select>
                <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:4px;">Pipeline onde o lead vai cair.</p>
            </div>
            <div>
                <label style="{{ $labelStyle }}">Etapa padrão</label>
                <select wire:model="stage_id" style="{{ $inputStyle }} {{ !$pipeline_id ? 'opacity:0.4;' : '' }}" {!! $inputFocus !!} @disabled(!$pipeline_id)>
                    <option value="">— Primeira etapa —</option>
                    @foreach($stages as $st)
                    <option value="{{ $st->id }}">{{ $st->name }}</option>
                    @endforeach
                </select>
                <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:4px;">Etapa onde o card vai ser criado.</p>
            </div>
        </div>

        <div style="display:flex; gap:10px;">
            <button wire:click="generate"
                    style="display:flex; align-items:center; gap:7px; padding:9px 20px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-size:12px; font-weight:700; border-radius:10px; border:none; cursor:pointer; transition:all 0.2s;"
                    onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Gerar token
            </button>
            <button wire:click="$set('showForm', false)"
                    style="padding:9px 16px; font-size:12px; color:rgba(255,255,255,0.35); background:transparent; border:none; cursor:pointer; transition:color 0.15s;"
                    onmouseover="this.style.color='rgba(255,255,255,0.7)'" onmouseout="this.style.color='rgba(255,255,255,0.35)'">
                Cancelar
            </button>
        </div>
    </div>
    @endif

    {{-- Documentação --}}
    <div style="{{ $cardStyle }}"
         x-data="{
             open: false,
             baseUrl: localStorage.getItem('crm_api_base') || window.location.origin,
             get apiUrl() { return this.baseUrl + '/api/leads' },
             saveBase() { localStorage.setItem('crm_api_base', this.baseUrl) }
         }">
        <button @click="open = !open"
                style="display:flex; align-items:center; justify-content:space-between; width:100%; background:transparent; border:none; cursor:pointer; text-align:left; padding:0;">
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="width:2px; height:16px; background:#3b82f6; border-radius:2px;"></div>
                <span style="font-size:13px; font-weight:700; color:white;">Documentação da API</span>
            </div>
            <svg style="transition:transform 0.2s; color:rgba(255,255,255,0.25);" :style="open ? 'transform:rotate(180deg)' : ''" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="open" x-collapse style="margin-top:16px; display:flex; flex-direction:column; gap:14px;">

            <div style="display:flex; align-items:center; gap:8px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:8px; padding:8px 12px;">
                <svg width="12" height="12" fill="none" stroke="rgba(255,255,255,0.3)" viewBox="0 0 24 24" style="flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                <input x-model="baseUrl" @change="saveBase()" type="text" placeholder="https://seu-id.ngrok-free.app"
                       style="flex:1; background:transparent; color:rgba(255,255,255,0.6); outline:none; font-family:monospace; font-size:12px; min-width:0; border:none;">
                <span style="font-size:10px; color:rgba(255,255,255,0.2); flex-shrink:0;">URL base</span>
            </div>

            <div>
                <p style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;">Endpoint</p>
                <code style="{{ $preStyle }} display:block; color:#b2ff00;" x-text="'POST ' + apiUrl"></code>
            </div>

            <div>
                <p style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;">Headers</p>
                <pre style="{{ $preStyle }}">Content-Type: application/json
Authorization: Bearer {seu_token}</pre>
            </div>

            <div>
                <p style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;">Body (JSON)</p>
                @php $customFields = \App\Models\CrmCustomField::orderBy('sort_order')->get(); @endphp
                <pre style="{{ $preStyle }}">{
  <span style="color:#fbbf24;">"name":  "João Silva",</span>       // obrigatório
  <span style="color:#fbbf24;">"phone": "5511999999999",</span>    // obrigatório (só dígitos)
  "email": "joao@site.com",         // opcional
  "notes": "Veio pelo site",        // opcional
@foreach($customFields as $cf)  <span style="{{ $cf->is_required ? 'color:#fbbf24;' : 'color:rgba(255,255,255,0.3);' }}">"{{ $cf->key }}": "...",</span>{{ str_pad('', max(0, 28 - strlen($cf->key))) }}// {{ $cf->type_label }}{{ $cf->is_required ? ' — obrigatório' : '' }}
@endforeach  "pipeline_id": 1,                 // opcional
  "stage_id":    3                  // opcional
}</pre>
                @if($customFields->isNotEmpty())
                <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:6px;">
                    Gerencie em <a href="{{ route('admin.crm.index') }}" style="color:#b2ff00; text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Pipelines CRM → Campos Personalizados</a>.
                </p>
                @endif
            </div>

            <div>
                <p style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;">Resposta (201)</p>
                <pre style="{{ $preStyle }}">{
  "success":    true,
  "created":    true,
  "contact_id": 42,
  "card_id":    7,
  "pipeline":   "Vendas",
  "stage":      "Novo Lead"
}</pre>
            </div>

            <div>
                <p style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;">cURL</p>
                <pre style="{{ $preStyle }} white-space:pre-wrap;"
                     x-text="`curl -X POST ${apiUrl} \\\n  -H &quot;Authorization: Bearer SEU_TOKEN&quot; \\\n  -H &quot;Content-Type: application/json&quot; \\\n  -d '{&quot;name&quot;:&quot;João&quot;,&quot;phone&quot;:&quot;5511999990000&quot;}'`"></pre>
            </div>
        </div>
    </div>

    {{-- Lista de tokens --}}
    @if($tokens->isEmpty())
    <div style="text-align:center; padding:48px 20px; color:rgba(255,255,255,0.15);">
        <svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 12px; opacity:0.3;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
        </svg>
        <p style="font-size:13px;">Nenhum token gerado ainda.</p>
    </div>
    @else
    <div style="display:flex; flex-direction:column; gap:8px;">
        @foreach($tokens as $tk)
        <div style="background:linear-gradient(145deg, rgba(17,24,39,0.8) 0%, rgba(11,15,28,0.9) 100%); border:1px solid {{ $tk->is_active ? 'rgba(255,255,255,0.06)' : 'rgba(255,255,255,0.03)' }}; border-radius:12px; padding:16px 20px; opacity:{{ $tk->is_active ? '1' : '0.5' }};">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                        <span style="font-size:13px; font-weight:600; color:rgba(255,255,255,0.85);">{{ $tk->name }}</span>
                        @if($tk->is_active)
                            <span style="font-size:9px; font-weight:700; padding:2px 7px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">Ativo</span>
                        @else
                            <span style="font-size:9px; font-weight:700; padding:2px 7px; border-radius:20px; background:rgba(239,68,68,0.12); color:#f87171; border:1px solid rgba(239,68,68,0.2);">Revogado</span>
                        @endif
                    </div>
                    <code style="font-size:11px; color:rgba(255,255,255,0.25); font-family:monospace;">{{ $tk->masked_token }}</code>
                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin-top:6px; font-size:11px; color:rgba(255,255,255,0.25);">
                        @if($tk->defaultPipeline)
                            <span style="display:flex; align-items:center; gap:4px;">
                                <span style="width:7px; height:7px; border-radius:50%; background:{{ $tk->defaultPipeline->color }};"></span>
                                {{ $tk->defaultPipeline->name }}
                                @if($tk->defaultStage) · {{ $tk->defaultStage->name }} @endif
                            </span>
                        @else
                            <span>Pipeline: primeiro ativo</span>
                        @endif
                        <span>Criado: {{ $tk->created_at->format('d/m/Y') }}</span>
                        @if($tk->last_used_at)
                            <span>Último uso: {{ $tk->last_used_at->diffForHumans() }}</span>
                        @else
                            <span style="color:rgba(255,255,255,0.15);">Nunca utilizado</span>
                        @endif
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap:4px; flex-shrink:0;">
                    @if($tk->is_active)
                    <button wire:click="revoke({{ $tk->id }})" wire:confirm="Revogar este token? Integrações que usam ele vão parar de funcionar."
                            style="font-size:11px; font-weight:600; color:rgba(251,191,36,0.7); background:rgba(251,191,36,0.06); border:1px solid rgba(251,191,36,0.15); border-radius:7px; padding:5px 10px; cursor:pointer; transition:all 0.15s;"
                            onmouseover="this.style.color='#fbbf24'; this.style.background='rgba(251,191,36,0.12)'"
                            onmouseout="this.style.color='rgba(251,191,36,0.7)'; this.style.background='rgba(251,191,36,0.06)'">
                        Revogar
                    </button>
                    @else
                    <button wire:click="activate({{ $tk->id }})"
                            style="font-size:11px; font-weight:600; color:rgba(34,197,94,0.7); background:rgba(34,197,94,0.06); border:1px solid rgba(34,197,94,0.15); border-radius:7px; padding:5px 10px; cursor:pointer; transition:all 0.15s;"
                            onmouseover="this.style.color='#4ade80'; this.style.background='rgba(34,197,94,0.12)'"
                            onmouseout="this.style.color='rgba(34,197,94,0.7)'; this.style.background='rgba(34,197,94,0.06)'">
                        Reativar
                    </button>
                    @endif
                    <button wire:click="delete({{ $tk->id }})" wire:confirm="Excluir este token permanentemente?"
                            style="width:30px; height:30px; display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.2); background:transparent; border:none; border-radius:7px; cursor:pointer; transition:all 0.15s;"
                            onmouseover="this.style.color='#f87171'; this.style.background='rgba(239,68,68,0.08)'"
                            onmouseout="this.style.color='rgba(255,255,255,0.2)'; this.style.background='transparent'">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
