<div style="position:relative;" wire:poll.30s>
    {{-- Bell icon --}}
    <button wire:click="toggleDropdown"
            style="position:relative; width:36px; height:36px; border-radius:10px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.15s;"
            onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='rgba(255,255,255,0.04)'">
        <svg width="18" height="18" fill="none" stroke="{{ $unreadCount > 0 ? '#fbbf24' : 'rgba(255,255,255,0.4)' }}" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if($unreadCount > 0)
        <span style="position:absolute; top:-2px; right:-2px; min-width:16px; height:16px; padding:0 4px; border-radius:20px; background:#ef4444; color:white; font-size:9px; font-weight:700; display:flex; align-items:center; justify-content:center; line-height:1; border:2px solid #0b0f1c;">
            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
        </span>
        @endif
    </button>

    {{-- Dropdown --}}
    @if($showDropdown)
    <div style="position:fixed; top:60px; left:70px; z-index:9999; width:360px; max-height:450px; background:#0f1320; border:1px solid rgba(255,255,255,0.1); border-radius:14px; box-shadow:0 12px 40px rgba(0,0,0,0.6); overflow:hidden;">
        {{-- Header --}}
        <div style="padding:12px 16px; border-bottom:1px solid rgba(255,255,255,0.06); display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:13px; font-weight:700; color:white; font-family:Syne,sans-serif;">Notificações</span>
            @if($unreadCount > 0)
            <button wire:click="markAllAsRead"
                    style="font-size:10px; color:#60a5fa; background:none; border:none; cursor:pointer;"
                    onmouseover="this.style.color='#93bbfc'" onmouseout="this.style.color='#60a5fa'">
                Marcar todas como lidas
            </button>
            @endif
        </div>

        {{-- List --}}
        <div style="max-height:380px; overflow-y:auto;">
            @forelse($notifications as $notif)
            <div wire:click="markAsRead({{ $notif->id }})"
                 style="padding:12px 16px; border-bottom:1px solid rgba(255,255,255,0.03); cursor:pointer; transition:background 0.1s;
                        background:{{ $notif->is_read ? 'transparent' : 'rgba(59,130,246,0.04)' }};"
                 onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background='{{ $notif->is_read ? 'transparent' : 'rgba(59,130,246,0.04)' }}'">
                <div style="display:flex; align-items:flex-start; gap:10px;">
                    @php
                        $iconColor = match($notif->type) {
                            'whatsapp_disconnected' => '#ef4444',
                            'whatsapp_connected' => '#22c55e',
                            default => '#60a5fa',
                        };
                    @endphp
                    <div style="width:28px; height:28px; border-radius:8px; background:{{ $iconColor }}15; border:1px solid {{ $iconColor }}30; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px;">
                        @if($notif->type === 'whatsapp_disconnected')
                        <svg width="14" height="14" fill="none" stroke="{{ $iconColor }}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 11-12.728 0M12 9v4m0 4h.01"/></svg>
                        @elseif($notif->type === 'whatsapp_connected')
                        <svg width="14" height="14" fill="none" stroke="{{ $iconColor }}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                        <svg width="14" height="14" fill="none" stroke="{{ $iconColor }}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                        @endif
                    </div>
                    <div style="flex:1; min-width:0;">
                        <p style="font-size:12px; font-weight:{{ $notif->is_read ? '400' : '600' }}; color:{{ $notif->is_read ? 'rgba(255,255,255,0.5)' : 'white' }};">{{ $notif->title }}</p>
                        @if($notif->message)
                        <p style="font-size:11px; color:rgba(255,255,255,0.3); margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $notif->message }}</p>
                        @endif
                        <p style="font-size:9px; color:rgba(255,255,255,0.2); margin-top:4px;">{{ $notif->created_at->diffForHumans() }}</p>
                    </div>
                    @if(!$notif->is_read)
                    <div style="width:8px; height:8px; border-radius:50%; background:#3b82f6; flex-shrink:0; margin-top:6px;"></div>
                    @endif
                </div>
            </div>
            @empty
            <div style="padding:40px 16px; text-align:center;">
                <svg width="32" height="32" fill="none" stroke="rgba(255,255,255,0.1)" viewBox="0 0 24 24" style="margin:0 auto 8px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <p style="font-size:12px; color:rgba(255,255,255,0.2);">Nenhuma notificação</p>
            </div>
            @endforelse
        </div>
    </div>
    @endif
</div>
