<div>
    {{-- Header --}}
    <div style="height:60px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:space-between; padding:0 24px; flex-shrink:0; background:rgba(11,15,28,0.5); backdrop-filter:blur(6px);">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
            <h1 style="font-size:15px; font-weight:800; color:white; font-family:Syne,sans-serif; letter-spacing:-0.02em;">Disparos</h1>
            <span style="font-size:10px; color:rgba(255,255,255,0.3); margin-left:4px;">{{ $activeLeadCount }} leads ativos</span>
        </div>
        <button wire:click="openCreate"
                style="display:flex; align-items:center; gap:6px; padding:6px 14px; font-size:11px; font-weight:600; color:#b2ff00; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:8px; cursor:pointer;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nova Campanha
        </button>
    </div>

    <div style="padding:20px 24px;">
        {{-- Tabela de campanhas --}}
        <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:12px; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.06);">
                        <th style="padding:10px 16px; text-align:left; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Campanha</th>
                        <th style="padding:10px 16px; text-align:center; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Status</th>
                        <th style="padding:10px 16px; text-align:center; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Enviados</th>
                        <th style="padding:10px 16px; text-align:center; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Falhas</th>
                        <th style="padding:10px 16px; text-align:left; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Criado em</th>
                        <th style="padding:10px 16px; text-align:right; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                        <td style="padding:10px 16px;">
                            <p style="font-size:12px; font-weight:600; color:white;">{{ $campaign->name }}</p>
                            <p style="font-size:10px; color:rgba(255,255,255,0.3); margin-top:2px;">{{ Str::limit($campaign->message, 60) }}</p>
                        </td>
                        <td style="padding:10px 16px; text-align:center;">
                            @php
                                $statusColors = [
                                    'draft'     => ['bg' => 'rgba(107,114,128,0.12)', 'color' => '#9ca3af', 'border' => 'rgba(107,114,128,0.2)', 'label' => 'Rascunho'],
                                    'sending'   => ['bg' => 'rgba(59,130,246,0.12)', 'color' => '#60a5fa', 'border' => 'rgba(59,130,246,0.2)', 'label' => 'Enviando'],
                                    'completed' => ['bg' => 'rgba(34,197,94,0.12)', 'color' => '#4ade80', 'border' => 'rgba(34,197,94,0.2)', 'label' => 'Concluída'],
                                    'failed'    => ['bg' => 'rgba(239,68,68,0.12)', 'color' => '#f87171', 'border' => 'rgba(239,68,68,0.2)', 'label' => 'Falhou'],
                                ];
                                $s = $statusColors[$campaign->status] ?? $statusColors['draft'];
                            @endphp
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:{{ $s['bg'] }}; color:{{ $s['color'] }}; border:1px solid {{ $s['border'] }};">{{ $s['label'] }}</span>
                        </td>
                        <td style="padding:10px 16px; text-align:center; font-size:12px; color:#4ade80; font-weight:600;">{{ $campaign->sent_count ?? 0 }}</td>
                        <td style="padding:10px 16px; text-align:center; font-size:12px; color:#f87171; font-weight:600;">{{ $campaign->failed_count ?? 0 }}</td>
                        <td style="padding:10px 16px; font-size:11px; color:rgba(255,255,255,0.4);">{{ $campaign->created_at->format('d/m/Y H:i') }}</td>
                        <td style="padding:10px 16px; text-align:right;">
                            <div style="display:flex; justify-content:flex-end; gap:6px;">
                                @if($campaign->status === 'draft' || $campaign->status === 'completed')
                                <button wire:click="send({{ $campaign->id }})" wire:confirm="Disparar mensagem para todos os leads ativos?"
                                        style="padding:4px 10px; font-size:11px; color:#b2ff00; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:6px; cursor:pointer;">
                                    {{ $campaign->status === 'completed' ? 'Re-disparar' : 'Disparar' }}
                                </button>
                                @endif
                                <button wire:click="viewRuns({{ $campaign->id }})"
                                        style="padding:4px 10px; font-size:11px; color:rgba(255,255,255,0.4); background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:6px; cursor:pointer;">
                                    Histórico
                                </button>
                                <button wire:click="deleteCampaign({{ $campaign->id }})" wire:confirm="Excluir campanha?"
                                        style="padding:4px 10px; font-size:11px; color:#f87171; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); border-radius:6px; cursor:pointer;">
                                    Excluir
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:40px 16px; text-align:center; font-size:13px; color:rgba(255,255,255,0.3);">Nenhuma campanha criada.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:12px;">{{ $campaigns->links() }}</div>
    </div>

    {{-- Modal: Nova Campanha --}}
    @if($showForm)
    <div style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px);" wire:click.self="$set('showForm', false)">
        <div style="background:#0f1320; border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:24px; width:100%; max-width:520px;">
            <h2 style="font-size:15px; font-weight:700; color:white; margin-bottom:16px; font-family:Syne,sans-serif;">Nova Campanha</h2>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Nome da campanha *</label>
                    <input wire:model="name" type="text" placeholder="Ex: Promoção de Natal"
                           style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none;">
                    @error('name') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Mensagem * <span style="color:rgba(255,255,255,0.2); font-weight:400;">(use {nome} para o nome do lead)</span></label>
                    <textarea wire:model="message" rows="5" placeholder="Olá {nome}! Temos uma oferta especial..."
                              style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; resize:vertical;"></textarea>
                    @error('message') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Intervalo entre envios (seg)</label>
                        <input wire:model="interval_seconds" type="number" min="3" max="120"
                               style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none;">
                    </div>
                    <div>
                        <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Destinatários</label>
                        <select wire:model.live="recipientMode"
                                style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white;">
                            <option value="all">Todos os leads ativos ({{ $activeLeadCount }})</option>
                            <option value="tag">Filtrar por tag</option>
                        </select>
                    </div>
                </div>
                @if($recipientMode === 'tag')
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Tag</label>
                    <select wire:model="filterTag"
                            style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white;">
                        <option value="">Selecione...</option>
                        @foreach($allTags as $tag)
                        <option value="{{ $tag }}">{{ $tag }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            <div style="display:flex; gap:10px; margin-top:18px;">
                <button wire:click="save" style="flex:1; padding:8px; font-size:12px; font-weight:700; color:#111; background:linear-gradient(135deg, #b2ff00, #8fcc00); border:none; border-radius:8px; cursor:pointer;">Criar Campanha</button>
                <button wire:click="$set('showForm', false)" style="padding:8px 16px; font-size:12px; color:rgba(255,255,255,0.4); background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; cursor:pointer;">Cancelar</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal: Histórico de runs --}}
    @if($viewingCampaign)
    <div style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px);" wire:click.self="closeRuns">
        <div style="background:#0f1320; border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:24px; width:100%; max-width:600px; max-height:80vh; overflow-y:auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h2 style="font-size:15px; font-weight:700; color:white; font-family:Syne,sans-serif;">{{ $viewingCampaign->name }} — Histórico</h2>
                <button wire:click="closeRuns" style="color:rgba(255,255,255,0.3); background:none; border:none; cursor:pointer; font-size:18px;">&times;</button>
            </div>

            @if($runs->isEmpty())
            <p style="font-size:12px; color:rgba(255,255,255,0.3); text-align:center; padding:20px;">Nenhum disparo realizado.</p>
            @else
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach($runs as $run)
                <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:10px; padding:12px 16px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <span style="font-size:11px; color:rgba(255,255,255,0.5);">{{ $run->created_at->format('d/m/Y H:i') }}</span>
                            @php
                                $rc = match($run->status) {
                                    'sending'   => 'color:#60a5fa',
                                    'completed' => 'color:#4ade80',
                                    'failed'    => 'color:#f87171',
                                    default     => 'color:#9ca3af',
                                };
                            @endphp
                            <span style="font-size:10px; font-weight:700; margin-left:8px; {{ $rc }}">{{ strtoupper($run->status) }}</span>
                        </div>
                        <div style="display:flex; gap:12px; font-size:11px;">
                            <span style="color:#4ade80;">{{ $run->sent_count }} enviados</span>
                            <span style="color:#f87171;">{{ $run->failed_count }} falhas</span>
                            <span style="color:rgba(255,255,255,0.3);">/ {{ $run->total_recipients }}</span>
                        </div>
                    </div>
                    @if($run->completed_at)
                    <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:4px;">Concluído em {{ $run->completed_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
