<div>
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; overflow:hidden;">
        <div style="padding:20px 20px 0;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                <div style="width:2px; height:14px; background:#3b82f6; border-radius:2px;"></div>
                <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Performance dos agentes</h3>
            </div>
        </div>

        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.05); border-top:1px solid rgba(255,255,255,0.05);">
                    <th style="text-align:left; padding:10px 20px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Agente</th>
                    <th style="text-align:center; padding:10px 16px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Ativas</th>
                    <th style="text-align:center; padding:10px 16px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Resolvidas hoje</th>
                    <th style="text-align:center; padding:10px 16px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agents as $agent)
                <tr style="border-bottom:1px solid rgba(255,255,255,0.03); transition:background 0.15s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.02)'"
                    onmouseout="this.style.background='transparent'">
                    <td style="padding:10px 20px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <img src="{{ $agent->avatar_url }}" alt="" style="width:30px; height:30px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.08);">
                            <div>
                                <p style="font-size:12px; font-weight:600; color:rgba(255,255,255,0.8);">{{ $agent->name }}</p>
                                <p style="font-size:10px; color:rgba(255,255,255,0.25);">{{ $agent->role === 'supervisor' ? 'Supervisor' : 'Agente' }}</p>
                            </div>
                        </div>
                    </td>
                    <td style="text-align:center; padding:10px 16px;">
                        <span style="font-size:14px; font-weight:700; color:{{ $agent->active_count > 0 ? '#b2ff00' : 'rgba(255,255,255,0.2)' }};">{{ $agent->active_count }}</span>
                    </td>
                    <td style="text-align:center; padding:10px 16px;">
                        <span style="font-size:14px; font-weight:700; color:{{ $agent->resolved_today > 0 ? '#10b981' : 'rgba(255,255,255,0.2)' }};">{{ $agent->resolved_today }}</span>
                    </td>
                    <td style="text-align:center; padding:10px 16px;">
                        @php
                            $statusColor = match($agent->status) {
                                'online' => '#22c55e',
                                'busy'   => '#eab308',
                                default  => '#6b7280',
                            };
                            $statusLabel = match($agent->status) {
                                'online' => 'Online',
                                'busy'   => 'Ocupado',
                                default  => 'Offline',
                            };
                        @endphp
                        <span style="font-size:10px; font-weight:600; padding:3px 8px; border-radius:20px; background:{{ $statusColor }}18; color:{{ $statusColor }}; border:1px solid {{ $statusColor }}30;">
                            {{ $statusLabel }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="padding:24px; text-align:center; font-size:12px; color:rgba(255,255,255,0.25);">Nenhum agente ativo.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
