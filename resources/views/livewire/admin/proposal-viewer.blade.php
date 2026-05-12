<div>
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h2 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif;">Propostas Geradas</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:2px;">Propostas comerciais salvas pelo simulador de preços</p>
        </div>
        <a href="/pricing" target="_blank" style="font-size:11px; color:#b2ff00; text-decoration:none; padding:6px 14px; border:1px solid rgba(178,255,0,0.2); border-radius:8px;">
            Abrir simulador →
        </a>
    </div>

    @if(empty($proposals))
    <div style="text-align:center; padding:60px 20px; color:rgba(255,255,255,0.3);">
        <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 12px; opacity:0.3;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p style="font-size:13px;">Nenhuma proposta gerada ainda</p>
    </div>
    @else
    <div style="display:flex; flex-direction:column; gap:10px;">
        @foreach($proposals as $p)
        <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:14px; overflow:hidden;">
            {{-- Header do card --}}
            <div wire:click="toggle({{ $p['id'] }})" style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; cursor:pointer;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <div style="width:36px; height:36px; border-radius:10px; background:rgba(178,255,0,0.08); display:flex; align-items:center; justify-content:center;">
                        <svg width="18" height="18" fill="none" stroke="#b2ff00" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p style="font-size:14px; font-weight:700; color:white;">{{ $p['client_name'] }}</p>
                        <p style="font-size:11px; color:rgba(255,255,255,0.3); margin-top:2px;">
                            {{ \Carbon\Carbon::parse($p['created_at'])->format('d/m/Y H:i') }}
                            @if($p['user'])
                                — por <span style="color:rgba(178,255,0,0.6);">{{ $p['user']['name'] }}</span>
                            @else
                                — via simulador público
                            @endif
                        </p>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:16px;">
                    <div style="text-align:right;">
                        <p style="font-size:10px; color:rgba(255,255,255,0.3);">Mensalidade</p>
                        <p style="font-size:14px; font-weight:700; color:#b2ff00;">R$ {{ number_format($p['total_monthly'], 2, ',', '.') }}</p>
                    </div>
                    <div style="text-align:right;">
                        <p style="font-size:10px; color:rgba(255,255,255,0.3);">Implantação</p>
                        <p style="font-size:14px; font-weight:700; color:#3b82f6;">R$ {{ number_format($p['total_setup'], 2, ',', '.') }}</p>
                    </div>
                    <svg width="16" height="16" fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="2" viewBox="0 0 24 24"
                         style="transform:rotate({{ $expandedId === $p['id'] ? '180' : '0' }}deg); transition:transform 0.2s;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>

            {{-- Detalhes expandidos --}}
            @if($expandedId === $p['id'])
            <div style="padding:0 20px 20px; border-top:1px solid rgba(255,255,255,0.04);">
                <div style="padding-top:16px;">
                    <p style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:10px;">Módulos contratados</p>
                    <div style="display:grid; grid-template-columns:1fr auto auto; gap:8px; font-size:12px;">
                        <div style="color:rgba(255,255,255,0.4); font-weight:600; padding-bottom:6px; border-bottom:1px solid rgba(255,255,255,0.06);">Módulo</div>
                        <div style="color:rgba(255,255,255,0.4); font-weight:600; text-align:right; padding-bottom:6px; border-bottom:1px solid rgba(255,255,255,0.06);">Mensal</div>
                        <div style="color:rgba(255,255,255,0.4); font-weight:600; text-align:right; padding-bottom:6px; border-bottom:1px solid rgba(255,255,255,0.06);">Implantação</div>

                        @php $details = $p['details'] ?? []; $modules = $p['modules'] ?? []; $cfg = $p['config'] ?? []; @endphp

                        @if(!empty($modules['multi']))
                        <div style="color:rgba(255,255,255,0.7);">Multi-atendimento ({{ $cfg['multi_users'] ?? 1 }} usr, {{ $cfg['multi_instances'] ?? 1 }} nº)</div>
                        <div style="color:#b2ff00; text-align:right;">R$ {{ number_format($details['multi_monthly'] ?? 0, 2, ',', '.') }}</div>
                        <div style="color:#3b82f6; text-align:right;">R$ {{ number_format($details['multi_setup'] ?? 0, 2, ',', '.') }}</div>
                        @endif

                        @if(!empty($modules['crm']))
                        <div style="color:rgba(255,255,255,0.7);">CRM — Pipeline de Vendas</div>
                        <div style="color:#b2ff00; text-align:right;">R$ {{ number_format($details['crm_monthly'] ?? 0, 2, ',', '.') }}</div>
                        <div style="color:#3b82f6; text-align:right;">R$ {{ number_format($details['crm_setup'] ?? 0, 2, ',', '.') }}</div>
                        @endif

                        @if(!empty($modules['email']))
                        <div style="color:rgba(255,255,255,0.7);">Disparos em Massa</div>
                        <div style="color:#b2ff00; text-align:right;">R$ {{ number_format($details['email_monthly'] ?? 0, 2, ',', '.') }}</div>
                        <div style="color:#3b82f6; text-align:right;">R$ {{ number_format($details['email_setup'] ?? 0, 2, ',', '.') }}</div>
                        @endif

                        @if(!empty($modules['ia']))
                        <div style="color:rgba(255,255,255,0.7);">IA de Atendimento ({{ $cfg['ia_flows'] ?? 1 }} fluxo{{ ($cfg['ia_flows'] ?? 1) > 1 ? 's' : '' }})</div>
                        <div style="color:#b2ff00; text-align:right;">R$ {{ number_format($details['ia_monthly'] ?? 0, 2, ',', '.') }}</div>
                        <div style="color:#3b82f6; text-align:right;">R$ {{ number_format($details['ia_setup'] ?? 0, 2, ',', '.') }}</div>
                        @endif

                        @if(!empty($modules['integrations']))
                        <div style="color:rgba(255,255,255,0.7);">Integrações ({{ $cfg['integrations_count'] ?? 1 }})</div>
                        <div style="color:#b2ff00; text-align:right;">R$ {{ number_format($details['int_monthly'] ?? 0, 2, ',', '.') }}</div>
                        <div style="color:#3b82f6; text-align:right;">R$ {{ number_format($details['int_setup'] ?? 0, 2, ',', '.') }}</div>
                        @endif
                    </div>
                </div>

                {{-- Desconto --}}
                @if($discountId === $p['id'])
                <div style="margin-top:12px; padding:12px 16px; background:rgba(245,158,11,0.06); border:1px solid rgba(245,158,11,0.15); border-radius:10px;">
                    <p style="font-size:11px; font-weight:600; color:#fbbf24; margin-bottom:8px;">Aplicar desconto percentual</p>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <input type="number" wire:model="discountPercent" min="1" max="90" placeholder="Ex: 10"
                               style="width:80px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:6px 10px; font-size:13px; color:white; outline:none; text-align:center;">
                        <span style="font-size:13px; color:rgba(255,255,255,0.4);">%</span>
                        <button wire:click="applyDiscount({{ $p['id'] }})"
                                style="padding:6px 14px; font-size:11px; background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.3); color:#fbbf24; border-radius:8px; cursor:pointer; font-weight:600;">
                            Aplicar
                        </button>
                        <button wire:click="openDiscount({{ $p['id'] }})"
                                style="padding:6px 10px; font-size:11px; background:transparent; border:1px solid rgba(255,255,255,0.1); color:rgba(255,255,255,0.4); border-radius:8px; cursor:pointer;">
                            Cancelar
                        </button>
                    </div>
                </div>
                @endif

                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px; flex-wrap:wrap;">
                    {{-- Copiar link --}}
                    <button x-data @click="navigator.clipboard.writeText(window.location.origin + '/pricing/{{ $p['token'] }}/editar'); $dispatch('toast', { type: 'success', message: 'Link copiado!' })"
                            style="padding:6px 14px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); color:rgba(255,255,255,0.6); border-radius:8px; cursor:pointer;">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline; vertical-align:middle; margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        Copiar link
                    </button>

                    {{-- Editar --}}
                    <a href="/pricing/{{ $p['token'] }}/editar" target="_blank"
                       style="padding:6px 14px; font-size:11px; background:rgba(59,130,246,0.1); border:1px solid rgba(59,130,246,0.2); color:#60a5fa; border-radius:8px; cursor:pointer; text-decoration:none;">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline; vertical-align:middle; margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Editar
                    </a>

                    {{-- Desconto --}}
                    <button wire:click="openDiscount({{ $p['id'] }})"
                            style="padding:6px 14px; font-size:11px; background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.2); color:#fbbf24; border-radius:8px; cursor:pointer;">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline; vertical-align:middle; margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        Desconto %
                    </button>

                    {{-- Excluir --}}
                    <button wire:click="delete({{ $p['id'] }})" wire:confirm="Excluir esta proposta?"
                            style="padding:6px 14px; font-size:11px; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2); color:#ef4444; border-radius:8px; cursor:pointer;">
                        Excluir
                    </button>
                </div>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>
