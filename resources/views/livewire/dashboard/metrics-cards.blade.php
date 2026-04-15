<div wire:poll.60s>
    <div style="display:grid; grid-template-columns:repeat(5, 1fr); gap:14px;" class="mobile-grid-2">
        @php
        $cards = [
            ['label' => 'Minhas Conversas', 'value' => $stats['mine'],           'color' => '#b2ff00', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
            ['label' => 'Na Fila',          'value' => $stats['queue'],           'color' => '#f59e0b', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Resolvidas Hoje',  'value' => $stats['resolved_today'],  'color' => '#10b981', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Novos Contatos',   'value' => $newContacts,              'color' => '#3b82f6', 'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
            ['label' => 'Tempo Resposta',   'value' => $avgResponse !== null ? $avgResponse . ' min' : '—', 'color' => '#a855f7', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
        ];
        @endphp

        @foreach($cards as $card)
        <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:18px; position:relative; overflow:hidden; transition:transform 0.2s, box-shadow 0.2s;"
             onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.3)'"
             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
            <div style="position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg, {{ $card['color'] }}80, {{ $card['color'] }}20, transparent);"></div>
            <div style="position:absolute; top:-20px; right:-20px; width:80px; height:80px; background:radial-gradient(circle, {{ $card['color'] }}15 0%, transparent 70%); pointer-events:none;"></div>

            <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:12px;">
                <span style="font-size:10px; font-weight:600; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:0.08em;">{{ $card['label'] }}</span>
                <div style="width:28px; height:28px; border-radius:8px; background:{{ $card['color'] }}18; border:1px solid {{ $card['color'] }}30; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="{{ $card['color'] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $card['icon'] }}"/></svg>
                </div>
            </div>

            <div style="font-size:28px; font-weight:800; color:{{ $card['color'] }}; line-height:1; margin-bottom:4px; font-family:'Syne',sans-serif; letter-spacing:-0.02em;">{{ $card['value'] }}</div>
            <div style="font-size:11px; color:rgba(255,255,255,0.2);">hoje</div>
        </div>
        @endforeach
    </div>
</div>
