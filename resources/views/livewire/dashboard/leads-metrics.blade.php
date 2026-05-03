<div wire:poll.120s>
    <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:14px;" class="mobile-grid-2">
        @php
        $cards = [
            ['label' => 'Leads Hoje',    'value' => $stats['today'], 'color' => '#22c55e', 'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
            ['label' => 'Leads Semana',  'value' => $stats['week'],  'color' => '#3b82f6', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ['label' => 'Leads Mes',     'value' => $stats['month'], 'color' => '#f59e0b', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['label' => 'Total Leads',   'value' => $stats['total'], 'color' => '#8b5cf6', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
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
