@php
$inputStyle = "width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:10px 14px; font-size:13px; color:white; outline:none; transition:all 0.2s; font-family:inherit; box-sizing:border-box;";
$inputFocus = "onfocus=\"this.style.borderColor='rgba(178,255,0,0.5)'; this.style.boxShadow='0 0 0 3px rgba(178,255,0,0.07)'\" onblur=\"this.style.borderColor='rgba(255,255,255,0.08)'; this.style.boxShadow='none'\"";
$labelStyle = "display:block; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;";
@endphp

<div style="display:flex; flex-direction:column; gap:16px;">

    {{-- Toggle principal --}}
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9) 0%, rgba(11,15,28,0.95) 100%); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:20px 24px; display:flex; align-items:center; gap:16px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, {{ $is_active ? '#b2ff0080' : 'rgba(107,114,128,0.3)' }}, transparent); border-radius:16px 16px 0 0;"></div>
        <div style="width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:{{ $is_active ? 'rgba(178,255,0,0.12)' : 'rgba(255,255,255,0.04)' }}; border:1px solid {{ $is_active ? 'rgba(178,255,0,0.25)' : 'rgba(255,255,255,0.07)' }};">
            <svg width="20" height="20" fill="none" stroke="{{ $is_active ? '#b2ff00' : 'rgba(255,255,255,0.25)' }}" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/>
            </svg>
        </div>
        <div style="flex:1;">
            <p style="font-size:14px; font-weight:700; color:white; font-family:'Syne',sans-serif;">Menu de Chatbot</p>
            <p style="font-size:11px; color:rgba(255,255,255,0.3); margin-top:2px;">Quando ativo, o primeiro contato recebe automaticamente um menu numerado para escolher o setor.</p>
        </div>
        <div style="display:flex; align-items:center; gap:10px; flex-shrink:0;">
            <span style="font-size:10px; font-weight:700; letter-spacing:0.08em; color:{{ $is_active ? '#b2ff00' : 'rgba(255,255,255,0.2)' }};">
                {{ $is_active ? 'ATIVO' : 'INATIVO' }}
            </span>
            <button wire:click="toggleActive"
                    style="position:relative; display:inline-flex; width:48px; height:26px; border-radius:20px; border:none; cursor:pointer; transition:background 0.2s; background:{{ $is_active ? '#b2ff00' : 'rgba(255,255,255,0.1)' }};">
                <span style="position:absolute; top:3px; width:20px; height:20px; border-radius:50%; background:white; box-shadow:0 1px 4px rgba(0,0,0,0.3); transition:left 0.2s; left:{{ $is_active ? '25px' : '3px' }};"></span>
            </button>
        </div>
    </div>

    {{-- Toggle responder em grupos --}}
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:14px; padding:16px 20px; margin-bottom:16px; display:flex; align-items:center; gap:14px;">
        <button type="button" wire:click="$toggle('reply_in_groups')"
                style="position:relative; display:inline-flex; width:44px; height:24px; border-radius:20px; border:none; cursor:pointer; transition:background 0.2s; flex-shrink:0; background:{{ $reply_in_groups ? '#b2ff00' : 'rgba(255,255,255,0.1)' }};">
            <span style="position:absolute; top:2px; width:20px; height:20px; border-radius:50%; background:white; box-shadow:0 1px 4px rgba(0,0,0,0.3); transition:left 0.2s; left:{{ $reply_in_groups ? '22px' : '2px' }};"></span>
        </button>
        <div>
            <p style="font-size:13px; font-weight:600; color:rgba(255,255,255,0.8);">Responder em grupos</p>
            <p style="font-size:11px; color:rgba(255,255,255,0.35);">Quando ativado, o chatbot também envia mensagens dentro de grupos do WhatsApp. Por padrão fica desativado.</p>
        </div>
    </div>

    <form wire:submit="save">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;" class="mobile-grid-1">

            {{-- Configurações --}}
            <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9) 0%, rgba(11,15,28,0.95) 100%); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:24px; display:flex; flex-direction:column; gap:14px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                    <div style="width:2px; height:16px; background:#b2ff00; border-radius:2px;"></div>
                    <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Configurações</h3>
                </div>
                <div>
                    <label style="{{ $labelStyle }}">Nome da empresa *</label>
                    <input wire:model.live="company_name" type="text" placeholder="Ex: RSG Group" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                    @error('company_name') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="{{ $labelStyle }}">Boas-vindas * <span style="text-transform:none; font-weight:400; color:rgba(255,255,255,0.2);">use {nome} e {empresa}</span></label>
                    <textarea wire:model.live="welcome_template" rows="3"
                              placeholder="Olá {nome}! Seja bem-vindo(a) à {empresa}."
                              style="{{ $inputStyle }} resize:none; line-height:1.6;" {!! $inputFocus !!}></textarea>
                    @error('welcome_template') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="{{ $labelStyle }}">Texto acima do menu *</label>
                    <input wire:model.live="menu_prompt" type="text" placeholder="Digite o *número* do setor:" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                    @error('menu_prompt') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="{{ $labelStyle }}">Após seleção <span style="text-transform:none; font-weight:400; color:rgba(255,255,255,0.2);">use {departamento}</span></label>
                    <input wire:model="after_selection_message" type="text" placeholder="Perfeito! Direcionando você para *{departamento}*. 😊" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                    @error('after_selection_message') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="{{ $labelStyle }}">Opção inválida *</label>
                    <input wire:model="invalid_option_message" type="text" placeholder="Opção inválida. Digite apenas o número do setor." style="{{ $inputStyle }}" {!! $inputFocus !!}>
                    @error('invalid_option_message') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Preview --}}
            <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9) 0%, rgba(11,15,28,0.95) 100%); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:24px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                    <div style="width:2px; height:16px; background:#22c55e; border-radius:2px;"></div>
                    <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Pré-visualização</h3>
                </div>
                <div style="background:#0b141a; border-radius:12px; padding:16px; min-height:180px; display:flex; align-items:flex-start;">
                    <div style="max-width:88%;">
                        <div style="background:#202c33; border-radius:8px 8px 8px 0; padding:10px 14px; font-size:13px; color:#e9edef; line-height:1.6; white-space:pre-wrap; word-break:break-words; box-shadow:0 1px 4px rgba(0,0,0,0.3);">
                            @php
                                $previewName    = 'Rogério Gomes';
                                $previewCompany = $company_name ?: 'sua empresa';
                                $previewWelcome = str_replace(['{nome}', '{empresa}'], [$previewName, $previewCompany], $welcome_template ?: '');
                                $lines = array_filter([$previewWelcome]);
                                if ($menu_prompt) $lines[] = "\n" . $menu_prompt;
                                foreach ($departments as $i => $dept) {
                                    $lines[] = ($i + 1) . ' - ' . $dept->name;
                                }
                                echo nl2br(e(implode("\n", $lines)));
                            @endphp
                        </div>
                        <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:4px; text-align:right;">Chatbot • agora</p>
                    </div>
                </div>

                @if($departments->isEmpty())
                <div style="margin-top:10px; display:flex; align-items:flex-start; gap:8px; background:rgba(234,179,8,0.06); border:1px solid rgba(234,179,8,0.2); border-radius:8px; padding:10px 12px; font-size:11px; color:rgba(251,191,36,0.8);">
                    <svg width="13" height="13" fill="none" stroke="#fbbf24" viewBox="0 0 24 24" style="flex-shrink:0; margin-top:1px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
                    </svg>
                    Nenhum departamento ativo encontrado. Cadastre departamentos para o menu funcionar.
                </div>
                @else
                <div style="margin-top:10px; display:flex; align-items:flex-start; gap:8px; background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.12); border-radius:8px; padding:10px 12px; font-size:11px; color:rgba(255,255,255,0.3);">
                    <svg width="13" height="13" fill="none" stroke="#b2ff00" viewBox="0 0 24 24" style="flex-shrink:0; margin-top:1px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    O menu usa os <strong style="color:rgba(255,255,255,0.5);">{{ $departments->count() }} departamentos ativos</strong> em ordem alfabética.
                </div>
                @endif
            </div>

        </div>

        {{-- Save --}}
        <div style="display:flex; justify-content:flex-end;">
            <button type="submit"
                    style="display:flex; align-items:center; gap:8px; padding:10px 24px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-size:13px; font-weight:700; border-radius:11px; border:none; cursor:pointer; transition:all 0.2s; box-shadow:0 2px 16px rgba(178,255,0,0.3);"
                    onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 24px rgba(178,255,0,0.4)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 16px rgba(178,255,0,0.3)'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span wire:loading.remove wire:target="save">Salvar configurações</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </button>
        </div>

    </form>
</div>
