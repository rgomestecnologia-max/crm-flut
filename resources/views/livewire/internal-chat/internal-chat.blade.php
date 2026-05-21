<div style="display:flex; height:100%; overflow:hidden;">
    {{-- Lista de agentes --}}
    <div style="width:280px; flex-shrink:0; border-right:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; background:rgba(8,12,22,0.5);">
        <div style="padding:14px; border-bottom:1px solid rgba(255,255,255,0.05);">
            <h2 style="font-size:14px; font-weight:800; color:white; font-family:'Syne',sans-serif;">Chat Interno</h2>
            <p style="font-size:10px; color:rgba(255,255,255,0.3); margin-top:2px;">{{ $agents->count() }} agentes</p>
        </div>
        <div style="flex:1; overflow-y:auto;">
            @foreach($agents as $agent)
            <button wire:click="selectUser({{ $agent->id }})"
                    style="width:100%; text-align:left; padding:10px 14px; border:none; cursor:pointer; transition:background 0.1s; display:flex; align-items:center; gap:10px;
                           background:{{ $selectedUserId === $agent->id ? 'rgba(178,255,0,0.06)' : 'transparent' }};
                           border-left:3px solid {{ $selectedUserId === $agent->id ? '#b2ff00' : 'transparent' }};"
                    onmouseover="if({{ $selectedUserId === $agent->id ? 'false' : 'true' }}) this.style.background='rgba(255,255,255,0.03)'"
                    onmouseout="if({{ $selectedUserId === $agent->id ? 'false' : 'true' }}) this.style.background='transparent'">
                <div style="position:relative; flex-shrink:0;">
                    <img src="{{ $agent->avatar_url }}" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.08);">
                    <span style="position:absolute; bottom:0; right:0; width:8px; height:8px; border-radius:50%; border:2px solid #0B0F1C; background:{{ $agent->isOnline() ? '#22c55e' : '#6b7280' }};"></span>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <p style="font-size:12px; font-weight:600; color:white; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $agent->name }}</p>
                        @if($agent->unread_count > 0)
                        <span style="min-width:18px; height:18px; padding:0 5px; border-radius:20px; background:#b2ff00; color:#111; font-size:9px; font-weight:800; display:flex; align-items:center; justify-content:center;">{{ $agent->unread_count }}</span>
                        @endif
                    </div>
                    <p style="font-size:10px; color:rgba(255,255,255,0.25); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $agent->last_internal_msg ? \Illuminate\Support\Str::limit($agent->last_internal_msg->content, 30) : ($agent->isOnline() ? 'Online' : 'Offline') }}
                    </p>
                </div>
            </button>
            @endforeach
        </div>
    </div>

    {{-- Área de conversa --}}
    <div style="flex:1; display:flex; flex-direction:column; background:rgba(8,12,22,0.3);">
        @if($selectedUser)
            {{-- Header --}}
            <div style="padding:12px 16px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; gap:10px; flex-shrink:0;">
                <img src="{{ $selectedUser->avatar_url }}" style="width:32px; height:32px; border-radius:50%; object-fit:cover;">
                <div>
                    <p style="font-size:13px; font-weight:700; color:white;">{{ $selectedUser->name }}</p>
                    <p style="font-size:10px; color:{{ $selectedUser->isOnline() ? '#4ade80' : 'rgba(255,255,255,0.3)' }};">{{ $selectedUser->isOnline() ? 'Online' : 'Offline' }}</p>
                </div>
            </div>

            {{-- Mensagens --}}
            <div style="flex:1; overflow-y:auto; padding:16px;" x-data x-on:internal-scroll-bottom.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)" x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)">
                @foreach($messages as $msg)
                @php $isMe = $msg->sender_id === auth()->id(); @endphp
                <div style="display:flex; justify-content:{{ $isMe ? 'flex-end' : 'flex-start' }}; margin-bottom:8px;">
                    <div style="max-width:70%; padding:8px 12px; border-radius:{{ $isMe ? '14px 14px 4px 14px' : '14px 14px 14px 4px' }};
                                background:{{ $isMe ? '#2d4a08' : 'rgba(31,41,55,0.8)' }}; color:white; font-size:13px; line-height:1.5;">
                        @if($msg->type === 'image' && $msg->media_url)
                            <img src="{{ $msg->media_url }}" style="max-width:200px; border-radius:8px; margin-bottom:4px; cursor:pointer;" onclick="window.open('{{ $msg->media_url }}')">
                        @elseif($msg->type === 'document' && $msg->media_url)
                            <a href="{{ route('media.download', $msg->id) }}" style="display:flex; align-items:center; gap:6px; color:#60a5fa; text-decoration:none; font-size:12px;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ $msg->media_filename ?? 'Arquivo' }}
                            </a>
                        @endif
                        @if($msg->content && $msg->type === 'text')
                            {!! nl2br(e($msg->content)) !!}
                        @endif
                        <p style="font-size:9px; color:rgba(255,255,255,0.3); margin-top:4px; text-align:right;">{{ $msg->created_at->format('H:i') }}</p>
                    </div>
                </div>
                @endforeach

                @if($messages->isEmpty())
                <div style="text-align:center; padding:40px; color:rgba(255,255,255,0.2);">
                    <p style="font-size:13px;">Nenhuma mensagem ainda</p>
                    <p style="font-size:11px; margin-top:4px;">Envie uma mensagem para {{ $selectedUser->name }}</p>
                </div>
                @endif
            </div>

            {{-- Input --}}
            <div style="padding:10px 12px; border-top:1px solid rgba(255,255,255,0.05); flex-shrink:0; display:flex; align-items:center; gap:8px;">
                <label style="cursor:pointer; color:rgba(255,255,255,0.3); padding:6px; transition:color 0.15s;" onmouseover="this.style.color='#60a5fa'" onmouseout="this.style.color='rgba(255,255,255,0.3)'">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    <input type="file" wire:model="attachment" style="display:none;" x-on:change="$wire.sendFile()">
                </label>
                <input wire:model="messageText" type="text" placeholder="Digite uma mensagem..."
                       x-ref="internalInput"
                       x-on:keydown.enter="$wire.sendMessage().then(() => { $refs.internalInput.value = ''; })"
                       x-on:internal-scroll-bottom.window="$nextTick(() => { $refs.internalInput.value = ''; $refs.internalInput.focus(); })"
                       style="flex:1; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:8px 14px; font-size:13px; color:white; outline:none;"
                       onfocus="this.style.borderColor='rgba(178,255,0,0.4)'" onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
                <button wire:click="sendMessage" style="padding:8px; color:#b2ff00; background:none; border:none; cursor:pointer;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </div>
        @else
            <div style="flex:1; display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.2);">
                <div style="text-align:center;">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 12px; opacity:0.3;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <p style="font-size:14px;">Selecione um agente para conversar</p>
                </div>
            </div>
        @endif
    </div>
</div>
