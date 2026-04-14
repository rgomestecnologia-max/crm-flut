@php
$inputStyle = "width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:10px 14px; font-size:13px; color:white; outline:none; transition:all 0.2s; font-family:monospace; box-sizing:border-box;";
$inputFocus = "onfocus=\"this.style.borderColor='rgba(178,255,0,0.5)'; this.style.boxShadow='0 0 0 3px rgba(178,255,0,0.07)'\" onblur=\"this.style.borderColor='rgba(255,255,255,0.08)'; this.style.boxShadow='none'\"";
$labelStyle = "display:block; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;";
$cardStyle = "background:linear-gradient(145deg, rgba(17,24,39,0.9) 0%, rgba(11,15,28,0.95) 100%); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:24px;";
@endphp

<div style="max-width:680px;">
    {{-- Header --}}
    <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px;">
        <div style="width:48px; height:48px; border-radius:14px; background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.2); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <svg width="24" height="24" fill="#22c55e" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
        </div>
        <div style="flex:1;">
            <h2 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif; letter-spacing:-0.02em;">Configuração Z-API</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:2px;">Integração com WhatsApp</p>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            {{-- Status de conexão --}}
            <span style="font-size:11px; font-weight:700; padding:5px 14px; border-radius:20px; letter-spacing:0.02em;
                         {{ $connectionStatus === 'connected' ? 'background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);' :
                            ($connectionStatus === 'qrcode' ? 'background:rgba(234,179,8,0.12); color:#fbbf24; border:1px solid rgba(234,179,8,0.2);' :
                                                              'background:rgba(239,68,68,0.12); color:#f87171; border:1px solid rgba(239,68,68,0.2);') }}">
                {{ match($connectionStatus) {
                    'connected' => '● Conectado',
                    'qrcode'    => '● Aguardando QR',
                    default     => '● Desconectado'
                } }}
            </span>

            {{-- Toggle Ativo/Inativo --}}
            <button wire:click="toggleActive"
                    title="{{ $isActive ? 'Z-API ativo — clique para desativar' : 'Z-API inativo — clique para ativar' }}"
                    style="display:flex; align-items:center; gap:7px; padding:5px 14px; border-radius:20px; border:none; cursor:pointer; transition:all 0.2s; font-size:11px; font-weight:700; letter-spacing:0.02em;
                           {{ $isActive ? 'background:rgba(178,255,0,0.12); color:#2DD4BF; border:1px solid rgba(178,255,0,0.25);' : 'background:rgba(239,68,68,0.1); color:#f87171; border:1px solid rgba(239,68,68,0.2);' }}"
                    onmouseover="this.style.opacity='0.8'"
                    onmouseout="this.style.opacity='1'">
                <span style="display:inline-block; width:28px; height:16px; border-radius:8px; position:relative; transition:all 0.2s;
                             {{ $isActive ? 'background:#b2ff00;' : 'background:rgba(239,68,68,0.4);' }}">
                    <span style="position:absolute; top:2px; width:12px; height:12px; border-radius:50%; background:white; transition:all 0.2s; box-shadow:0 1px 3px rgba(0,0,0,0.4);
                                 {{ $isActive ? 'left:14px;' : 'left:2px;' }}"></span>
                </span>
                {{ $isActive ? 'Z-API ativo' : 'Z-API inativo' }}
            </button>
        </div>
    </div>

    {{-- Banner de aviso quando inativo --}}
    @unless($isActive)
    <div style="background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.2); border-radius:12px; padding:12px 16px; margin-bottom:16px; display:flex; align-items:center; gap:10px;">
        <svg width="16" height="16" fill="none" stroke="#f87171" viewBox="0 0 24 24" style="flex-shrink:0;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
        </svg>
        <p style="font-size:12px; color:rgba(248,113,113,0.9); margin:0;"><strong style="color:#f87171;">Z-API desativado.</strong> O sistema está usando a Evolution API para envio de mensagens.</p>
    </div>
    @endunless

    {{-- Credenciais --}}
    <div style="{{ $cardStyle }} margin-bottom:16px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, #b2ff0080, #b2ff0020, transparent); border-radius:16px 16px 0 0;"></div>

        <div style="display:flex; align-items:center; gap:8px; margin-bottom:20px;">
            <div style="width:2px; height:16px; background:#b2ff00; border-radius:2px;"></div>
            <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Credenciais da Instância</h3>
        </div>

        <div style="display:flex; flex-direction:column; gap:16px;">
            <div>
                <label style="{{ $labelStyle }}">ID da Instância *</label>
                <input wire:model="instance_id" type="text" placeholder="Ex: 3C2A...F1B0" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                @error('instance_id') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:5px;">Painel Z-API → sua instância → <strong style="color:rgba(255,255,255,0.35);">ID da Instância</strong></p>
            </div>
            <div>
                <label style="{{ $labelStyle }}">Token da Instância {{ !$tokenSaved ? '*' : '' }}</label>
                <input wire:model="token" type="password"
                       placeholder="{{ $tokenSaved ? '••••••••  (em branco = manter)' : 'Cole o token aqui' }}"
                       style="{{ $inputStyle }}" {!! $inputFocus !!}>
                @error('token') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:5px;">Painel Z-API → sua instância → <strong style="color:rgba(255,255,255,0.35);">Token da Instância</strong></p>
            </div>

            {{-- Security Token --}}
            <div style="border-top:1px solid rgba(255,255,255,0.05); padding-top:16px;">
                <label style="{{ $labelStyle }}">Security Token <span style="color:#fbbf24; text-transform:none; font-weight:400;">(obrigatório se aparecer erro "client-token")</span></label>

                <div style="display:flex; align-items:flex-start; gap:10px; background:rgba(234,179,8,0.06); border:1px solid rgba(234,179,8,0.2); border-radius:10px; padding:12px 14px; margin-bottom:10px;">
                    <svg width="14" height="14" fill="none" stroke="#fbbf24" viewBox="0 0 24 24" style="flex-shrink:0; margin-top:1px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <div style="font-size:11px; color:rgba(251,191,36,0.8);">
                        <p style="font-weight:700; color:#fbbf24; margin-bottom:4px;">Onde encontrar no Z-API:</p>
                        <ol style="padding-left:0; list-style:none; display:flex; flex-direction:column; gap:2px;">
                            <li>1. Acesse <strong style="color:#fbbf24;">developer.z-api.io</strong></li>
                            <li>2. Clique no seu <strong style="color:#fbbf24;">perfil</strong> (canto superior direito)</li>
                            <li>3. Vá em <strong style="color:#fbbf24;">Segurança</strong></li>
                            <li>4. Copie o <strong style="color:#fbbf24;">Security Token</strong></li>
                        </ol>
                    </div>
                </div>

                <input wire:model="client_token" type="password" placeholder="Cole o Security Token aqui"
                       style="{{ $inputStyle }} border-color:rgba(234,179,8,0.3);"
                       onfocus="this.style.borderColor='rgba(234,179,8,0.6)'; this.style.boxShadow='0 0 0 3px rgba(234,179,8,0.07)'"
                       onblur="this.style.borderColor='rgba(234,179,8,0.3)'; this.style.boxShadow='none'">
                @error('client_token') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:10px; margin-top:20px;">
            <button wire:click="save" wire:loading.attr="disabled"
                    style="display:flex; align-items:center; gap:7px; padding:9px 20px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-size:12px; font-weight:700; border-radius:10px; border:none; cursor:pointer; transition:all 0.2s; box-shadow:0 2px 12px rgba(178,255,0,0.25);"
                    onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 20px rgba(178,255,0,0.35)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 12px rgba(178,255,0,0.25)'">
                <span wire:loading.remove wire:target="save">Salvar Credenciais</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </button>
            <button wire:click="testConnection" wire:loading.attr="disabled"
                    style="display:flex; align-items:center; gap:7px; padding:9px 16px; background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.5); font-size:12px; border-radius:10px; border:1px solid rgba(255,255,255,0.08); cursor:pointer; transition:all 0.2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.color='rgba(255,255,255,0.8)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.color='rgba(255,255,255,0.5)'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                </svg>
                <span wire:loading.remove wire:target="testConnection">Testar Conexão</span>
                <span wire:loading wire:target="testConnection">Testando...</span>
            </button>
        </div>
    </div>

    {{-- Webhook --}}
    <div style="{{ $cardStyle }}">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
            <div style="width:2px; height:16px; background:#b2ff00; border-radius:2px;"></div>
            <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">URL do Webhook</h3>
            <span style="font-size:10px; color:rgba(255,255,255,0.25); margin-left:2px;">configure no painel Z-API</span>
        </div>

        <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.12); border-radius:10px; padding:12px 16px; display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px;">
            <div style="min-width:0;">
                <p style="font-size:10px; color:rgba(255,255,255,0.25); margin-bottom:4px;">Ao receber mensagem / Status de envio</p>
                <code style="font-size:12px; color:#b2ff00; font-family:monospace; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ url('/api/webhook/zapi') }}</code>
            </div>
            <button onclick="navigator.clipboard.writeText('{{ url('/api/webhook/zapi') }}').then(() => { this.textContent = 'Copiado!'; setTimeout(() => this.textContent = 'Copiar', 2000); })"
                    style="font-size:11px; font-weight:600; color:rgba(255,255,255,0.4); background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:7px; padding:5px 12px; cursor:pointer; white-space:nowrap; flex-shrink:0; transition:all 0.15s;"
                    onmouseover="this.style.color='#b2ff00'; this.style.borderColor='rgba(178,255,0,0.3)'"
                    onmouseout="this.style.color='rgba(255,255,255,0.4)'; this.style.borderColor='rgba(255,255,255,0.08)'">Copiar</button>
        </div>

        <div>
            <p style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:8px;">Como configurar no Z-API:</p>
            <ol style="list-style:none; padding:0; display:flex; flex-direction:column; gap:5px;">
                @foreach(['Acesse o painel Z-API → sua instância', 'Clique em <strong style="color:rgba(255,255,255,0.5)">Webhooks</strong>', 'Cole a URL acima nos campos <strong style="color:rgba(255,255,255,0.5)">Ao receber</strong> e <strong style="color:rgba(255,255,255,0.5)">Status</strong>', 'Salve e clique em <strong style="color:rgba(255,255,255,0.5)">Testar Webhook</strong> no Z-API'] as $i => $step)
                <li style="display:flex; gap:10px; font-size:11px; color:rgba(255,255,255,0.3);">
                    <span style="font-weight:800; color:#b2ff00; flex-shrink:0;">{{ $i + 1 }}.</span>
                    <span>{!! $step !!}</span>
                </li>
                @endforeach
            </ol>
        </div>
    </div>
</div>
