<div wire:poll.120s>
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:20px;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:18px;">
            <div style="width:2px; height:14px; background:#b2ff00; border-radius:2px;"></div>
            <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Conversas por departamento</h3>
        </div>

        @forelse($departments as $dept)
        <div style="margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                <span style="font-size:12px; font-weight:600; color:{{ $dept->color }};">{{ $dept->name }}</span>
                <span style="font-size:12px; font-weight:700; color:rgba(255,255,255,0.6);">{{ $dept->total }}</span>
            </div>
            <div style="height:8px; background:rgba(255,255,255,0.04); border-radius:4px; overflow:hidden;">
                <div style="height:100%; width:{{ round(($dept->total / $max) * 100) }}%; background:{{ $dept->color }}; border-radius:4px; transition:width 0.5s;"></div>
            </div>
        </div>
        @empty
        <p style="font-size:12px; color:rgba(255,255,255,0.25); text-align:center; padding:20px 0;">Nenhuma conversa aberta.</p>
        @endforelse
    </div>
</div>
