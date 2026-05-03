@php
$inputStyle = "width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:10px 14px; font-size:13px; color:white; outline:none; transition:all 0.2s; font-family:inherit; box-sizing:border-box;";
$inputFocus = "onfocus=\"this.style.borderColor='rgba(178,255,0,0.5)'; this.style.boxShadow='0 0 0 3px rgba(178,255,0,0.07)'\" onblur=\"this.style.borderColor='rgba(255,255,255,0.08)'; this.style.boxShadow='none'\"";
$labelStyle = "display:block; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;";
@endphp

<div style="max-width:900px; margin:0 auto; padding:24px 16px; display:flex; flex-direction:column; gap:16px;">

    {{-- Header --}}
    <div style="display:flex; align-items:center; justify-content:space-between;">
        <div>
            <h2 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif; letter-spacing:-0.02em;">Meta WhatsApp</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:2px;">API oficial do WhatsApp Business (Cloud API)</p>
        </div>
    </div>

    {{-- Provider selector --}}
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:20px 24px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, #3b82f680, transparent);"></div>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:14px;">
            <div style="width:2px; height:16px; background:#3b82f6; border-radius:2px;"></div>
            <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Provider ativo</h3>
        </div>
        <p style="font-size:11px; color:rgba(255,255,255,0.3); margin-bottom:14px;">Escolha qual API de WhatsApp esta empresa utiliza para enviar e receber mensagens.</p>

        <div style="display:flex; gap:12px;">
            <button wire:click="switchProvider('evolution')"
                    style="flex:1; padding:14px 16px; border-radius:12px; border:2px solid {{ $whatsapp_provider === 'evolution' ? '#b2ff00' : 'rgba(255,255,255,0.08)' }}; background:{{ $whatsapp_provider === 'evolution' ? 'rgba(178,255,0,0.06)' : 'rgba(255,255,255,0.02)' }}; cursor:pointer; transition:all 0.2s; text-align:left;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                    <svg width="18" height="18" fill="none" stroke="{{ $whatsapp_provider === 'evolution' ? '#b2ff00' : 'rgba(255,255,255,0.3)' }}" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span style="font-size:13px; font-weight:700; color:{{ $whatsapp_provider === 'evolution' ? '#b2ff00' : 'rgba(255,255,255,0.5)' }};">Evolution API</span>
                </div>
                <p style="font-size:10px; color:rgba(255,255,255,0.25);">API não-oficial via instância própria. Requer QR Code.</p>
            </button>

            <button wire:click="switchProvider('meta')"
                    style="flex:1; padding:14px 16px; border-radius:12px; border:2px solid {{ $whatsapp_provider === 'meta' ? '#b2ff00' : 'rgba(255,255,255,0.08)' }}; background:{{ $whatsapp_provider === 'meta' ? 'rgba(178,255,0,0.06)' : 'rgba(255,255,255,0.02)' }}; cursor:pointer; transition:all 0.2s; text-align:left;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                    <svg width="18" height="18" fill="none" stroke="{{ $whatsapp_provider === 'meta' ? '#b2ff00' : 'rgba(255,255,255,0.3)' }}" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
                    </svg>
                    <span style="font-size:13px; font-weight:700; color:{{ $whatsapp_provider === 'meta' ? '#b2ff00' : 'rgba(255,255,255,0.5)' }};">Meta WhatsApp</span>
                </div>
                <p style="font-size:10px; color:rgba(255,255,255,0.25);">API oficial do WhatsApp Business. Sem QR Code.</p>
            </button>
        </div>
    </div>

    {{-- Toggle ativo --}}
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:20px 24px; display:flex; align-items:center; gap:16px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, {{ $is_active ? '#22c55e80' : 'rgba(107,114,128,0.3)' }}, transparent);"></div>
        <div style="width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:{{ $is_active ? 'rgba(34,197,94,0.12)' : 'rgba(255,255,255,0.04)' }}; border:1px solid {{ $is_active ? 'rgba(34,197,94,0.25)' : 'rgba(255,255,255,0.07)' }};">
            <svg width="20" height="20" fill="none" stroke="{{ $is_active ? '#22c55e' : 'rgba(255,255,255,0.25)' }}" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
            </svg>
        </div>
        <div style="flex:1;">
            <p style="font-size:14px; font-weight:700; color:white; font-family:'Syne',sans-serif;">Meta WhatsApp API</p>
            <p style="font-size:11px; color:rgba(255,255,255,0.3); margin-top:2px;">
                @if($phone_display)
                    Telefone: {{ $phone_display }}
                @else
                    Configure as credenciais abaixo
                @endif
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:10px; flex-shrink:0;">
            <span style="font-size:10px; font-weight:700; letter-spacing:0.08em; color:{{ $is_active ? '#22c55e' : 'rgba(255,255,255,0.2)' }};">
                {{ $is_active ? 'ATIVO' : 'INATIVO' }}
            </span>
            <button wire:click="toggleActive"
                    style="position:relative; display:inline-flex; width:48px; height:26px; border-radius:20px; border:none; cursor:pointer; transition:background 0.2s; background:{{ $is_active ? '#22c55e' : 'rgba(255,255,255,0.1)' }};">
                <span style="position:absolute; top:3px; width:20px; height:20px; border-radius:50%; background:white; box-shadow:0 1px 4px rgba(0,0,0,0.3); transition:left 0.2s; left:{{ $is_active ? '25px' : '3px' }};"></span>
            </button>
        </div>
    </div>

    <form wire:submit="save">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;" class="mobile-grid-1">

            {{-- Credenciais --}}
            <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:24px; display:flex; flex-direction:column; gap:14px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                    <div style="width:2px; height:16px; background:#b2ff00; border-radius:2px;"></div>
                    <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Credenciais</h3>
                </div>

                <div>
                    <label style="{{ $labelStyle }}">Phone Number ID *</label>
                    <input wire:model="phone_number_id" type="text" placeholder="Ex: 123456789012345" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                    @error('phone_number_id') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                    <p style="font-size:10px; color:rgba(255,255,255,0.15); margin-top:4px;">Encontrado em Meta Business Suite > WhatsApp > Configurações da API</p>
                </div>

                <div>
                    <label style="{{ $labelStyle }}">WABA ID</label>
                    <input wire:model="whatsapp_business_account_id" type="text" placeholder="Ex: 123456789012345" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                    <p style="font-size:10px; color:rgba(255,255,255,0.15); margin-top:4px;">WhatsApp Business Account ID (opcional)</p>
                </div>

                <div>
                    <label style="{{ $labelStyle }}">Access Token *</label>
                    <input wire:model="access_token" type="password" placeholder="Token permanente do System User" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                    @error('access_token') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label style="{{ $labelStyle }}">Telefone (exibição)</label>
                    <input wire:model="phone_display" type="text" placeholder="Ex: +55 11 99999-9999" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                </div>
            </div>

            {{-- Webhook --}}
            <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:24px; display:flex; flex-direction:column; gap:14px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                    <div style="width:2px; height:16px; background:#f59e0b; border-radius:2px;"></div>
                    <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Webhook</h3>
                </div>

                <div style="background:rgba(245,158,11,0.06); border:1px solid rgba(245,158,11,0.15); border-radius:10px; padding:14px;">
                    <p style="font-size:11px; color:rgba(255,255,255,0.5); margin-bottom:8px;">Configure no Meta Business Suite:</p>

                    <div style="margin-bottom:10px;">
                        <label style="{{ $labelStyle }}">Callback URL</label>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="text" value="{{ $webhookUrl }}" readonly
                                   style="{{ $inputStyle }} background:rgba(0,0,0,0.3); cursor:text; font-family:monospace; font-size:11px;">
                            <button type="button" onclick="navigator.clipboard.writeText('{{ $webhookUrl }}'); this.textContent='Copiado!'; setTimeout(() => this.textContent='Copiar', 1500)"
                                    style="padding:8px 14px; background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.3); border-radius:8px; color:#f59e0b; font-size:11px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                Copiar
                            </button>
                        </div>
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Verify Token</label>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input wire:model="verify_token" type="text" style="{{ $inputStyle }} font-family:monospace; font-size:11px;" {!! $inputFocus !!}>
                            <button type="button" wire:click="generateVerifyToken"
                                    style="padding:8px 14px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:rgba(255,255,255,0.5); font-size:11px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                Gerar
                            </button>
                        </div>
                    </div>
                </div>

                <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.04); border-radius:10px; padding:12px; font-size:11px; color:rgba(255,255,255,0.25); line-height:1.8;">
                    <p style="font-weight:700; color:rgba(255,255,255,0.4); margin-bottom:6px;">Passo a passo:</p>
                    1. Acesse o Meta Business Suite<br>
                    2. Vá em WhatsApp > Configuração<br>
                    3. Em "Webhook", clique em "Editar"<br>
                    4. Cole a URL e o Verify Token acima<br>
                    5. Inscreva-se nos campos: <strong style="color:rgba(255,255,255,0.4);">messages</strong>
                </div>

                {{-- Test Connection --}}
                <button type="button" wire:click="testConnection"
                        style="display:flex; align-items:center; justify-content:center; gap:8px; padding:10px; background:rgba(59,130,246,0.1); border:1px solid rgba(59,130,246,0.25); border-radius:10px; color:#60a5fa; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.2s;"
                        onmouseover="this.style.background='rgba(59,130,246,0.18)'"
                        onmouseout="this.style.background='rgba(59,130,246,0.1)'">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span wire:loading.remove wire:target="testConnection">Testar Conexão</span>
                    <span wire:loading wire:target="testConnection">Testando...</span>
                </button>

                @if($testResult)
                <div style="padding:10px 14px; border-radius:8px; font-size:12px; background:{{ $testStatus === 'success' ? 'rgba(34,197,94,0.08)' : 'rgba(239,68,68,0.08)' }}; border:1px solid {{ $testStatus === 'success' ? 'rgba(34,197,94,0.2)' : 'rgba(239,68,68,0.2)' }}; color:{{ $testStatus === 'success' ? '#22c55e' : '#f87171' }};">
                    {{ $testResult }}
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
