<div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:20px 24px; position:relative; overflow:hidden;">
    <div style="position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, #f9731680, transparent);"></div>

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
        <div style="display:flex; align-items:center; gap:8px;">
            <div style="width:2px; height:16px; background:#fb923c; border-radius:2px;"></div>
            <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Atendimentos por Agente × Estado</h3>
        </div>
        <div style="display:flex; gap:8px; align-items:center;">
            @if(!empty($data))
            <button onclick="printAgentDddReport()" style="padding:5px 12px; font-size:11px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); border-radius:6px; color:white; cursor:pointer; display:flex; align-items:center; gap:4px;" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='rgba(255,255,255,0.08)'">
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
    <p style="font-size:12px; color:rgba(255,255,255,0.3); text-align:center; padding:20px;">Nenhum atendimento no período.</p>
    @else
    <div style="overflow-x:auto;">
        <table id="agentDddTable" style="width:100%; border-collapse:collapse; font-size:11px;">
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

    <script>
    function printAgentDddReport() {
        const table = document.getElementById('agentDddTable');
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        const headers = table.querySelectorAll('thead th');
        const footCells = table.querySelectorAll('tfoot td');

        let headerHtml = '<tr>';
        headers.forEach(th => {
            headerHtml += '<th style="padding:8px 6px; border:1px solid #ccc; background:#f97316; color:#fff; font-weight:700; font-size:11px; text-align:center; white-space:nowrap;">' + th.textContent.trim() + '</th>';
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
                const isLast = i === cells.length - 1;
                let cellStyle = 'padding:6px; border:1px solid #ddd; font-size:11px; background:' + bg + ';';
                if (isFirst) {
                    cellStyle += 'text-align:left; font-weight:600; white-space:nowrap;';
                } else if (isLast) {
                    cellStyle += 'text-align:center; font-weight:800; color:#c2410c;';
                } else {
                    cellStyle += 'text-align:center;';
                    if (val && val !== '—' && val !== '' && parseInt(val) > 0) {
                        cellStyle += 'font-weight:700; color:#9a3412;';
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
            const isLast = i === footCells.length - 1;
            let cellStyle = 'padding:8px 6px; border:1px solid #ccc; background:#fff7ed; font-weight:700; font-size:11px;';
            if (i === 0) {
                cellStyle += 'text-align:left;';
            } else if (isLast) {
                cellStyle += 'text-align:center; font-weight:800; color:#16a34a; font-size:13px;';
            } else {
                cellStyle += 'text-align:center;';
            }
            footHtml += '<td style="' + cellStyle + '">' + val + '</td>';
        });
        footHtml += '</tr>';

        const dateInfo = document.querySelector('[wire\\:model\\.live="dateFrom"]');
        const dateToInfo = document.querySelector('[wire\\:model\\.live="dateTo"]');
        let periodText = '';
        if (dateInfo && dateInfo.value) {
            const from = dateInfo.value.split('-').reverse().join('/');
            const to = dateToInfo && dateToInfo.value ? dateToInfo.value.split('-').reverse().join('/') : 'hoje';
            periodText = '<p style="font-size:12px; color:#666; margin:4px 0 16px;">Período: ' + from + ' a ' + to + '</p>';
        }

        const printWindow = window.open('', '_blank', 'width=900,height=700');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Atendimentos por Agente × Estado</title>
                <style>
                    @page { size: landscape; margin: 15mm; }
                    body { font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 20px; color: #111; }
                    table { width: 100%; border-collapse: collapse; }
                    @media print { body { padding: 0; } }
                </style>
            </head>
            <body>
                <h2 style="font-size:16px; font-weight:800; color:#111; margin:0 0 4px;">ATENDIMENTOS POR AGENTE × ESTADO</h2>
                ${periodText}
                <table>
                    <thead>${headerHtml}</thead>
                    <tbody>${bodyHtml}</tbody>
                    <tfoot>${footHtml}</tfoot>
                </table>
                <p style="font-size:9px; color:#999; margin-top:16px; text-align:right;">Gerado em ${new Date().toLocaleDateString('pt-BR')} às ${new Date().toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'})}</p>
            </body>
            </html>
        `);
        printWindow.document.close();
        setTimeout(() => { printWindow.print(); }, 300);
    }
    </script>
    @endif
</div>
