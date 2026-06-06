<div style="display:flex; height:100%; overflow:hidden;">
    {{-- Lista de agentes --}}
    <div style="width:280px; flex-shrink:0; border-right:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; background:rgba(8,12,22,0.5);">
        <div style="padding:14px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:space-between;">
            <div>
                <h2 style="font-size:14px; font-weight:800; color:white; font-family:'Syne',sans-serif;">Chat Interno</h2>
                <p style="font-size:10px; color:rgba(255,255,255,0.3); margin-top:2px;">{{ $agents->count() }} agentes · {{ $groups->count() }} grupos</p>
            </div>
            <button wire:click="$set('showGroupModal', true)" title="Novo grupo" style="width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:rgba(178,255,0,0.1); border:none; cursor:pointer; color:#b2ff00;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </button>
        </div>
        <div style="flex:1; overflow-y:auto;">
            {{-- Grupos --}}
            @foreach($groups as $group)
            <button wire:click="selectGroup({{ $group->id }})"
                    style="width:100%; text-align:left; padding:10px 14px; border:none; cursor:pointer; transition:background 0.1s; display:flex; align-items:center; gap:10px;
                           background:{{ $selectedGroupId === $group->id ? 'rgba(178,255,0,0.06)' : 'transparent' }};
                           border-left:3px solid {{ $selectedGroupId === $group->id ? '#b2ff00' : 'transparent' }};"
                    onmouseover="if({{ $selectedGroupId === $group->id ? 'false' : 'true' }}) this.style.background='rgba(255,255,255,0.03)'"
                    onmouseout="if({{ $selectedGroupId === $group->id ? 'false' : 'true' }}) this.style.background='transparent'">
                <div style="width:36px; height:36px; border-radius:50%; background:rgba(139,92,246,0.2); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="16" height="16" fill="none" stroke="#a78bfa" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <p style="font-size:12px; font-weight:600; color:white; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $group->name }}</p>
                        @if($group->unread_count > 0)
                        <span style="min-width:18px; height:18px; padding:0 5px; border-radius:20px; background:#a78bfa; color:#111; font-size:9px; font-weight:800; display:flex; align-items:center; justify-content:center;">{{ $group->unread_count }}</span>
                        @endif
                    </div>
                    <p style="font-size:10px; color:rgba(255,255,255,0.25); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $group->members->count() }} membros
                    </p>
                </div>
            </button>
            @endforeach
            @if($groups->isNotEmpty() && $agents->isNotEmpty())
            <div style="height:1px; background:rgba(255,255,255,0.04); margin:4px 14px;"></div>
            @endif
            {{-- Agentes (1-a-1) --}}
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
        @if($selectedGroup)
            {{-- Header do Grupo --}}
            <div style="padding:12px 16px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; gap:10px; flex-shrink:0;">
                <div style="width:32px; height:32px; border-radius:50%; background:rgba(139,92,246,0.2); display:flex; align-items:center; justify-content:center;">
                    <svg width="14" height="14" fill="none" stroke="#a78bfa" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <p style="font-size:13px; font-weight:700; color:white;">{{ $selectedGroup->name }}</p>
                    <p style="font-size:10px; color:rgba(255,255,255,0.3);">{{ $selectedGroup->members->pluck('name')->map(fn($n) => \Illuminate\Support\Str::before($n, ' '))->implode(', ') }}</p>
                </div>
            </div>

            {{-- Mensagens do grupo --}}
            <div style="flex:1; overflow-y:auto; padding:16px;" x-data x-on:internal-scroll-bottom.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)" x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)">
                @foreach($messages as $msg)
                @php $isMe = $msg->sender_id === auth()->id(); @endphp
                <div style="display:flex; justify-content:{{ $isMe ? 'flex-end' : 'flex-start' }}; margin-bottom:8px;">
                    <div style="max-width:70%; padding:8px 12px; border-radius:{{ $isMe ? '14px 14px 4px 14px' : '14px 14px 14px 4px' }};
                                background:{{ $isMe ? 'rgba(178,255,0,0.1)' : 'rgba(255,255,255,0.04)' }};
                                border:1px solid {{ $isMe ? 'rgba(178,255,0,0.15)' : 'rgba(255,255,255,0.06)' }};">
                        @if(!$isMe)
                        <p style="font-size:10px; font-weight:700; color:#a78bfa; margin-bottom:2px;">{{ $msg->sender?->name ?? '?' }}</p>
                        @endif
                        @if($msg->type === 'image')
                            <img src="{{ $msg->media_url }}" style="max-width:200px; border-radius:8px; cursor:pointer;" onclick="window.open(this.src)">
                        @elseif($msg->type === 'audio')
                            <audio controls src="{{ $msg->media_url }}" style="max-width:220px; height:32px;"></audio>
                        @elseif($msg->type === 'document')
                            <a href="{{ $msg->media_url }}" target="_blank" style="color:#60a5fa; font-size:12px; text-decoration:none;">📎 {{ $msg->media_filename ?? 'Documento' }}</a>
                        @else
                            <p style="font-size:13px; color:{{ $isMe ? 'rgba(255,255,255,0.85)' : 'rgba(255,255,255,0.7)' }}; white-space:pre-wrap; word-break:break-word;">{{ $msg->content }}</p>
                        @endif
                        <p style="font-size:9px; color:rgba(255,255,255,0.2); margin-top:3px; text-align:{{ $isMe ? 'right' : 'left' }};">{{ $msg->created_at->format('H:i') }}</p>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Input do grupo --}}
            <div style="padding:10px 16px; border-top:1px solid rgba(255,255,255,0.05); flex-shrink:0;">
                <form wire:submit="sendMessage" style="display:flex; gap:8px;">
                    <input wire:model="messageText" type="text" placeholder="Mensagem para o grupo..."
                           style="flex:1; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:10px 14px; font-size:13px; color:white; outline:none;"
                           onfocus="this.style.borderColor='rgba(178,255,0,0.4)'" onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
                    <button type="submit" style="padding:10px 16px; background:#b2ff00; color:#111; font-weight:700; font-size:12px; border:none; border-radius:10px; cursor:pointer;">Enviar</button>
                </form>
            </div>

        @elseif($selectedUser)
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
                <div style="display:flex; justify-content:{{ $isMe ? 'flex-end' : 'flex-start' }}; margin-bottom:8px;"
                     x-data="{ showActions: false, editing: false, editText: '{{ addslashes($msg->content ?? '') }}' }">
                    <div style="max-width:70%; padding:8px 12px; border-radius:{{ $isMe ? '14px 14px 4px 14px' : '14px 14px 14px 4px' }};
                                background:{{ $isMe ? '#2d4a08' : 'rgba(31,41,55,0.8)' }}; color:white; font-size:13px; line-height:1.5; position:relative;"
                         @mouseenter="showActions = true" @mouseleave="showActions = false">
                        @if($msg->type === 'image' && $msg->media_url)
                            <img src="{{ $msg->media_url }}" alt="Imagem"
                                 @click="$dispatch('open-lightbox', { src: '{{ $msg->media_url }}' })"
                                 style="max-width:220px; border-radius:8px; margin-bottom:4px; cursor:zoom-in; display:block; transition:opacity 0.2s;"
                                 onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                        @elseif($msg->type === 'video' && $msg->media_url)
                            <div @click="$dispatch('open-lightbox', { src: '{{ $msg->media_url }}', video: true })" style="cursor:pointer; position:relative; max-width:220px;">
                                <div style="width:100%; height:130px; background:rgba(0,0,0,0.4); border-radius:8px; display:flex; align-items:center; justify-content:center; margin-bottom:4px;">
                                    <div style="width:40px; height:40px; border-radius:50%; background:rgba(255,255,255,0.15); backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center;">
                                        <svg width="18" height="18" fill="white" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                                @if($msg->media_filename)
                                    <p style="font-size:10px; color:rgba(255,255,255,0.4);">{{ $msg->media_filename }}</p>
                                @endif
                            </div>
                        @elseif($msg->type === 'document' && $msg->media_url)
                            @php
                                $icDocFile  = $msg->media_filename ?? 'Documento';
                                $icDocExt   = strtolower(pathinfo($icDocFile, PATHINFO_EXTENSION));
                                $icDocCanPv = $msg->media_url && ($icDocExt === 'pdf' || in_array($icDocExt, ['doc','docx','xls','xlsx','ppt','pptx']));
                                $icDocPvUrl = in_array($icDocExt, ['doc','docx','xls','xlsx','ppt','pptx'])
                                    ? 'https://docs.google.com/viewer?url='.urlencode($msg->media_url).'&embedded=true'
                                    : $msg->media_url;
                                $icDocColor = match(true) {
                                    $icDocExt === 'pdf'                          => '#ef4444',
                                    in_array($icDocExt, ['doc','docx'])          => '#3b82f6',
                                    in_array($icDocExt, ['xls','xlsx','csv'])    => '#22c55e',
                                    in_array($icDocExt, ['ppt','pptx'])          => '#f97316',
                                    default                                      => '#b2ff00',
                                };
                            @endphp
                            <div x-data="{ pvOpen: false }" style="position:relative;">
                                <div style="display:flex; align-items:center; gap:8px; padding:8px 12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.06); border-radius:10px;">
                                    <div style="width:30px; height:30px; border-radius:7px; background:{{ $icDocColor }}1a; border:1px solid {{ $icDocColor }}33; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <svg width="14" height="14" fill="none" stroke="{{ $icDocColor }}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div style="flex:1; min-width:0;">
                                        <p style="font-size:11px; color:rgba(255,255,255,0.7); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $icDocFile }}</p>
                                        @if($icDocExt)<p style="font-size:9px; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.06em; margin-top:1px;">{{ $icDocExt }}</p>@endif
                                    </div>
                                    <div style="display:flex; gap:5px; flex-shrink:0;">
                                        @if($icDocCanPv)
                                        <button @click.stop="pvOpen = true"
                                                style="font-size:10px; color:rgba(255,255,255,0.55); background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:5px; padding:3px 8px; cursor:pointer;"
                                                onmouseover="this.style.color='white'; this.style.background='rgba(255,255,255,0.1)'"
                                                onmouseout="this.style.color='rgba(255,255,255,0.55)'; this.style.background='rgba(255,255,255,0.05)'">Ver</button>
                                        @endif
                                        <a href="{{ $msg->media_url }}" download style="font-size:10px; color:#b2ff00; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:5px; padding:3px 8px; text-decoration:none; font-weight:600;">↓</a>
                                    </div>
                                </div>
                                @if($icDocCanPv)
                                <template x-teleport="body">
                                <div x-show="pvOpen" x-cloak @click.self="pvOpen = false"
                                     style="position:fixed; top:0; left:0; right:0; bottom:0; z-index:9999; background:rgba(0,0,0,0.88); display:flex; align-items:center; justify-content:center; padding:20px;">
                                    <div style="width:100%; max-width:min(900px, 95vw); height:86vh; background:#0f172a; border-radius:16px; overflow:hidden; display:flex; flex-direction:column; border:1px solid rgba(255,255,255,0.08); box-shadow:0 24px 80px rgba(0,0,0,0.6);">
                                        <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid rgba(255,255,255,0.06); flex-shrink:0;">
                                            <p style="font-size:13px; color:rgba(255,255,255,0.6); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:70%;">{{ $icDocFile }}</p>
                                            <div style="display:flex; gap:8px;">
                                                <a href="{{ $msg->media_url }}" download
                                                   style="font-size:12px; color:#b2ff00; background:rgba(178,255,0,0.1); border:1px solid rgba(45,74,8,0.6); border-radius:8px; padding:5px 14px; text-decoration:none; font-weight:600;">Download</a>
                                                <button @click="pvOpen = false"
                                                        style="width:30px; height:30px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.5); cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center;"
                                                        onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.color='white'"
                                                        onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.color='rgba(255,255,255,0.5)'">✕</button>
                                            </div>
                                        </div>
                                        <iframe x-bind:src="pvOpen ? '{{ $icDocPvUrl }}' : ''"
                                                style="flex:1; width:100%; border:none; background:white;" allow="fullscreen"></iframe>
                                    </div>
                                </div>
                                </template>
                                @endif
                            </div>
                        @elseif($msg->type === 'audio' && $msg->media_url)
                            <div style="display:flex; align-items:center; gap:8px; padding:6px 10px; background:rgba(255,255,255,0.06); border-radius:8px;">
                                <svg width="16" height="16" fill="none" stroke="#60a5fa" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                                <audio src="{{ $msg->media_url }}" controls preload="none" style="flex:1; height:32px;"></audio>
                            </div>
                        @endif
                        @if($msg->content && $msg->type === 'text')
                            <div x-show="!editing">{!! nl2br(e($msg->content)) !!}</div>
                            <div x-show="editing" x-cloak style="display:flex; gap:6px; align-items:center;">
                                <input type="text" x-model="editText" @keydown.enter="$wire.editInternalMessage({{ $msg->id }}, editText); editing=false" @keydown.escape="editing=false"
                                       x-ref="editInput{{ $msg->id }}"
                                       style="flex:1; background:rgba(255,255,255,0.1); border:1px solid rgba(178,255,0,0.3); border-radius:6px; padding:4px 8px; font-size:12px; color:white; outline:none;">
                                <button @click="$wire.editInternalMessage({{ $msg->id }}, editText); editing=false" style="font-size:10px; color:#b2ff00; background:rgba(178,255,0,0.15); border:1px solid rgba(178,255,0,0.3); border-radius:5px; padding:3px 8px; cursor:pointer;">✓</button>
                                <button @click="editing=false" style="font-size:10px; color:rgba(255,255,255,0.4); background:none; border:none; cursor:pointer;">✕</button>
                            </div>
                        @endif

                        {{-- Ações (hover) --}}
                        <div x-show="showActions && !editing" x-transition
                             style="position:absolute; top:4px; {{ $isMe ? 'left:-28px' : 'right:-28px' }}; display:flex; gap:2px;">
                            @if($isMe)
                                @if($msg->type === 'text')
                                <button @click.stop="editing=true; showActions=false" title="Editar"
                                        style="width:24px; height:24px; border-radius:6px; background:rgba(0,0,0,0.5); border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.5);"
                                        onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">
                                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                @endif
                                <button @click.stop="if(confirm('Excluir esta mensagem?')) $wire.deleteInternalMessage({{ $msg->id }})" title="Excluir"
                                        style="width:24px; height:24px; border-radius:6px; background:rgba(0,0,0,0.5); border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.5);"
                                        onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">
                                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            @endif
                        </div>

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
            <div style="padding:10px 12px; border-top:1px solid rgba(255,255,255,0.05); flex-shrink:0; position:relative;"
                 x-data="{
                    showEmoji: false,
                    recording: false, recSeconds: 0, recTimer: null, mediaRecorder: null, audioChunks: [],
                    async startRec() {
                        try {
                            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                            this.audioChunks = [];
                            this.mediaRecorder = new MediaRecorder(stream);
                            this.mediaRecorder.ondataavailable = e => { if(e.data.size>0) this.audioChunks.push(e.data); };
                            this.mediaRecorder.onstop = () => {
                                const mimeType = this.mediaRecorder.mimeType || 'audio/webm';
                                const blob = new Blob(this.audioChunks, { type: mimeType });
                                const reader = new FileReader();
                                reader.onload = () => $wire.receiveAudioBlob(reader.result);
                                reader.readAsDataURL(blob);
                                stream.getTracks().forEach(t => t.stop());
                            };
                            this.mediaRecorder.start();
                            this.recording = true; this.recSeconds = 0;
                            this.recTimer = setInterval(() => this.recSeconds++, 1000);
                        } catch(e) { alert('Permissão de microfone negada.'); }
                    },
                    stopRec() { if(this.mediaRecorder && this.recording) { this.mediaRecorder.stop(); this.recording = false; clearInterval(this.recTimer); } },
                    cancelRec() { if(this.mediaRecorder) { this.mediaRecorder.ondataavailable=null; this.mediaRecorder.onstop=null; this.mediaRecorder.stop(); this.mediaRecorder.stream?.getTracks().forEach(t=>t.stop()); } this.recording=false; clearInterval(this.recTimer); this.audioChunks=[]; },
                    fmtRec(s) { return Math.floor(s/60)+':'+(''+(s%60)).padStart(2,'0'); }
                 }">
                {{-- Emoji picker --}}
                <div x-show="showEmoji" x-transition @click.outside="showEmoji = false"
                     style="position:absolute; bottom:52px; right:10px; background:#1a1f2e; border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:10px; width:320px; z-index:50; box-shadow:0 8px 32px rgba(0,0,0,0.5);">
                    <div style="display:grid; grid-template-columns:repeat(9, 1fr); gap:2px; max-height:200px; overflow-y:auto;">
                        @foreach(['😀','😂','🤣','😊','😍','🥰','😘','😎','🤩','🥳','😇','🤔','🤗','😅','😆','😁','😉','😋','😜','🤪','😝','🤑','🤭','🫡','🤫','🫣','😬','😌','😴','🤤','😷','🤒','🤕','🥴','😵','🤯','🥶','🥵','😱','😨','😰','😢','😭','😤','😠','🤬','💀','💩','👻','👽','🤖','😺','😸','😹','😻','😼','😽','🙀','😿','😾','👋','🤚','✋','🖐️','👌','🤌','🤏','✌️','🤞','🫰','🤟','🤘','🤙','👈','👉','👆','👇','☝️','👍','👎','✊','👊','🤛','🤜','👏','🙌','🫶','👐','🤲','🤝','🙏','✍️','💪','❤️','🧡','💛','💚','💙','💜','🖤','🤍','💔','❣️','💕','💞','💓','💗','💖','💘','💝','⭐','🌟','✨','💫','🔥','💯','✅','❌','⚡','🎉','🎊','🏆','📌','📍','💰','📱','💻','📧','📞','🕐','📅'] as $emoji)
                            <button type="button"
                                    @click.stop="$wire.set('messageText', ($wire.messageText||'') + '{{ $emoji }}'); $refs.internalInput.value = ($refs.internalInput.value||'') + '{{ $emoji }}'; showEmoji=false; $refs.internalInput.focus();"
                                    style="font-size:18px; padding:4px; border-radius:6px; border:none; background:transparent; cursor:pointer; text-align:center; line-height:1; transition:background 0.1s;"
                                    onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">{{ $emoji }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- Preview de arquivo pendente --}}
                @if($attachment)
                <div style="display:flex; align-items:center; gap:10px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07); border-radius:12px; padding:8px 12px; margin-bottom:8px;">
                    @php $aMime = $attachment->getMimeType() ?? ''; @endphp
                    @if(str_starts_with($aMime, 'image/'))
                        <img src="{{ $attachment->temporaryUrl() }}" style="width:44px; height:44px; border-radius:8px; object-fit:cover; flex-shrink:0;">
                    @else
                        <div style="width:36px; height:36px; border-radius:8px; background:rgba(96,165,250,0.15); border:1px solid rgba(96,165,250,0.3); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg width="16" height="16" fill="none" stroke="#60a5fa" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                    @endif
                    <div style="flex:1; min-width:0;">
                        <p style="font-size:12px; color:rgba(255,255,255,0.8); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $attachment->getClientOriginalName() }}</p>
                        <p style="font-size:10px; color:rgba(255,255,255,0.3); margin-top:1px;">{{ number_format($attachment->getSize() / 1024, 1) }} KB</p>
                    </div>
                    <button wire:click="sendFile" wire:loading.attr="disabled"
                            style="padding:6px 12px; background:rgba(178,255,0,0.15); border:1px solid rgba(178,255,0,0.3); color:#b2ff00; font-size:11px; font-weight:600; border-radius:8px; cursor:pointer;">
                        <span wire:loading.remove wire:target="sendFile">Enviar</span>
                        <span wire:loading wire:target="sendFile">Enviando...</span>
                    </button>
                    <button wire:click="cancelFile" style="color:rgba(255,255,255,0.2); background:transparent; border:none; cursor:pointer; padding:4px;"
                            onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='rgba(255,255,255,0.2)'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @endif

                {{-- Indicador de gravação --}}
                <div x-show="recording" style="display:flex; align-items:center; gap:10px; background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.2); border-radius:10px; padding:8px 12px; margin-bottom:8px;">
                    <span style="width:8px; height:8px; border-radius:50%; background:#ef4444; flex-shrink:0; animation:pulse 1.5s ease-in-out infinite;"></span>
                    <span style="font-size:12px; color:#f87171; font-family:monospace; flex:1;" x-text="'Gravando... ' + fmtRec(recSeconds)"></span>
                    <button @click="cancelRec()" style="font-size:11px; color:rgba(255,255,255,0.3); background:transparent; border:none; cursor:pointer; padding:2px 8px;"
                            onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='rgba(255,255,255,0.3)'">Cancelar</button>
                    <button @click="stopRec()" style="display:flex; align-items:center; gap:5px; font-size:11px; font-weight:600; background:#ef4444; color:white; padding:5px 10px; border-radius:7px; border:none; cursor:pointer;">
                        <svg width="10" height="10" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12"/></svg>
                        Enviar
                    </button>
                </div>
                <style>@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.3} }</style>

                <div style="display:flex; align-items:center; gap:8px;">
                    {{-- Imagem --}}
                    <label title="Enviar imagem" style="cursor:pointer; color:rgba(255,255,255,0.3); padding:6px; transition:color 0.15s;" onmouseover="this.style.color='#4ade80'" onmouseout="this.style.color='rgba(255,255,255,0.3)'">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <input type="file" wire:model="attachment" accept="image/*" style="display:none;">
                    </label>
                    {{-- Documento --}}
                    <label title="Enviar documento" style="cursor:pointer; color:rgba(255,255,255,0.3); padding:6px; transition:color 0.15s;" onmouseover="this.style.color='#60a5fa'" onmouseout="this.style.color='rgba(255,255,255,0.3)'">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <input type="file" wire:model="attachment" style="display:none;">
                    </label>
                    <input wire:model="messageText" type="text" placeholder="Digite uma mensagem..." spellcheck="true" lang="pt-BR"
                           x-ref="internalInput"
                           x-on:keydown.enter="$wire.sendMessage().then(() => { $refs.internalInput.value = ''; })"
                           x-on:internal-scroll-bottom.window="$nextTick(() => { $refs.internalInput.value = ''; $refs.internalInput.focus(); })"
                           style="flex:1; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:8px 14px; font-size:13px; color:white; outline:none;"
                           onfocus="this.style.borderColor='rgba(178,255,0,0.4)'" onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
                    {{-- Emoji --}}
                    <button type="button" @click.stop="showEmoji = !showEmoji" title="Emojis"
                            style="padding:6px; color:rgba(255,255,255,0.3); background:none; border:none; cursor:pointer; transition:color 0.15s;"
                            onmouseover="this.style.color='#fbbf24'" onmouseout="this.style.color='rgba(255,255,255,0.3)'">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                    {{-- Mic --}}
                    <button type="button" @click="recording ? stopRec() : startRec()" title="Gravar áudio"
                            :style="recording ? 'background:#ef4444; animation:pulse 1.5s ease-in-out infinite;' : ''"
                            style="padding:6px; color:rgba(255,255,255,0.3); background:none; border:none; cursor:pointer; transition:color 0.15s;"
                            onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='rgba(255,255,255,0.3)'">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-7a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                    </button>
                    {{-- Send --}}
                    <button wire:click="sendMessage" style="padding:8px; color:#b2ff00; background:none; border:none; cursor:pointer;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </div>
            </div>
        @else
            <div style="flex:1; display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.2);">
                <div style="text-align:center;">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 12px; opacity:0.3;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <p style="font-size:14px;">Selecione um agente ou grupo para conversar</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Modal Criar Grupo --}}
    @if($showGroupModal)
    <div style="position:fixed; inset:0; z-index:999; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px);" wire:click.self="$set('showGroupModal', false)">
        <div style="background:#0f1320; border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:24px; width:400px; max-width:90vw; max-height:80vh; overflow-y:auto;">
            <h3 style="font-size:15px; font-weight:700; color:white; margin-bottom:16px;">Novo Grupo</h3>

            <div style="margin-bottom:14px;">
                <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px; text-transform:uppercase; font-weight:600;">Nome do grupo *</label>
                <input wire:model="groupName" type="text" placeholder="Ex: Equipe Comercial"
                       style="width:100%; padding:10px 12px; font-size:13px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:white; outline:none; box-sizing:border-box;">
            </div>

            <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:8px; text-transform:uppercase; font-weight:600;">Membros</label>
            <div style="max-height:250px; overflow-y:auto;">
                @foreach($agents as $agent)
                @php $selected = in_array($agent->id, $groupMemberIds); @endphp
                <button wire:click="toggleGroupMember({{ $agent->id }})"
                        style="width:100%; text-align:left; padding:8px 10px; background:{{ $selected ? 'rgba(178,255,0,0.06)' : 'transparent' }}; border:none; border-bottom:1px solid rgba(255,255,255,0.04); cursor:pointer; display:flex; align-items:center; gap:10px; color:white;">
                    <div style="width:18px; height:18px; border-radius:4px; border:2px solid {{ $selected ? '#b2ff00' : 'rgba(255,255,255,0.15)' }}; background:{{ $selected ? '#b2ff00' : 'transparent' }}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        @if($selected)<svg width="10" height="10" fill="#111" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>@endif
                    </div>
                    <img src="{{ $agent->avatar_url }}" style="width:28px; height:28px; border-radius:50%; object-fit:cover;">
                    <span style="font-size:12px;">{{ $agent->name }}</span>
                </button>
                @endforeach
            </div>

            <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:16px;">
                <button wire:click="$set('showGroupModal', false)" style="padding:8px 16px; font-size:12px; color:rgba(255,255,255,0.4); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:8px; cursor:pointer;">Cancelar</button>
                <button wire:click="createGroup" style="padding:8px 20px; font-size:12px; font-weight:700; color:#111; background:#b2ff00; border:none; border-radius:8px; cursor:pointer;">Criar Grupo</button>
            </div>
        </div>
    </div>
    @endif
</div>
