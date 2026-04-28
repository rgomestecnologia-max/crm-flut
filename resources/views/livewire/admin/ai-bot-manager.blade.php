@php
$inputStyle = "width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:10px 14px; font-size:13px; color:white; outline:none; transition:all 0.2s; font-family:inherit; box-sizing:border-box;";
$inputFocus = "onfocus=\"this.style.borderColor='rgba(178,255,0,0.5)'; this.style.boxShadow='0 0 0 3px rgba(178,255,0,0.07)'\" onblur=\"this.style.borderColor='rgba(255,255,255,0.08)'; this.style.boxShadow='none'\"";
$labelStyle = "display:block; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;";
$cardStyle = "background:linear-gradient(145deg, rgba(17,24,39,0.9) 0%, rgba(11,15,28,0.95) 100%); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:24px; margin-bottom:16px; position:relative; overflow:hidden;";
@endphp

<div>

    {{-- Toggle principal --}}
    <div style="{{ $cardStyle }}">
        <div style="position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, {{ $is_active ? '#b2ff0080' : 'rgba(107,114,128,0.4)' }}, transparent); border-radius:16px 16px 0 0;"></div>
        <div style="display:flex; align-items:center; gap:16px;">
            <div style="width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all 0.3s;
                        background:{{ $is_active ? 'rgba(178,255,0,0.12)' : 'rgba(255,255,255,0.04)' }}; border:1px solid {{ $is_active ? 'rgba(178,255,0,0.25)' : 'rgba(255,255,255,0.07)' }};">
                <svg width="24" height="24" fill="none" stroke="{{ $is_active ? '#b2ff00' : 'rgba(255,255,255,0.25)' }}" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2h-2M9 9h6"/>
                </svg>
            </div>
            <div style="flex:1;">
                <h2 style="font-size:16px; font-weight:800; color:white; font-family:'Syne',sans-serif; letter-spacing:-0.01em;">IA de Atendimento</h2>
                <p style="font-size:12px; color:rgba(255,255,255,0.35); margin-top:3px;">
                    Atendimento automático via Google Gemini com roteamento inteligente de departamentos.
                </p>
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
    </div>

    {{-- Aviso sem chave global --}}
    @if(!$globalKeySet)
    <div style="display:flex; align-items:flex-start; gap:10px; background:rgba(234,179,8,0.06); border:1px solid rgba(234,179,8,0.2); border-radius:12px; padding:12px 16px; margin-bottom:16px;">
        <svg width="14" height="14" fill="none" stroke="#fbbf24" viewBox="0 0 24 24" style="flex-shrink:0; margin-top:1px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
        </svg>
        <p style="font-size:12px; color:rgba(251,191,36,0.8);">A chave API Gemini não está configurada. Peça ao administrador do sistema para configurar em <strong>Config. Globais</strong>.</p>
    </div>
    @endif

    <form wire:submit="save">

        {{-- Status da API Gemini (global) --}}
        <div style="{{ $cardStyle }} {{ $globalKeySet ? 'border-color:rgba(178,255,0,0.2);' : 'border-color:rgba(245,158,11,0.3);' }}">
            <div style="display:flex; align-items:center; gap:10px;">
                @if($globalKeySet)
                    <div style="width:32px; height:32px; border-radius:9px; background:rgba(178,255,0,0.12); border:1px solid rgba(178,255,0,0.3); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <svg width="14" height="14" fill="none" stroke="#b2ff00" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p style="font-size:12px; font-weight:600; color:#b2ff00;">API Gemini configurada</p>
                        <p style="font-size:11px; color:rgba(255,255,255,0.4);">Modelo: <strong style="color:rgba(255,255,255,0.6);">{{ $globalModel }}</strong> — gerenciado pelo administrador do sistema.</p>
                    </div>
                @else
                    <div style="width:32px; height:32px; border-radius:9px; background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.3); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <svg width="14" height="14" fill="none" stroke="#f59e0b" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p style="font-size:12px; font-weight:600; color:#f59e0b;">API Gemini não configurada</p>
                        <p style="font-size:11px; color:rgba(255,255,255,0.4);">A IA não funcionará até o administrador do sistema configurar a chave em <strong style="color:rgba(255,255,255,0.6);">Configurações Globais</strong>.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Personalidade --}}
        <div style="{{ $cardStyle }}">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <div style="width:2px; height:16px; background:#f59e0b; border-radius:2px;"></div>
                <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Personalidade</h3>
            </div>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-bottom:16px; padding-left:10px;">Tom de voz e descrição da empresa para contexto da IA.</p>

            <div style="display:flex; flex-direction:column; gap:14px;">
                <div>
                    <label style="{{ $labelStyle }}">Tom de voz <span style="font-weight:400; color:rgba(255,255,255,0.15);">— clique para selecionar</span></label>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        @foreach(\App\Livewire\Admin\AiBotManager::AVAILABLE_TONES as $tone)
                        @php $selected = in_array($tone, $voice_tones); @endphp
                        <button type="button" wire:click="toggleTone('{{ $tone }}')"
                                style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; border-radius:10px; cursor:pointer; transition:all 0.15s; font-size:12px; font-weight:600; border:1px solid {{ $selected ? 'rgba(178,255,0,0.4)' : 'rgba(255,255,255,0.08)' }}; background:{{ $selected ? 'rgba(178,255,0,0.1)' : 'rgba(255,255,255,0.03)' }}; color:{{ $selected ? '#b2ff00' : 'rgba(255,255,255,0.45)' }};"
                                onmouseover="this.style.background='{{ $selected ? 'rgba(178,255,0,0.15)' : 'rgba(255,255,255,0.06)' }}'"
                                onmouseout="this.style.background='{{ $selected ? 'rgba(178,255,0,0.1)' : 'rgba(255,255,255,0.03)' }}'">
                            {{ $tone }}
                        </button>
                        @endforeach
                    </div>
                    @error('voice_tones') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="{{ $labelStyle }}">Descrição da empresa</label>
                    <textarea wire:model="company_description" rows="4"
                              placeholder="Descreva a empresa, produtos/serviços, diferenciais, horário de atendimento..."
                              style="{{ $inputStyle }} resize:none; line-height:1.6;" {!! $inputFocus !!}></textarea>
                    @error('company_description') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="{{ $labelStyle }}">URL do site (para contexto adicional)</label>
                    <input wire:model="website_url" type="text"
                           placeholder="https://www.suaempresa.com.br"
                           style="{{ $inputStyle }}" {!! $inputFocus !!}>
                    @error('website_url') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Treinamento --}}
        <div style="{{ $cardStyle }}">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <div style="width:2px; height:16px; background:#b2ff00; border-radius:2px;"></div>
                <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Treinamento da IA</h3>
            </div>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-bottom:16px; padding-left:10px;">Instruções internas — <strong style="color:rgba(255,255,255,0.4);">nunca enviadas ao contato</strong>. Defina persona, empresa, produtos, tom de voz.</p>

            <div>
                <label style="{{ $labelStyle }}">Instruções do sistema</label>
                <textarea wire:model="system_prompt" rows="8"
                          placeholder="Exemplo: Você é o assistente virtual da empresa XYZ, especializada em vendas de eletrônicos. Seu nome é ARIA. Seja sempre educado, objetivo e profissional. Responda em português brasileiro..."
                          style="{{ $inputStyle }} resize:none; line-height:1.6;" {!! $inputFocus !!}></textarea>
                @error('system_prompt') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Controles --}}
        <div style="{{ $cardStyle }}">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:18px;">
                <div style="width:2px; height:16px; background:#a855f7; border-radius:2px;"></div>
                <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Controles</h3>
            </div>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div style="display:flex; align-items:flex-start; gap:20px; flex-wrap:wrap;">
                    <div>
                        <label style="{{ $labelStyle }}">Máx. turnos por conversa</label>
                        <input wire:model="max_bot_turns" type="number" min="1" max="50"
                               style="width:96px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:9px 14px; font-size:13px; color:white; outline:none; transition:all 0.2s; font-family:inherit; text-align:center;"
                               onfocus="this.style.borderColor='rgba(178,255,0,0.5)'; this.style.boxShadow='0 0 0 3px rgba(178,255,0,0.07)'"
                               onblur="this.style.borderColor='rgba(255,255,255,0.08)'; this.style.boxShadow='none'">
                        @error('max_bot_turns') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="{{ $labelStyle }}">Delay de resposta (seg)</label>
                        <input wire:model="response_delay" type="number" min="0" max="120"
                               style="width:96px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:9px 14px; font-size:13px; color:white; outline:none; transition:all 0.2s; font-family:inherit; text-align:center;"
                               onfocus="this.style.borderColor='rgba(178,255,0,0.5)'; this.style.boxShadow='0 0 0 3px rgba(178,255,0,0.07)'"
                               onblur="this.style.borderColor='rgba(255,255,255,0.08)'; this.style.boxShadow='none'">
                        <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:4px;">0 = imediato</p>
                        @error('response_delay') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                    </div>
                    <div style="flex:1; min-width:200px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); border-radius:10px; padding:12px 14px;">
                        <p style="font-size:11px; font-weight:600; color:rgba(255,255,255,0.4); margin-bottom:4px;">Passagem para humano</p>
                        <p style="font-size:11px; color:rgba(255,255,255,0.2); line-height:1.5;">Ao atingir o limite de turnos, o robô envia a mensagem abaixo e para de responder.</p>
                    </div>
                </div>
                <div>
                    <label style="{{ $labelStyle }}">Mensagem de transferência para atendente</label>
                    <textarea wire:model="handoff_message" rows="2"
                              placeholder="Vou transferir você para um de nossos atendentes. Em breve alguém irá te responder!"
                              style="{{ $inputStyle }} resize:none; line-height:1.6;" {!! $inputFocus !!}></textarea>
                    @error('handoff_message') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                </div>
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
