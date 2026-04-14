<div style="padding:28px; max-width:960px; margin:0 auto; display:flex; flex-direction:column; gap:24px;"
     @if(in_array($connectionStatus, ['connecting', 'close'])) wire:poll.3s="pollQrCode" @endif>

    {{-- Header --}}
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px;">
        <div>
            <h1 style="font-family:'Syne',sans-serif; font-size:20px; font-weight:800; color:white; margin:0;">
                Evolution API
            </h1>
            <p style="font-size:12px; color:rgba(255,255,255,0.3); margin:4px 0 0;">
                Integração WhatsApp via Evolution API v2 — instância, QR Code e webhook
            </p>
        </div>
        {{-- Status badge --}}
        @php
            $statusColor = match($connectionStatus) {
                'open'         => '#22c55e',
                'connecting'   => '#f59e0b',
                'close',
                'disconnected' => '#ef4444',
                default        => '#6b7280',
            };
            $statusLabel = match($connectionStatus) {
                'open'         => 'Conectado',
                'connecting'   => 'Conectando',
                'close'        => 'Desconectado',
                'disconnected' => 'Desconectado',
                default        => 'Desconhecido',
            };
        @endphp
        <div style="display:flex; align-items:center; gap:8px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:12px; padding:10px 16px;">
            <span style="width:8px; height:8px; border-radius:50%; background:{{ $statusColor }}; display:inline-block; box-shadow:0 0 6px {{ $statusColor }};"></span>
            <span style="font-size:12px; font-weight:600; color:{{ $statusColor }};">{{ $statusLabel }}</span>
            @if($profileName)
                <span style="font-size:11px; color:rgba(255,255,255,0.3);">· {{ $profileName }}</span>
            @endif
            @if($phoneNumber)
                <span style="font-size:11px; color:rgba(255,255,255,0.2);">+{{ $phoneNumber }}</span>
            @endif
        </div>
    </div>

    {{-- Grid: Configuração + QR Code --}}
    <div style="display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start;">

        {{-- ── Coluna esquerda: formulário ── --}}
        <div style="display:flex; flex-direction:column; gap:16px;">

            {{-- Card: Servidor --}}
            <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.07); border-radius:16px; padding:20px;">
                <h2 style="font-size:12px; font-weight:700; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.08em; margin:0 0 16px;">
                    Servidor
                </h2>

                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div>
                        <label style="font-size:11px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">URL do Servidor</label>
                        <input wire:model="server_url"
                               type="url"
                               placeholder="https://evolution.seudominio.com"
                               style="width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:9px 12px; font-size:13px; color:white; outline:none; box-sizing:border-box;"
                               onfocus="this.style.borderColor='rgba(178,255,0,0.5)'"
                               onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
                        @error('server_url')
                            <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label style="font-size:11px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Global API Key</label>
                        <input wire:model="global_api_key"
                               type="password"
                               placeholder="Chave global do servidor Evolution"
                               style="width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:9px 12px; font-size:13px; color:white; outline:none; box-sizing:border-box;"
                               onfocus="this.style.borderColor='rgba(178,255,0,0.5)'"
                               onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
                        @error('global_api_key')
                            <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label style="font-size:11px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Nome da Instância</label>
                        <input wire:model="instance_name"
                               type="text"
                               placeholder="crm-whatsapp"
                               style="width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:9px 12px; font-size:13px; color:white; outline:none; box-sizing:border-box;"
                               onfocus="this.style.borderColor='rgba(178,255,0,0.5)'"
                               onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
                        @error('instance_name')
                            <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($instanceApiKey)
                    <div>
                        <label style="font-size:11px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Instance API Key (gerada)</label>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <code style="flex:1; font-size:11px; color:#b2ff00; background:rgba(178,255,0,0.06); border:1px solid rgba(178,255,0,0.15); border-radius:8px; padding:8px 10px; word-break:break-all; font-family:monospace;">{{ $instanceApiKey }}</code>
                        </div>
                    </div>
                    @endif
                </div>

                <div style="display:flex; gap:8px; margin-top:16px;">
                    <button wire:click="saveConfig" wire:loading.attr="disabled"
                            style="flex:1; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; border:none; border-radius:10px; padding:9px 16px; font-size:12px; font-weight:600; cursor:pointer; transition:opacity 0.15s;"
                            onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                        Salvar Configuração
                    </button>
                    <button wire:click="testServer" wire:loading.attr="disabled"
                            style="background:rgba(255,255,255,0.04); color:rgba(255,255,255,0.6); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:9px 14px; font-size:12px; font-weight:500; cursor:pointer; transition:all 0.15s;"
                            onmouseover="this.style.background='rgba(255,255,255,0.07)'; this.style.color='white'"
                            onmouseout="this.style.background='rgba(255,255,255,0.04)'; this.style.color='rgba(255,255,255,0.6)'">
                        Testar
                    </button>
                </div>
            </div>

            {{-- Card: Instância --}}
            <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.07); border-radius:16px; padding:20px;">
                <h2 style="font-size:12px; font-weight:700; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.08em; margin:0 0 16px;">
                    Gerenciar Instância
                </h2>

                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <button wire:click="createInstance" wire:loading.attr="disabled"
                            style="background:rgba(178,255,0,0.1); color:#b2ff00; border:1px solid rgba(178,255,0,0.2); border-radius:10px; padding:8px 14px; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.15s; display:flex; align-items:center; gap:6px;"
                            onmouseover="this.style.background='rgba(178,255,0,0.18)'" onmouseout="this.style.background='rgba(178,255,0,0.1)'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Criar Instância
                    </button>

                    <button wire:click="connectInstance" wire:loading.attr="disabled"
                            style="background:rgba(34,197,94,0.1); color:#22c55e; border:1px solid rgba(34,197,94,0.2); border-radius:10px; padding:8px 14px; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.15s; display:flex; align-items:center; gap:6px;"
                            onmouseover="this.style.background='rgba(34,197,94,0.18)'" onmouseout="this.style.background='rgba(34,197,94,0.1)'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                        </svg>
                        Conectar / QR Code
                    </button>

                    <button wire:click="checkStatus" wire:loading.attr="disabled"
                            style="background:rgba(255,255,255,0.04); color:rgba(255,255,255,0.6); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:8px 14px; font-size:12px; font-weight:500; cursor:pointer; transition:all 0.15s; display:flex; align-items:center; gap:6px;"
                            onmouseover="this.style.background='rgba(255,255,255,0.07)'; this.style.color='white'"
                            onmouseout="this.style.background='rgba(255,255,255,0.04)'; this.style.color='rgba(255,255,255,0.6)'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Verificar Status
                    </button>

                    <button wire:click="restartInstance" wire:loading.attr="disabled"
                            style="background:rgba(245,158,11,0.1); color:#f59e0b; border:1px solid rgba(245,158,11,0.2); border-radius:10px; padding:8px 14px; font-size:12px; font-weight:500; cursor:pointer; transition:all 0.15s; display:flex; align-items:center; gap:6px;"
                            onmouseover="this.style.background='rgba(245,158,11,0.18)'" onmouseout="this.style.background='rgba(245,158,11,0.1)'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m0 0a8.001 8.001 0 0115.356 2M20 20v-5h-.581m0 0a8.003 8.003 0 01-15.357-2"/>
                        </svg>
                        Reiniciar
                    </button>

                    <button wire:click="logoutInstance" wire:loading.attr="disabled"
                            style="background:rgba(239,68,68,0.1); color:#f87171; border:1px solid rgba(239,68,68,0.2); border-radius:10px; padding:8px 14px; font-size:12px; font-weight:500; cursor:pointer; transition:all 0.15s; display:flex; align-items:center; gap:6px;"
                            onmouseover="this.style.background='rgba(239,68,68,0.18)'" onmouseout="this.style.background='rgba(239,68,68,0.1)'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Desconectar
                    </button>
                </div>

                {{-- Loading indicator --}}
                <div wire:loading style="margin-top:12px; display:flex; align-items:center; gap:8px; color:rgba(255,255,255,0.3); font-size:12px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="animation:spin 1s linear infinite;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Aguardando...
                </div>
            </div>

            {{-- Card: Configurações da Instância --}}
            <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.07); border-radius:16px; padding:20px;">
                <h2 style="font-size:12px; font-weight:700; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.08em; margin:0 0 16px;">
                    Configurações da Instância
                </h2>

                <div style="display:flex; flex-direction:column; gap:10px;">
                    @foreach([
                        ['groups_ignore',  'Ignorar mensagens de grupos'],
                        ['always_online',  'Manter sempre online'],
                        ['read_messages',  'Marcar mensagens como lidas automaticamente'],
                        ['reject_call',    'Rejeitar chamadas de voz/vídeo'],
                    ] as [$field, $label])
                    <label style="display:flex; align-items:center; justify-content:space-between; cursor:pointer; padding:10px 12px; background:rgba(255,255,255,0.02); border-radius:10px; border:1px solid rgba(255,255,255,0.05); transition:background 0.15s;"
                           onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background='rgba(255,255,255,0.02)'">
                        <span style="font-size:12px; color:rgba(255,255,255,0.6);">{{ $label }}</span>
                        <input type="checkbox" wire:model="{{ $field }}"
                               style="width:14px; height:14px; accent-color:#b2ff00; cursor:pointer;">
                    </label>
                    @endforeach

                    <div x-data x-show="{{ $reject_call ? 'true' : 'false' }}" style="padding:0 2px;">
                        <label style="font-size:11px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Mensagem ao rejeitar chamada</label>
                        <input wire:model="msg_call" type="text"
                               placeholder="Ex: No momento não posso atender, envie uma mensagem."
                               style="width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:9px 12px; font-size:12px; color:white; outline:none; box-sizing:border-box;"
                               onfocus="this.style.borderColor='rgba(178,255,0,0.5)'"
                               onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
                    </div>
                </div>

                <button wire:click="saveSettings" wire:loading.attr="disabled"
                        style="margin-top:14px; background:rgba(178,255,0,0.1); color:#b2ff00; border:1px solid rgba(178,255,0,0.2); border-radius:10px; padding:9px 18px; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.15s;"
                        onmouseover="this.style.background='rgba(178,255,0,0.18)'" onmouseout="this.style.background='rgba(178,255,0,0.1)'">
                    Salvar na Instância
                </button>
            </div>

            {{-- Card: Webhook --}}
            <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.07); border-radius:16px; padding:20px;">
                <h2 style="font-size:12px; font-weight:700; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.08em; margin:0 0 16px;">
                    Webhook
                </h2>

                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div style="background:rgba(178,255,0,0.05); border:1px solid rgba(178,255,0,0.15); border-radius:10px; padding:12px 14px;">
                        <p style="font-size:11px; color:rgba(255,255,255,0.4); margin:0 0 4px;">URL para configurar na Evolution API:</p>
                        <code style="font-size:12px; color:#b2ff00; font-family:monospace; word-break:break-all;">{{ rtrim(config('app.url'), '/') . '/api/webhook/evolution' }}</code>
                    </div>

                    <div style="display:flex; gap:8px;">
                        <button wire:click="setupWebhook" wire:loading.attr="disabled"
                                style="background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; border:none; border-radius:10px; padding:9px 16px; font-size:12px; font-weight:600; cursor:pointer; transition:opacity 0.15s; display:flex; align-items:center; gap:6px;"
                                onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Configurar Webhook Automaticamente
                        </button>

                        <button wire:click="loadWebhookInfo" wire:loading.attr="disabled"
                                style="background:rgba(255,255,255,0.04); color:rgba(255,255,255,0.5); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:9px 14px; font-size:12px; cursor:pointer; transition:all 0.15s;"
                                onmouseover="this.style.background='rgba(255,255,255,0.07)'; this.style.color='white'"
                                onmouseout="this.style.background='rgba(255,255,255,0.04)'; this.style.color='rgba(255,255,255,0.5)'">
                            Ver Configuração Atual
                        </button>
                    </div>

                    @if($webhookInfo)
                    <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:10px; padding:12px 14px;">
                        <p style="font-size:11px; color:rgba(255,255,255,0.4); margin:0 0 8px; font-weight:600;">Webhook atual na instância:</p>
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <div style="display:flex; gap:8px; align-items:center;">
                                <span style="font-size:10px; color:rgba(255,255,255,0.3); min-width:50px;">Status</span>
                                <span style="font-size:11px; color:{{ ($webhookInfo['enabled'] ?? false) ? '#22c55e' : '#f87171' }}; font-weight:600;">
                                    {{ ($webhookInfo['enabled'] ?? false) ? 'Ativo' : 'Inativo' }}
                                </span>
                            </div>
                            @if(!empty($webhookInfo['url']))
                            <div style="display:flex; gap:8px; align-items:flex-start;">
                                <span style="font-size:10px; color:rgba(255,255,255,0.3); min-width:50px; padding-top:1px;">URL</span>
                                <code style="font-size:11px; color:#b2ff00; font-family:monospace; word-break:break-all;">{{ $webhookInfo['url'] }}</code>
                            </div>
                            @endif
                            @if(!empty($webhookInfo['events']))
                            <div style="display:flex; gap:8px; align-items:flex-start;">
                                <span style="font-size:10px; color:rgba(255,255,255,0.3); min-width:50px; padding-top:2px;">Eventos</span>
                                <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                    @foreach((array)$webhookInfo['events'] as $ev)
                                    <span style="font-size:10px; background:rgba(178,255,0,0.1); color:#b2ff00; border-radius:4px; padding:2px 6px; font-family:monospace;">{{ $ev }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ── Coluna direita: QR Code + Info ── --}}
        <div style="position:sticky; top:24px; display:flex; flex-direction:column; gap:16px;">

            {{-- QR Code --}}
            <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.07); border-radius:16px; padding:20px; text-align:center;">
                <h2 style="font-size:12px; font-weight:700; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.08em; margin:0 0 16px; text-align:left;">
                    QR Code
                </h2>

                @if($qrBase64)
                    <div style="background:white; border-radius:12px; padding:12px; display:inline-block; margin-bottom:12px;">
                        <img src="{{ $qrBase64 }}" alt="QR Code WhatsApp"
                             style="width:220px; height:220px; display:block;">
                    </div>
                    @if($pairingCode)
                    <div style="background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:10px; padding:10px 14px; margin-bottom:8px;">
                        <p style="font-size:10px; color:rgba(255,255,255,0.4); margin:0 0 4px;">Código de pareamento alternativo:</p>
                        <p style="font-size:22px; font-weight:800; color:#b2ff00; letter-spacing:0.15em; font-family:'Syne',sans-serif; margin:0;">{{ $pairingCode }}</p>
                    </div>
                    @endif
                    <p style="font-size:11px; color:rgba(255,255,255,0.3); margin:0;">
                        Escaneie com o WhatsApp → Dispositivos conectados → Conectar dispositivo
                    </p>
                    <button wire:click="checkStatus"
                            style="margin-top:12px; width:100%; background:rgba(34,197,94,0.1); color:#22c55e; border:1px solid rgba(34,197,94,0.2); border-radius:10px; padding:9px; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.15s;"
                            onmouseover="this.style.background='rgba(34,197,94,0.18)'" onmouseout="this.style.background='rgba(34,197,94,0.1)'">
                        Já escaneei — Verificar Conexão
                    </button>

                @elseif($connectionStatus === 'open')
                    <div style="padding:24px 0; display:flex; flex-direction:column; align-items:center; gap:10px;">
                        <div style="width:60px; height:60px; border-radius:50%; background:rgba(34,197,94,0.1); border:2px solid rgba(34,197,94,0.3); display:flex; align-items:center; justify-content:center;">
                            <svg width="28" height="28" fill="none" stroke="#22c55e" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p style="font-size:13px; font-weight:600; color:#22c55e; margin:0;">WhatsApp Conectado</p>
                        @if($profileName)
                        <p style="font-size:11px; color:rgba(255,255,255,0.4); margin:0;">{{ $profileName }}</p>
                        @endif
                        @if($phoneNumber)
                        <p style="font-size:12px; color:rgba(255,255,255,0.3); margin:0; font-family:monospace;">+{{ $phoneNumber }}</p>
                        @endif
                    </div>

                @else
                    <div style="padding:24px 0; display:flex; flex-direction:column; align-items:center; gap:10px;">
                        <div style="width:60px; height:60px; border-radius:50%; background:rgba(255,255,255,0.03); border:1px dashed rgba(255,255,255,0.1); display:flex; align-items:center; justify-content:center;">
                            <svg width="28" height="28" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6.364 1.636l-.707.707M20 12h-1M17.657 17.657l-.707-.707M12 20v-1m-5.657-1.636l.707-.707M4 12H3m3.343-5.657l.707.707M12 12a3 3 0 110-6 3 3 0 010 6z"/>
                            </svg>
                        </div>
                        <p style="font-size:12px; color:rgba(255,255,255,0.2); margin:0; text-align:center; line-height:1.6;">
                            Clique em <strong style="color:rgba(255,255,255,0.4);">Conectar / QR Code</strong><br>para gerar o código de conexão
                        </p>
                    </div>
                @endif
            </div>

            {{-- Info rápida --}}
            <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.07); border-radius:16px; padding:16px;">
                <h2 style="font-size:12px; font-weight:700; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.08em; margin:0 0 12px;">
                    Como Conectar
                </h2>
                <ol style="margin:0; padding:0 0 0 16px; display:flex; flex-direction:column; gap:8px;">
                    @foreach([
                        'Preencha a URL do servidor e a Global API Key',
                        'Clique em "Salvar Configuração"',
                        'Clique em "Criar Instância"',
                        'Clique em "Conectar / QR Code"',
                        'Escaneie o QR Code com seu WhatsApp',
                        'Clique em "Configurar Webhook" para receber mensagens',
                    ] as $i => $step)
                    <li style="font-size:11px; color:rgba(255,255,255,0.4); line-height:1.5;">
                        <span style="color:#b2ff00; font-weight:700;">{{ $i + 1 }}.</span> {{ $step }}
                    </li>
                    @endforeach
                </ol>
            </div>
        </div>

    </div>
</div>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
