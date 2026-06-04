<div>
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h2 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif;">Precificação</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:2px;">Configure os valores do simulador público</p>
        </div>
        <a href="/pricing" target="_blank" style="font-size:11px; color:#b2ff00; text-decoration:none; padding:6px 14px; border:1px solid rgba(178,255,0,0.2); border-radius:8px;">
            Ver simulador →
        </a>
    </div>

    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:24px;">

        @php
            $sections = [
                'Multi-atendimento' => ['multi_base_price','multi_base_users','multi_extra_user','multi_extra_instance','multi_setup','multi_messenger_price','multi_instagram_price'],
                'CRM' => ['crm_price','crm_setup'],
                'Disparos Email' => ['email_5k_price','email_20k_price','email_50k_price','email_setup'],
                'IA de Atendimento' => ['ia_flow_price','ia_flow_setup'],
                'Integrações' => ['integration_setup','integration_monthly'],
                'Chat Interno' => ['chat_interno_price','chat_interno_setup'],
                'Landing Pages' => ['landing_price','landing_setup'],
                'FlutChat' => ['flutchat_price','flutchat_ia_price','flutchat_setup'],
                'FlutZap' => ['flutzap_price','flutzap_setup'],
                'Gestão Consultiva e Operacional' => ['consultoria_price','consultoria_hours','consultoria_setup'],
            ];
            $benefitsKeys = [
                'Multi-atendimento' => 'multi_benefits',
                'CRM' => 'crm_benefits',
                'Disparos Email' => 'email_benefits',
                'IA de Atendimento' => 'ia_benefits',
                'Integrações' => 'integration_benefits',
                'Chat Interno' => 'chat_interno_benefits',
                'Landing Pages' => 'landing_benefits',
                'FlutChat' => 'flutchat_benefits',
                'FlutZap' => 'flutzap_benefits',
                'Gestão Consultiva e Operacional' => 'consultoria_benefits',
            ];
            $imgModels = ['multi_image','crm_image','email_image','ia_image','integration_image','chat_interno_image','landing_image','flutchat_image','flutzap_image','consultoria_image'];
            $imgConfigs = ['multi_screenshot','crm_screenshot','email_screenshot','ia_screenshot','integration_screenshot','chat_interno_screenshot','landing_screenshot','flutchat_screenshot','flutzap_screenshot','consultoria_screenshot'];
            $colors = ['#b2ff00','#8b5cf6','#3b82f6','#ec4899','#06b6d4','#10b981','#f97316','#6366f1','#f59e0b','#14b8a6'];
        @endphp

        @foreach($sections as $section => $keys)
        <div style="margin-bottom:20px; {{ !$loop->first ? 'padding-top:16px; border-top:1px solid rgba(255,255,255,0.04);' : '' }}">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                <div style="width:2px; height:14px; background:{{ $colors[$loop->index] }}; border-radius:2px;"></div>
                <h3 style="font-size:11px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">{{ $section }}</h3>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;" class="mobile-grid-1">
                @foreach($keys as $key)
                @if(isset($labels[$key]))
                <div style="padding:10px 14px; background:rgba(255,255,255,0.02); border-radius:10px; border:1px solid rgba(255,255,255,0.04);">
                    <label style="display:block; font-size:10px; font-weight:600; color:rgba(255,255,255,0.3); margin-bottom:4px;">{{ $labels[$key][0] }}</label>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <input wire:model="prices.{{ $key }}" type="text"
                               style="flex:1; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:7px 10px; font-size:13px; color:white; outline:none; font-family:monospace;"
                               onfocus="this.style.borderColor='rgba(178,255,0,0.4)'" onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
                        <span style="font-size:10px; color:rgba(255,255,255,0.2); white-space:nowrap;">{{ $labels[$key][1] }}</span>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
            {{-- Conteúdo da proposta (PDF) + Upload de screenshot --}}
            @if(isset($benefitsKeys[$section]))
            <div style="margin-top:12px; padding:12px 14px; background:rgba(255,255,255,0.015); border-radius:10px; border:1px solid rgba(255,255,255,0.04);">
                <label style="display:flex; align-items:center; gap:6px; font-size:10px; font-weight:600; color:rgba(255,255,255,0.3); margin-bottom:6px;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Conteúdo da proposta (PDF) — benefícios e diferenciais
                </label>
                <textarea wire:model="prices.{{ $benefitsKeys[$section] }}" rows="6"
                          style="width:100%; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:8px; padding:10px 12px; font-size:11px; color:rgba(255,255,255,0.7); outline:none; resize:vertical; line-height:1.6;"
                          onfocus="this.style.borderColor='rgba(178,255,0,0.3)'" onblur="this.style.borderColor='rgba(255,255,255,0.06)'"
                          placeholder="Descreva os benefícios e diferenciais deste módulo que aparecerão no PDF da proposta..."></textarea>
                <div style="margin-top:10px; padding-top:10px; border-top:1px solid rgba(255,255,255,0.04);">
                    <label style="display:flex; align-items:center; gap:6px; font-size:10px; font-weight:600; color:rgba(255,255,255,0.3); margin-bottom:6px;">
                        Ilustração do módulo (PDF)
                    </label>
                    @if(!empty($prices[$imgConfigs[$loop->index]]))
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                        <img src="{{ $prices[$imgConfigs[$loop->index]] }}" style="max-height:80px; border-radius:6px; border:1px solid rgba(255,255,255,0.08);">
                        <button wire:click="removeScreenshot('{{ $imgConfigs[$loop->index] }}')" type="button"
                                style="padding:4px 10px; font-size:10px; color:#ef4444; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2); border-radius:6px; cursor:pointer;">
                            Remover
                        </button>
                    </div>
                    @endif
                    <input type="file" wire:model="{{ $imgModels[$loop->index] }}" accept="image/*"
                           style="font-size:11px; color:rgba(255,255,255,0.5);">
                    <div wire:loading wire:target="{{ $imgModels[$loop->index] }}" style="font-size:10px; color:#b2ff00; margin-top:4px;">Enviando...</div>
                </div>
            </div>
            @endif
        </div>
        @endforeach

        <div style="display:flex; justify-content:flex-end; margin-top:16px;">
            <button wire:click="save"
                    style="padding:10px 24px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-size:13px; font-weight:700; border-radius:11px; border:none; cursor:pointer; box-shadow:0 2px 16px rgba(178,255,0,0.3);">
                Salvar preços
            </button>
        </div>
    </div>
</div>
