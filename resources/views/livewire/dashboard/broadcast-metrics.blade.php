<div wire:poll.120s>
    <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:14px;" class="mobile-grid-2">
        @php
        $cards = [
            ['label' => 'Total Campanhas',   'value' => $stats['total_campaigns'],  'color' => '#ec4899', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            ['label' => 'Campanhas Ativas',  'value' => $stats['active_campaigns'], 'color' => '#f59e0b', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
            ['label' => 'Mensagens Enviadas','value' => number_format($stats['total_sent'], 0, ',', '.'), 'color' => '#22c55e', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Taxa de Sucesso',   'value' => $stats['success_rate'] . '%', 'color' => '#3b82f6', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        ];
        @endphp

        @foreach($cards as $card)
        <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:18px; position:relative; overflow:hidden; transition:transform 0.2s, box-shadow 0.2s;"
             onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.3)'"
             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
            <div style="position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, {{ $card['color'] }}80, {{ $card['color'] }}20, transparent);"></div>
            <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:12px;">
                <span style="font-size:10px; font-weight:600; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:0.08em;">{{ $card['label'] }}</span>
                <div style="width:28px; height:28px; border-radius:8px; background:{{ $card['color'] }}18; border:1px solid {{ $card['color'] }}30; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="{{ $card['color'] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $card['icon'] }}"/></svg>
                </div>
            </div>
            <div style="font-size:28px; font-weight:800; color:{{ $card['color'] }}; line-height:1; margin-bottom:4px; font-family:'Syne',sans-serif; letter-spacing:-0.02em;">{{ $card['value'] }}</div>
        </div>
        @endforeach
    </div>
</div>
