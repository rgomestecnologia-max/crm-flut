<div wire:poll.120s>
    <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:14px;" class="mobile-grid-2">
        @php
        $cards = [
            ['label' => 'Cards Ativos',     'value' => $stats['active_cards'],  'color' => '#8b5cf6', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['label' => 'Criados Hoje',     'value' => $stats['created_today'], 'color' => '#22c55e', 'icon' => 'M12 4v16m8-8H4'],
            ['label' => 'Valor Total',      'value' => 'R$ ' . number_format($stats['total_value'], 0, ',', '.'), 'color' => '#f59e0b', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Pipelines',        'value' => $stats['pipelines'],     'color' => '#3b82f6', 'icon' => 'M4 6h16M4 12h16M4 18h7'],
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
