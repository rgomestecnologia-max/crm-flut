<div wire:poll.60s>
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:20px; height:100%;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:18px;">
            <div style="width:2px; height:14px; background:#a855f7; border-radius:2px;"></div>
            <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.06em;">Atividade recente</h3>
        </div>

        <div style="display:flex; flex-direction:column; gap:0;">
            @forelse($activities as $log)
            <div style="display:flex; gap:12px; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.03);">
                <div style="display:flex; flex-direction:column; align-items:center; flex-shrink:0;">
                    <div style="width:8px; height:8px; border-radius:50%; background:{{ $log->action_color }}; margin-top:4px;"></div>
                    @if(!$loop->last)
                    <div style="width:1px; flex:1; background:rgba(255,255,255,0.06); margin-top:4px;"></div>
                    @endif
                </div>
                <div style="flex:1; min-width:0;">
                    <p style="font-size:11px; color:rgba(255,255,255,0.7); line-height:1.4;">
                        <strong style="color:rgba(255,255,255,0.9);">{{ $log->user_name }}</strong>
                        <span style="color:{{ $log->action_color }};">{{ strtolower($log->action_label) }}</span>
                        {{ $log->model_label }}
                        <span style="color:rgba(255,255,255,0.5);">{{ \Illuminate\Support\Str::limit($log->auditable_label, 30) }}</span>
                    </p>
                    <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:2px;">{{ $log->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @empty
            <p style="font-size:12px; color:rgba(255,255,255,0.25); text-align:center; padding:20px 0;">Nenhuma atividade registrada.</p>
            @endforelse
        </div>
    </div>
</div>
