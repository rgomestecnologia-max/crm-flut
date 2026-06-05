<div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:20px 24px; position:relative; overflow:hidden;">
    <div style="position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, #f9731680, transparent);"></div>

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
        <div style="display:flex; align-items:center; gap:8px;">
            <div style="width:2px; height:16px; background:#fb923c; border-radius:2px;"></div>
            <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Atendimentos por Agente × Estado</h3>
        </div>
        <div style="display:flex; gap:8px;">
            <select wire:model.live="agentFilter" style="padding:5px 10px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:white; outline:none;">
                <option value="">Todos os agentes</option>
                @foreach($agents as $agent)
                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if(empty($data))
    <p style="font-size:12px; color:rgba(255,255,255,0.3); text-align:center; padding:20px;">Nenhum atendimento no período.</p>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:11px;">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08);">
                    <th style="text-align:left; padding:8px 10px; color:rgba(255,255,255,0.4); font-weight:600; white-space:nowrap; position:sticky; left:0; background:#0f1320;">Agente</th>
                    @foreach($estados as $uf)
                    <th style="text-align:center; padding:8px 6px; color:rgba(255,255,255,0.4); font-weight:700; min-width:40px;">{{ $uf }}</th>
                    @endforeach
                    <th style="text-align:center; padding:8px 10px; color:#fb923c; font-weight:700;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $agentId => $agent)
                <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                    <td style="padding:6px 10px; color:white; font-weight:600; white-space:nowrap; position:sticky; left:0; background:#0f1320;">
                        {{ \Illuminate\Support\Str::limit($agent['name'], 25) }}
                    </td>
                    @foreach($estados as $uf)
                    @php $count = $agent['estados'][$uf] ?? 0; $intensity = $count > 0 ? max(0.1, min(0.8, $count / $maxValue)) : 0; @endphp
                    <td style="text-align:center; padding:6px 4px;">
                        @if($count > 0)
                        <span style="display:inline-flex; align-items:center; justify-content:center; min-width:28px; padding:2px 6px; border-radius:4px; font-weight:700; font-size:11px; background:rgba(249,115,22,{{ $intensity }}); color:{{ $intensity > 0.4 ? '#fff' : '#fb923c' }};">{{ $count }}</span>
                        @else
                        <span style="color:rgba(255,255,255,0.08);">—</span>
                        @endif
                    </td>
                    @endforeach
                    <td style="text-align:center; padding:6px 10px; font-weight:800; color:#fb923c;">{{ $agent['total'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="border-top:2px solid rgba(255,255,255,0.1);">
                    <td style="padding:8px 10px; color:rgba(255,255,255,0.5); font-weight:700; position:sticky; left:0; background:#0f1320;">Total</td>
                    @foreach($estados as $uf)
                    <td style="text-align:center; padding:8px 4px; font-weight:700; color:rgba(255,255,255,0.6);">{{ $estadoTotals[$uf] ?? 0 }}</td>
                    @endforeach
                    <td style="text-align:center; padding:8px 10px; font-weight:800; color:#b2ff00; font-size:13px;">{{ $grandTotal }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>
