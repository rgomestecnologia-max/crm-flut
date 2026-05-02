@php
$inputStyle = "width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:9px 14px; font-size:13px; color:white; outline:none; transition:all 0.2s; font-family:inherit; box-sizing:border-box;";
$inputFocus = "onfocus=\"this.style.borderColor='rgba(178,255,0,0.5)'; this.style.boxShadow='0 0 0 3px rgba(178,255,0,0.07)'\" onblur=\"this.style.borderColor='rgba(255,255,255,0.08)'; this.style.boxShadow='none'\"";
$labelStyle = "display:block; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;";
@endphp

<div>
    {{-- Header --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h2 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif; letter-spacing:-0.02em;">Configurações Globais</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:2px;">Configurações compartilhadas entre todas as empresas</p>
        </div>
    </div>

    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9) 0%, rgba(11,15,28,0.95) 100%); border:1px solid rgba(255,255,255,0.07); border-radius:16px; padding:24px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, #b2ff0080, #b2ff0020, transparent); border-radius:16px 16px 0 0;"></div>

        <form wire:submit="save">
            {{-- Gemini API --}}
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:20px;">
                <div style="width:2px; height:16px; background:#b2ff00; border-radius:2px;"></div>
                <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Google Gemini (IA)</h3>
            </div>

            <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:10px; padding:12px 14px; margin-bottom:20px;">
                <div style="display:flex; align-items:flex-start; gap:10px;">
                    <svg width="16" height="16" fill="none" stroke="#b2ff00" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0; margin-top:1px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p style="font-size:11px; color:rgba(255,255,255,0.55); line-height:1.5;">
                        Estes valores são usados por <strong style="color:rgba(255,255,255,0.8);">todas as empresas</strong> do sistema. Cada empresa só configura os prompts e ativa/desativa a IA — a chave e o modelo vêm daqui.
                    </p>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;" class="mobile-grid-1">
                <div>
                    <label style="{{ $labelStyle }}">Chave API Gemini {{ $keyAlreadySaved ? '(em branco = manter)' : '*' }}</label>
                    <input wire:model="gemini_api_key" type="password"
                           placeholder="{{ $keyAlreadySaved ? '••••••••••••••••••' : 'AIza...' }}"
                           style="{{ $inputStyle }} font-family:monospace;" {!! $inputFocus !!}>
                    <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:4px;">Obtenha em aistudio.google.com</p>
                    @error('gemini_api_key') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="{{ $labelStyle }}">Modelo</label>
                    <select wire:model="gemini_model" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                        <option value="gemini-2.5-flash">Gemini 2.5 Flash (recomendado)</option>
                        <option value="gemini-2.5-pro">Gemini 2.5 Pro (mais inteligente)</option>
                        <option value="gemini-2.0-flash">Gemini 2.0 Flash</option>
                        <option value="gemini-2.0-flash-lite">Gemini 2.0 Flash Lite</option>
                    </select>
                </div>
            </div>

            {{-- SendGrid --}}
            <div style="margin-top:28px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.06);">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:20px;">
                    <div style="width:2px; height:16px; background:#3b82f6; border-radius:2px;"></div>
                    <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">SendGrid (Disparo de Email)</h3>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;" class="mobile-grid-1">
                    <div>
                        <label style="{{ $labelStyle }}">API Key SendGrid {{ $sendgridKeySaved ? '(em branco = manter)' : '' }}</label>
                        <input wire:model="sendgrid_api_key" type="password"
                               placeholder="{{ $sendgridKeySaved ? '••••••••••••••••••' : 'SG.xxxxxxxx...' }}"
                               style="{{ $inputStyle }} font-family:monospace;" {!! $inputFocus !!}>
                    </div>
                    <div>
                        <label style="{{ $labelStyle }}">Email remetente</label>
                        <input wire:model="sendgrid_from_email" type="email"
                               placeholder="contato@suaempresa.com.br"
                               style="{{ $inputStyle }}" {!! $inputFocus !!}>
                        @error('sendgrid_from_email') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label style="{{ $labelStyle }}">Nome remetente</label>
                    <input wire:model="sendgrid_from_name" type="text"
                           placeholder="Sua Empresa"
                           style="{{ $inputStyle }} max-width:300px;" {!! $inputFocus !!}>
                </div>
            </div>

            <div style="margin-top:20px;">
                <button type="submit"
                        style="padding:9px 24px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-size:12px; font-weight:700; border-radius:9px; border:none; cursor:pointer; transition:all 0.2s;"
                        onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    Salvar configurações globais
                </button>
            </div>
        </form>
    </div>
</div>
