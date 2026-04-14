<div>
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:20px;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:18px;">
            <div style="width:2px; height:14px; background:#f59e0b; border-radius:2px;"></div>
            <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Resumo do CRM</h3>
        </div>

        @foreach($pipelines as $pipeline)
        <div style="margin-bottom:16px;">
            <p style="font-size:11px; font-weight:700; color:rgba(255,255,255,0.5); margin-bottom:10px;">{{ $pipeline->name }}</p>
            <div style="display:flex; gap:10px; overflow-x:auto;">
                @foreach($pipeline->stages as $stage)
                <div style="flex:1; min-width:120px; background:rgba(255,255,255,0.02); border:1px solid {{ $stage->color }}30; border-radius:12px; padding:14px; text-align:center;">
                    <div style="width:6px; height:6px; border-radius:50%; background:{{ $stage->color }}; margin:0 auto 8px;"></div>
                    <p style="font-size:10px; font-weight:600; color:{{ $stage->color }}; margin-bottom:6px;">{{ $stage->name }}</p>
                    <p style="font-size:22px; font-weight:800; color:white; font-family:'Syne',sans-serif; line-height:1;">{{ $stage->cards_count }}</p>
                    <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:2px;">cards</p>
                    @if($stage->total_value > 0)
                    <p style="font-size:11px; font-weight:700; color:#10b981; margin-top:6px;">R$ {{ number_format($stage->total_value, 2, ',', '.') }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
