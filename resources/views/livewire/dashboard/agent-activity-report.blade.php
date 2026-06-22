<div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:20px 24px; position:relative; overflow:hidden;">
    <div style="position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, #3b82f680, transparent);"></div>

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:8px;">
        <div style="display:flex; align-items:center; gap:8px;">
            <div style="width:2px; height:16px; background:#3b82f6; border-radius:2px;"></div>
            <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Atividade Real por Agente × Estado</h3>
            <span style="font-size:9px; color:rgba(255,255,255,0.25); font-weight:400;">(baseado em mensagens enviadas)</span>
        </div>
        <div style="display:flex; gap:8px; align-items:center;">
            @if(!empty($data))
            <button onclick="printAgentActivityReport()" style="padding:5px 12px; font-size:11px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); border-radius:6px; color:white; cursor:pointer; display:flex; align-items:center; gap:4px;" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='rgba(255,255,255,0.08)'">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Imprimir
            </button>
            @endif
            <select wire:model.live="agentFilter" style="padding:5px 10px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:white; outline:none;">
                <option value="">Todos os agentes</option>
                @foreach($agents as $agent)
                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if(empty($data))
    <p style="font-size:12px; color:rgba(255,255,255,0.3); text-align:center; padding:20px;">Nenhuma atividade no periodo.</p>
    @else
    <div style="overflow-x:auto;">
        <table id="agentActivityTable" style="width:100%; border-collapse:collapse; font-size:11px;">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08);">
                    <th style="text-align:left; padding:8px 10px; color:rgba(255,255,255,0.4); font-weight:600; white-space:nowrap; position:sticky; left:0; background:#0f1320;">Agente</th>
                    @foreach($estados as $uf)
                    <th style="text-align:center; padding:8px 6px; color:rgba(255,255,255,0.4); font-weight:700; min-width:40px;">{{ $uf }}</th>
                    @endforeach
                    <th style="text-align:center; padding:8px 10px; color:#3b82f6; font-weight:700;">Conversas</th>
                    <th style="text-align:center; padding:8px 10px; color:#8b5cf6; font-weight:700;">Msgs</th>
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
                        <span style="display:inline-flex; align-items:center; justify-content:center; min-width:28px; padding:2px 6px; border-radius:4px; font-weight:700; font-size:11px; background:rgba(59,130,246,{{ $intensity }}); color:{{ $intensity > 0.4 ? '#fff' : '#60a5fa' }};">{{ $count }}</span>
                        @else
                        <span style="color:rgba(255,255,255,0.08);">—</span>
                        @endif
                    </td>
                    @endforeach
                    <td style="text-align:center; padding:6px 10px; font-weight:800; color:#3b82f6;">{{ $agent['total_convs'] }}</td>
                    <td style="text-align:center; padding:6px 10px; font-weight:800; color:#8b5cf6;">{{ $agent['total_msgs'] }}</td>
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
                    <td style="text-align:center; padding:8px 10px; font-weight:800; color:#c4b5fd; font-size:13px;">{{ collect($data)->sum('total_msgs') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script>
    function printAgentActivityReport() {
        const table = document.getElementById('agentActivityTable');
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        const headers = table.querySelectorAll('thead th');
        const footCells = table.querySelectorAll('tfoot td');

        let headerHtml = '<tr>';
        headers.forEach(th => {
            headerHtml += '<th style="padding:8px 6px; border:1px solid #ccc; background:#3b82f6; color:#fff; font-weight:700; font-size:11px; text-align:center; white-space:nowrap;">' + th.textContent.trim() + '</th>';
        });
        headerHtml += '</tr>';

        let bodyHtml = '';
        let rowIdx = 0;
        rows.forEach(tr => {
            const cells = tr.querySelectorAll('td');
            const bg = rowIdx % 2 === 0 ? '#fff' : '#f9fafb';
            bodyHtml += '<tr>';
            cells.forEach((td, i) => {
                const val = td.textContent.trim();
                const isFirst = i === 0;
                const isLast = i >= cells.length - 2;
                let cellStyle = 'padding:6px; border:1px solid #ddd; font-size:11px; background:' + bg + ';';
                if (isFirst) {
                    cellStyle += 'text-align:left; font-weight:600; white-space:nowrap;';
                } else if (isLast) {
                    cellStyle += 'text-align:center; font-weight:800; color:#1d4ed8;';
                } else {
                    cellStyle += 'text-align:center;';
                    if (val && val !== '—' && val !== '' && parseInt(val) > 0) {
                        cellStyle += 'font-weight:700; color:#1e40af;';
                    } else {
                        cellStyle += 'color:#ccc;';
                    }
                }
                bodyHtml += '<td style="' + cellStyle + '">' + (val === '—' || val === '' ? '–' : val) + '</td>';
            });
            bodyHtml += '</tr>';
            rowIdx++;
        });

        let footHtml = '<tr>';
        footCells.forEach((td, i) => {
            const val = td.textContent.trim();
            let cellStyle = 'padding:8px 6px; border:1px solid #ccc; background:#eff6ff; font-weight:700; font-size:11px;';
            if (i === 0) {
                cellStyle += 'text-align:left;';
            } else {
                cellStyle += 'text-align:center;';
            }
            footHtml += '<td style="' + cellStyle + '">' + val + '</td>';
        });
        footHtml += '</tr>';

        const printWindow = window.open('', '_blank', 'width=900,height=700');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Atividade Real por Agente × Estado</title>
                <style>
                    @page { size: landscape; margin: 15mm; }
                    body { font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 20px; color: #111; }
                    table { width: 100%; border-collapse: collapse; }
                    @media print { body { padding: 0; } }
                </style>
            </head>
            <body>
                <h2 style="font-size:16px; font-weight:800; color:#111; margin:0 0 4px;">ATIVIDADE REAL POR AGENTE × ESTADO</h2>
                <p style="font-size:10px; color:#888; margin:0 0 16px;">Baseado em mensagens enviadas pelo agente no periodo</p>
                <table>
                    <thead>${headerHtml}</thead>
                    <tbody>${bodyHtml}</tbody>
                    <tfoot>${footHtml}</tfoot>
                </table>
                <p style="font-size:9px; color:#999; margin-top:16px; text-align:right;">Gerado em ${new Date().toLocaleDateString('pt-BR')} as ${new Date().toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'})}</p>
            </body>
            </html>
        `);
        printWindow.document.close();
        setTimeout(() => { printWindow.print(); }, 300);
    }
    </script>
    @endif
</div>
