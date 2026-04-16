<div class="flex flex-col h-full"
     wire:poll.5s
     x-data="chatArea()"
     x-init="init()"
     @scroll-to-bottom.window="scrollToBottom(true)">

    @if($conversationId && $conversation)
    {{-- Chat Header --}}
    <div style="height:64px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; padding:0 20px; gap:12px; flex-shrink:0; background:rgba(11,15,28,0.6); backdrop-filter:blur(8px);">
        {{-- Botão voltar (mobile) --}}
        <button @click="$dispatch('conversation-deleted')"
                class="chat-back-btn"
                style="display:none; flex-shrink:0; width:32px; height:32px; border-radius:8px; background:rgba(255,255,255,0.05); border:none; color:rgba(255,255,255,0.5); cursor:pointer; align-items:center; justify-content:center;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        <style>@media (max-width: 768px) { .chat-back-btn { display: flex !important; } }</style>
        <div style="position:relative; flex-shrink:0;">
            <img src="{{ $conversation->contact->avatar }}" alt=""
                 style="width:38px; height:38px; border-radius:50%; object-fit:cover; border:2px solid rgba(178,255,0,0.3);">
            <div style="position:absolute; bottom:0; right:0; width:10px; height:10px; border-radius:50%; background:#22c55e; border:2px solid #0B0F1C;"></div>
        </div>
        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <p style="font-size:13px; font-weight:700; color:white; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; letter-spacing:-0.01em;">
                    {{ $conversation->is_group ? ($conversation->group_name ?: $conversation->contact->display_name) : $conversation->contact->display_name }}
                </p>
                @if($conversation->is_group)
                <span style="flex-shrink:0; display:inline-flex; align-items:center; gap:3px; font-size:9px; font-weight:700; padding:2px 7px; border-radius:20px; background:rgba(168,85,247,0.15); color:#c084fc; border:1px solid rgba(168,85,247,0.3);">
                    <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75M9 7a4 4 0 100 8 4 4 0 000-8z"/>
                    </svg>
                    GRUPO
                </span>
                @endif
                {{-- CRM badges --}}
                @foreach($crmCards as $crmCard)
                    @if($crmCard->pipeline && $crmCard->stage)
                    <span style="display:inline-flex; align-items:center; gap:3px; font-size:10px; font-weight:600; padding:2px 7px; border-radius:5px; flex-shrink:0; background:{{ $crmCard->stage->color }}18; color:{{ $crmCard->stage->color }}; border:1px solid {{ $crmCard->stage->color }}44;">
                        <svg width="8" height="8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                        </svg>
                        {{ $crmCard->pipeline->name }} · {{ $crmCard->stage->name }}
                    </span>
                    @endif
                @endforeach
            </div>
            <p style="font-size:10px; color:rgba(255,255,255,0.25); margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:flex; align-items:center; gap:4px;">
                <span>{{ $conversation->contact->phone }}</span>
                <span style="color:rgba(255,255,255,0.1);">·</span>
                <span style="color: {{ $conversation->department->color }}">{{ $conversation->department->name }}</span>
                <span style="color:rgba(255,255,255,0.1);">·</span>
                <span>#{{ $conversation->protocol }}</span>
            </p>
        </div>

        {{-- Search button --}}
        <button @click="searchOpen = !searchOpen; if(!searchOpen) clearSearch(); else $nextTick(() => $refs.searchInput?.focus())"
                title="Buscar na conversa"
                :style="searchOpen ? 'background:rgba(178,255,0,0.15); color:#b2ff00; border-color:rgba(178,255,0,0.3);' : ''"
                style="width:32px; height:32px; border-radius:8px; background:rgba(255,255,255,0.04); color:rgba(255,255,255,0.3); border:1px solid rgba(255,255,255,0.07); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.15s; flex-shrink:0;"
                onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.color='rgba(255,255,255,0.7)'"
                onmouseout="if(!this.getAttribute('aria-pressed')) { this.style.background='rgba(255,255,255,0.04)'; this.style.color='rgba(255,255,255,0.3)'; }">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </button>

        {{-- Actions --}}
        <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
            @if($conversation->isOpen() || $conversation->isPending())
                {{-- Resolve button --}}
                <button wire:click="resolveConversation"
                        wire:confirm="Encerrar este atendimento?"
                        style="display:flex; align-items:center; gap:5px; padding:6px 12px; border-radius:8px; font-size:11px; font-weight:600; border:none; cursor:pointer; transition:all 0.2s; background:rgba(34,197,94,0.1); color:#4ade80; border:1px solid rgba(34,197,94,0.2);"
                        onmouseover="this.style.background='rgba(34,197,94,0.18)'"
                        onmouseout="this.style.background='rgba(34,197,94,0.1)'">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Encerrar
                </button>

                {{-- CRM button --}}
                <button wire:click="openCrmPanel"
                        style="display:flex; align-items:center; gap:5px; padding:6px 12px; border-radius:8px; font-size:11px; font-weight:600; border:none; cursor:pointer; transition:all 0.2s;
                               {{ $showCrmPanel ? 'background:rgba(178,255,0,0.15); color:#b2ff00; border:1px solid rgba(178,255,0,0.3);' : 'background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.4); border:1px solid rgba(255,255,255,0.07);' }}"
                        onmouseover="if(!{{ $showCrmPanel ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='rgba(255,255,255,0.7)'; }"
                        onmouseout="if(!{{ $showCrmPanel ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.05)'; this.style.color='rgba(255,255,255,0.4)'; }">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                    CRM
                </button>

                {{-- Transfer button (qualquer agente pode transferir) --}}
                <button wire:click="$set('showTransfer', true)"
                        style="display:flex; align-items:center; gap:5px; padding:6px 12px; border-radius:8px; font-size:11px; font-weight:600; background:rgba(59,130,246,0.1); color:#60a5fa; border:1px solid rgba(59,130,246,0.2); cursor:pointer; transition:all 0.2s;"
                        onmouseover="this.style.background='rgba(59,130,246,0.18)'"
                        onmouseout="this.style.background='rgba(59,130,246,0.1)'">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Transferir
                </button>
            @else
                <button wire:click="reopenConversation"
                        style="display:flex; align-items:center; gap:5px; padding:6px 12px; border-radius:8px; font-size:11px; font-weight:600; background:rgba(178,255,0,0.1); color:#b2ff00; border:1px solid rgba(178,255,0,0.2); cursor:pointer; transition:all 0.2s;"
                        onmouseover="this.style.background='rgba(178,255,0,0.18)'"
                        onmouseout="this.style.background='rgba(178,255,0,0.1)'">
                    Reabrir
                </button>
            @endif

            {{-- Delete button (admin only) --}}
            @if(auth()->user()->isAdmin())
            <button wire:click="deleteConversation"
                    wire:confirm="Excluir esta conversa permanentemente do CRM? Esta ação não pode ser desfeita."
                    style="display:flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; background:rgba(239,68,68,0.08); color:rgba(239,68,68,0.6); border:1px solid rgba(239,68,68,0.15); cursor:pointer; transition:all 0.2s;"
                    onmouseover="this.style.background='rgba(239,68,68,0.18)'; this.style.color='#f87171'"
                    onmouseout="this.style.background='rgba(239,68,68,0.08)'; this.style.color='rgba(239,68,68,0.6)'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
            @endif
        </div>
    </div>

    {{-- Search bar --}}
    <div x-show="searchOpen" x-cloak
         style="flex-shrink:0; padding:8px 16px; border-bottom:1px solid rgba(255,255,255,0.05); background:rgba(8,12,22,0.8); backdrop-filter:blur(8px); display:flex; align-items:center; gap:8px;">
        <div style="flex:1; display:flex; align-items:center; gap:8px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:6px 12px;"
             x-effect="if(searchOpen) $el.style.borderColor='rgba(178,255,0,0.3)'">
            <svg width="13" height="13" fill="none" stroke="rgba(255,255,255,0.3)" viewBox="0 0 24 24" style="flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input x-ref="searchInput"
                   x-model="searchQuery"
                   @input.debounce.300ms="doSearch()"
                   @keydown.escape="searchOpen=false; clearSearch()"
                   @keydown.enter="nextMatch()"
                   type="text"
                   placeholder="Buscar na conversa..."
                   style="flex:1; background:transparent; border:none; outline:none; font-size:13px; color:white; font-family:inherit;">
            <span x-show="searchMatches.length > 0"
                  style="font-size:11px; color:rgba(255,255,255,0.3); white-space:nowrap; flex-shrink:0;"
                  x-text="(searchIndex+1) + ' de ' + searchMatches.length"></span>
            <span x-show="searchQuery && searchMatches.length === 0"
                  style="font-size:11px; color:rgba(239,68,68,0.6); flex-shrink:0;">Não encontrado</span>
        </div>
        <button @click="prevMatch()" x-show="searchMatches.length > 0"
                style="width:28px; height:28px; border-radius:7px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); color:rgba(255,255,255,0.4); cursor:pointer; display:flex; align-items:center; justify-content:center;"
                onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.color='white'"
                onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.color='rgba(255,255,255,0.4)'">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
            </svg>
        </button>
        <button @click="nextMatch()" x-show="searchMatches.length > 0"
                style="width:28px; height:28px; border-radius:7px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); color:rgba(255,255,255,0.4); cursor:pointer; display:flex; align-items:center; justify-content:center;"
                onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.color='white'"
                onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.color='rgba(255,255,255,0.4)'">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <button @click="searchOpen=false; clearSearch()"
                style="width:28px; height:28px; border-radius:7px; background:transparent; border:none; color:rgba(255,255,255,0.25); cursor:pointer; display:flex; align-items:center; justify-content:center;"
                onmouseover="this.style.color='rgba(239,68,68,0.7)'" onmouseout="this.style.color='rgba(255,255,255,0.25)'">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Messages area --}}
    <div class="flex-1 overflow-y-auto" x-ref="msgContainer" id="messages-container"
         style="padding:20px 24px; display:flex; flex-direction:column; gap:6px;
                background: radial-gradient(ellipse at 20% 0%, rgba(178,255,0,0.02) 0%, transparent 60%),
                            radial-gradient(ellipse at 80% 100%, rgba(178,255,0,0.015) 0%, transparent 60%);
                background-attachment:local;">
        @php $lastDate = null; @endphp
        @foreach($messages as $msg)
            @php
                $msgDate = $msg->created_at->format('Y-m-d');
                $showDateSep = $msgDate !== $lastDate;
                $lastDate = $msgDate;
            @endphp
            @if($showDateSep)
                <div style="display:flex; justify-content:center; margin:12px 0 8px;">
                    <span style="background:rgba(88,101,124,0.7); color:rgba(255,255,255,0.85); font-size:11px; font-weight:600; padding:4px 14px; border-radius:8px; box-shadow:0 1px 4px rgba(0,0,0,0.2);">
                        {{ $msg->created_at->format('d/m/Y') }}
                    </span>
                </div>
            @endif
            @if($msg->isSystem())
                {{-- System message --}}
                <div style="display:flex; justify-content:center; margin:4px 0;">
                    <span style="background:rgba(255,255,255,0.04); color:rgba(255,255,255,0.25); font-size:10px; padding:4px 12px; border-radius:20px; border:1px solid rgba(255,255,255,0.06);">
                        {{ $msg->content }}
                    </span>
                </div>
            @elseif($msg->isFromContact())
                {{-- Contact message (left) --}}
                <div style="display:flex; align-items:flex-end; gap:8px; max-width:75%; position:relative;" x-data="{ showMenu: false }" @mouseenter="showMenu = true" @mouseleave="showMenu = false">
                    <img src="{{ $conversation->contact->avatar }}" alt=""
                         style="width:26px; height:26px; border-radius:50%; object-fit:cover; flex-shrink:0; margin-bottom:2px; border:1px solid rgba(255,255,255,0.08);">
                    <div>
                        @if($msg->type === 'text')
                            <div data-msg-text style="background:rgba(31,41,55,0.8); backdrop-filter:blur(4px); color:rgba(255,255,255,0.88); border-radius:18px 18px 18px 4px; padding:10px 14px; font-size:13px; line-height:1.5; border:1px solid rgba(255,255,255,0.06); max-width:min(400px, 85vw); word-break:break-word;">
                                @if($msg->sender_name)
                                    <p style="font-size:11px; font-weight:700; color:#b2ff00; margin-bottom:3px;">{{ $msg->sender_name }}</p>
                                @endif
                                {{ $msg->content }}
                            </div>
                        @elseif($msg->type === 'image')
                            <div style="background:rgba(31,41,55,0.8); border-radius:18px 18px 18px 4px; overflow:hidden; border:1px solid rgba(255,255,255,0.06);">
                                @if($msg->sender_name)
                                    <p style="font-size:11px; font-weight:700; color:#b2ff00; padding:8px 10px 4px;">{{ $msg->sender_name }}</p>
                                @endif
                                <img src="{{ $msg->media_thumb_url ?? $msg->media_url }}" alt="Imagem"
                                     loading="lazy"
                                     onerror="if(!this.dataset.fb){this.dataset.fb=1;this.src='{{ $msg->media_url }}'}"
                                     @click="$dispatch('open-lightbox', { src: '{{ $msg->media_url }}' })"
                                     style="max-width:min(260px, 70vw); display:block; cursor:zoom-in; transition:opacity 0.2s;"
                                     onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                                @if($msg->content)
                                    <p style="padding:6px 10px 8px; font-size:11px; color:rgba(255,255,255,0.6);">{{ $msg->content }}</p>
                                @endif
                            </div>
                        @elseif($msg->type === 'audio')
                            @php
                                $audioSeed = strlen($msg->media_url ?? '');
                                $audioBars = array_map(fn($i) => max(15, min(100, abs(sin(($i+1)*$audioSeed*0.07+$i*1.9))*85+15)), range(0,51));
                            @endphp
                            <div style="background:rgba(31,41,55,0.8); border-radius:18px 18px 18px 4px; padding:12px 14px 10px; width:min(280px, 80vw); border:1px solid rgba(255,255,255,0.06);"
                                 x-data="{
                                    playing: false, progress: 0, currentTime: 0, duration: {{ $msg->media_duration ?? 0 }},
                                    speed: 1, speeds: [1, 1.5, 2],
                                    bars: {{ json_encode($audioBars) }},
                                    audioEl: null,
                                    _interval: null,
                                    init() {
                                        this.audioEl = this.$el.querySelector('audio');
                                    },
                                    cycleSpeed() {
                                        const i = this.speeds.indexOf(this.speed);
                                        this.speed = this.speeds[(i+1) % this.speeds.length];
                                        this.audioEl.playbackRate = this.speed;
                                    },
                                    toggle() {
                                        const a = this.audioEl;
                                        if (this.playing) {
                                            a.pause();
                                            this.playing = false;
                                            if (this._interval) { clearInterval(this._interval); this._interval = null; }
                                        } else {
                                            document.querySelectorAll('audio').forEach(x => { if(x!==a) x.pause(); });
                                            a.play().then(() => {
                                                this.playing = true;
                                                if (a.duration && isFinite(a.duration)) this.duration = a.duration;
                                                this._interval = setInterval(() => {
                                                    if (a.ended) {
                                                        this.playing = false; this.progress = 0; this.currentTime = 0;
                                                        a.currentTime = 0;
                                                        clearInterval(this._interval); this._interval = null;
                                                        return;
                                                    }
                                                    this.currentTime = a.currentTime;
                                                    if (a.duration && isFinite(a.duration)) this.duration = a.duration;
                                                    if (this.duration > 0) this.progress = a.currentTime / this.duration;
                                                }, 100);
                                            }).catch(e => console.error('play error', e));
                                        }
                                    },
                                    seek(e) {
                                        if (!this.duration) return;
                                        const r = Math.max(0, Math.min(1, (e.clientX - e.currentTarget.getBoundingClientRect().left) / e.currentTarget.offsetWidth));
                                        this.audioEl.currentTime = r * this.duration; this.progress = r;
                                    },
                                    fmt(s) { if(!s||isNaN(s)) return '0:00'; s=Math.floor(s); return Math.floor(s/60)+':'+(''+(s%60)).padStart(2,'0'); }
                                 }">

                                <div style="display:flex; align-items:center; gap:10px;">
                                    {{-- Play/Pause --}}
                                    <button @click.stop="toggle()"
                                            style="flex-shrink:0; width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:50%; background:rgba(255,255,255,0.08); border:none; cursor:pointer; transition:all 0.15s; color:rgba(255,255,255,0.8);"
                                            onmouseover="this.style.background='rgba(255,255,255,0.14)'"
                                            onmouseout="this.style.background='rgba(255,255,255,0.08)'">
                                        <svg x-show="!playing" width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        <svg x-show="playing" width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                                    </button>

                                    {{-- Waveform --}}
                                    <div style="flex:1; display:flex; flex-direction:column; gap:6px;">
                                        <div style="display:flex; align-items:center; gap:1px; height:32px; cursor:pointer; position:relative;" @click.stop="seek($event)">
                                            <div style="position:absolute; width:10px; height:10px; border-radius:50%; background:#b2ff00; z-index:10; top:50%; transform:translateY(-50%); pointer-events:none;"
                                                 :style="'left: calc('+( progress*100 )+'% - 5px)'"></div>
                                            <template x-for="(bar, i) in bars" :key="i">
                                                <div style="border-radius:2px; width:3px; flex-shrink:0;"
                                                     :class="(i/bars.length) <= progress ? 'bg-accent' : ''"
                                                     :style="'height:'+bar+'%; background:'+ ((i/bars.length) <= progress ? '#b2ff00' : 'rgba(255,255,255,0.15)')"></div>
                                            </template>
                                        </div>
                                        <div style="display:flex; justify-content:space-between; align-items:center;">
                                            <span style="font-size:10px; color:rgba(255,255,255,0.4);" x-text="playing ? fmt(duration - currentTime) : fmt(duration)"></span>
                                            <button @click.stop="cycleSpeed()"
                                                    style="font-size:10px; font-weight:700; color:#b2ff00; border:none; background:transparent; cursor:pointer; padding:0 2px;"
                                                    x-text="speed + 'x'"></button>
                                            <span style="font-size:10px; color:rgba(255,255,255,0.25);" x-text="playing ? fmt(currentTime) : ''"></span>
                                        </div>
                                    </div>

                                    {{-- Mic icon --}}
                                    <div style="flex-shrink:0; width:32px; height:32px; border-radius:50%; background:rgba(178,255,0,0.2); border:1px solid rgba(178,255,0,0.3); display:flex; align-items:center; justify-content:center;">
                                        <svg width="14" height="14" fill="#b2ff00" viewBox="0 0 24 24">
                                            <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.91-3c-.49 0-.9.36-.98.85C16.52 14.2 14.47 16 12 16s-4.52-1.8-4.93-4.15c-.08-.49-.49-.85-.98-.85-.61 0-1.09.54-1 1.14.49 3 2.89 5.35 5.91 5.78V20c0 .55.45 1 1 1s1-.45 1-1v-2.08c3.02-.43 5.42-2.78 5.91-5.78.1-.6-.39-1.14-1-1.14z"/>
                                        </svg>
                                    </div>
                                </div>
                                <audio src="{{ $msg->media_url }}" preload="auto" style="display:none"></audio>
                            </div>
                        @elseif($msg->type === 'video')
                            <div style="background:rgba(31,41,55,0.8); border-radius:18px 18px 18px 4px; overflow:hidden; border:1px solid rgba(255,255,255,0.06); width:min(320px, 80vw);">
                                @if($msg->sender_name)
                                    <p style="font-size:11px; font-weight:700; color:#b2ff00; padding:8px 10px 4px;">{{ $msg->sender_name }}</p>
                                @endif
                                <video src="{{ $msg->media_url }}" controls preload="metadata" playsinline
                                       @if($msg->media_thumb_url) poster="{{ $msg->media_thumb_url }}" @endif
                                       style="width:100%; min-height:180px; display:block; background:#000; object-fit:contain;">
                                </video>
                                @if($msg->content)
                                    <p style="padding:6px 10px 8px; font-size:11px; color:rgba(255,255,255,0.6);">{{ $msg->content }}</p>
                                @endif
                            </div>
                        @elseif($msg->type === 'document')
                            @php
                                $docFile  = $msg->media_filename ?? 'Documento';
                                $docExt   = strtolower(pathinfo($docFile, PATHINFO_EXTENSION));
                                $docCanPv = $msg->media_url && ($docExt === 'pdf' || in_array($docExt, ['doc','docx','xls','xlsx','ppt','pptx']));
                                $docPvUrl = in_array($docExt, ['doc','docx','xls','xlsx','ppt','pptx'])
                                    ? 'https://docs.google.com/viewer?url='.urlencode($msg->media_url ?? '').'&embedded=true'
                                    : ($msg->media_url ?? '');
                                $docColor = match(true) {
                                    $docExt === 'pdf'                          => '#ef4444',
                                    in_array($docExt, ['doc','docx'])          => '#3b82f6',
                                    in_array($docExt, ['xls','xlsx'])          => '#22c55e',
                                    in_array($docExt, ['ppt','pptx'])          => '#f97316',
                                    default                                    => '#b2ff00',
                                };
                            @endphp
                            <div x-data="{ pvOpen: false }" style="position:relative;">
                                <div style="background:rgba(31,41,55,0.8); border-radius:18px 18px 18px 4px; padding:10px 14px; display:flex; align-items:center; gap:8px; border:1px solid rgba(255,255,255,0.06);">
                                    <div style="width:34px; height:34px; border-radius:8px; background:{{ $docColor }}1a; border:1px solid {{ $docColor }}33; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <svg width="16" height="16" fill="none" stroke="{{ $docColor }}" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div style="flex:1; min-width:0;">
                                        <p style="font-size:12px; color:rgba(255,255,255,0.7); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $docFile }}</p>
                                        @if($docExt)<p style="font-size:9px; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.06em; margin-top:1px;">{{ $docExt }}</p>@endif
                                    </div>
                                    <div style="display:flex; gap:6px; flex-shrink:0;">
                                        @if($docCanPv)
                                        <button @click.stop="pvOpen = true"
                                                style="font-size:11px; color:rgba(255,255,255,0.55); background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:6px; padding:3px 9px; cursor:pointer;"
                                                onmouseover="this.style.color='white'; this.style.background='rgba(255,255,255,0.1)'"
                                                onmouseout="this.style.color='rgba(255,255,255,0.55)'; this.style.background='rgba(255,255,255,0.05)'">Ver</button>
                                        @endif
                                        <a href="{{ $msg->media_url }}" target="_blank" download
                                           style="font-size:11px; color:#b2ff00; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:6px; padding:3px 9px; text-decoration:none; font-weight:600;"
                                           onmouseover="this.style.background='rgba(178,255,0,0.16)'" onmouseout="this.style.background='rgba(178,255,0,0.08)'">↓</a>
                                    </div>
                                </div>
                                @if($docCanPv)
                                <template x-teleport="body">
                                <div x-show="pvOpen" x-cloak @click.self="pvOpen = false"
                                     style="position:fixed; top:0; left:0; right:0; bottom:0; z-index:9999; background:rgba(0,0,0,0.88); display:flex; align-items:center; justify-content:center; padding:20px;">
                                    <div style="width:100%; max-width:min(900px, 95vw); height:86vh; background:#0f172a; border-radius:16px; overflow:hidden; display:flex; flex-direction:column; border:1px solid rgba(255,255,255,0.08); box-shadow:0 24px 80px rgba(0,0,0,0.6);">
                                        <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid rgba(255,255,255,0.06); flex-shrink:0;">
                                            <p style="font-size:13px; color:rgba(255,255,255,0.6); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:70%;">{{ $docFile }}</p>
                                            <div style="display:flex; gap:8px;">
                                                <a href="{{ $msg->media_url }}" target="_blank" download
                                                   style="font-size:12px; color:#b2ff00; background:rgba(178,255,0,0.1); border:1px solid rgba(178,255,0,0.25); border-radius:8px; padding:5px 14px; text-decoration:none; font-weight:600;">Download</a>
                                                <button @click="pvOpen = false"
                                                        style="width:30px; height:30px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.5); cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center;"
                                                        onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.color='white'"
                                                        onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.color='rgba(255,255,255,0.5)'">✕</button>
                                            </div>
                                        </div>
                                        <iframe x-bind:src="pvOpen ? '{{ $docPvUrl }}' : ''"
                                                style="flex:1; width:100%; border:none; background:white;" allow="fullscreen"></iframe>
                                    </div>
                                </div>
                                </template>
                                @endif
                            </div>
                        @endif
                        {{-- Emoji reaction trigger (ao lado da mensagem) --}}
                        <div x-show="showMenu" x-transition.opacity
                             style="position:absolute; right:-36px; top:50%; transform:translateY(-50%); z-index:10;">
                            <button @click.stop="showMenu = 'open'"
                                    style="width:28px; height:28px; border-radius:50%; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.1); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.15s; font-size:14px; line-height:1;"
                                    onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.borderColor='rgba(255,255,255,0.2)'"
                                    onmouseout="this.style.background='rgba(255,255,255,0.08)'; this.style.borderColor='rgba(255,255,255,0.1)'">😊</button>
                        </div>
                        {{-- Reaction popup --}}
                        <div x-show="showMenu === 'open'" x-transition @click.outside="showMenu = true"
                             style="position:absolute; right:-10px; top:-40px; z-index:20; background:rgba(17,24,39,0.97); border:1px solid rgba(255,255,255,0.12); border-radius:24px; padding:5px 8px; display:flex; gap:2px; align-items:center; box-shadow:0 8px 24px rgba(0,0,0,0.5);">
                            @foreach(['👍','❤️','😂','😮','😢','🙏'] as $e)
                            <button wire:click="reactToMessage({{ $msg->id }}, '{{ $e }}')" @click="showMenu = false"
                                    style="font-size:20px; padding:4px 5px; border:none; background:transparent; border-radius:8px; cursor:pointer; transition:all 0.15s; line-height:1;"
                                    onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.transform='scale(1.2)'"
                                    onmouseout="this.style.background='transparent'; this.style.transform='scale(1)'">{{ $e }}</button>
                            @endforeach
                            @if($conversation->is_group && $msg->sender_phone)
                            <span style="width:1px; height:20px; background:rgba(255,255,255,0.1); margin:0 2px;"></span>
                            <button wire:click="openPrivateChat({{ $msg->id }})" @click="showMenu = false"
                                    title="Responder no particular"
                                    style="display:flex; align-items:center; gap:3px; font-size:10px; font-weight:600; padding:5px 8px; border:none; background:rgba(59,130,246,0.15); color:#60a5fa; border-radius:8px; cursor:pointer; transition:all 0.15s; white-space:nowrap;"
                                    onmouseover="this.style.background='rgba(59,130,246,0.3)'"
                                    onmouseout="this.style.background='rgba(59,130,246,0.15)'">
                                <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                PV
                            </button>
                            @endif
                        </div>

                        {{-- Reactions --}}
                        @if(!empty($msg->reactions))
                        @php
                            $rxGrouped = collect($msg->reactions)->groupBy('emoji');
                            $myEmojis = collect($msg->reactions)->where('phone', $myReactionPhone)->pluck('emoji')->toArray();
                        @endphp
                        <div style="display:flex; gap:4px; flex-wrap:wrap; margin-top:4px; margin-left:2px;">
                            @foreach($rxGrouped as $rxEmoji => $rxList)
                            @php $isMine = in_array($rxEmoji, $myEmojis); @endphp
                            <button wire:click="reactToMessage({{ $msg->id }}, '{{ $rxEmoji }}')"
                                    style="background:{{ $isMine ? 'rgba(178,255,0,0.15)' : 'rgba(255,255,255,0.07)' }}; border:1px solid {{ $isMine ? 'rgba(178,255,0,0.35)' : 'rgba(255,255,255,0.12)' }}; border-radius:20px; padding:2px 7px; font-size:13px; line-height:1.5; display:inline-flex; align-items:center; gap:3px; cursor:pointer; transition:all 0.15s;"
                                    onmouseover="this.style.background='{{ $isMine ? 'rgba(178,255,0,0.25)' : 'rgba(255,255,255,0.12)' }}'"
                                    onmouseout="this.style.background='{{ $isMine ? 'rgba(178,255,0,0.15)' : 'rgba(255,255,255,0.07)' }}'">{{ $rxEmoji }}@if($rxList->count() > 1)<span style="font-size:10px; color:{{ $isMine ? '#b2ff00' : 'rgba(255,255,255,0.4)' }};">{{ $rxList->count() }}</span>@endif</button>
                            @endforeach
                        </div>
                        @endif
                        <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:3px; margin-left:4px;">
                            {{ $msg->created_at->format('H:i') }}
                        </p>
                    </div>
                </div>
            @else
                {{-- Agent message (right) --}}
                <div style="display:flex; align-items:flex-end; gap:8px; max-width:75%; margin-left:auto; flex-direction:row-reverse; position:relative;"
                     x-data="{ showMenu: false, editing: false, editText: '' }"
                     @mouseenter="showMenu = true" @mouseleave="showMenu = false">
                    <img src="{{ $msg->sender?->avatar_url ?? auth()->user()->avatar_url }}" alt=""
                         title="{{ $msg->sender?->name ?? 'WhatsApp' }}"
                         style="width:26px; height:26px; border-radius:50%; object-fit:cover; flex-shrink:0; margin-bottom:2px; border:1px solid rgba(178,255,0,0.3);">
                    <div>
                        @if($msg->type === 'text')
                            <div data-msg-text style="background:#49650a; color:white; border-radius:18px 18px 4px 18px; padding:10px 14px; font-size:13px; line-height:1.5; max-width:min(400px, 85vw); word-break:break-word; box-shadow:0 2px 12px rgba(73,101,10,0.3);">
                                @if($msg->sender?->name)
                                    <p style="font-size:11px; font-weight:700; color:rgba(255,255,255,0.95); margin-bottom:3px;">{{ $msg->sender->name }}</p>
                                @endif
                                {{ $msg->content }}
                            </div>
                        @elseif($msg->type === 'image')
                            <div style="background:rgba(178,255,0,0.12); border-radius:18px 18px 4px 18px; overflow:hidden; border:1px solid rgba(178,255,0,0.25);">
                                <img src="{{ $msg->media_thumb_url ?? $msg->media_url }}" alt="Imagem"
                                     loading="lazy"
                                     onerror="if(!this.dataset.fb){this.dataset.fb=1;this.src='{{ $msg->media_url }}'}"
                                     @click="$dispatch('open-lightbox', { src: '{{ $msg->media_url }}' })"
                                     style="max-width:min(260px, 70vw); display:block; cursor:zoom-in; transition:opacity 0.2s;"
                                     onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                                @if($msg->content)
                                    <p style="padding:6px 10px 8px; font-size:11px; color:rgba(255,255,255,0.7);">{{ $msg->content }}</p>
                                @endif
                            </div>
                        @elseif($msg->type === 'audio')
                            @php
                                $audioSeedA = strlen($msg->media_url ?? '');
                                $audioBarsA = array_map(fn($i) => max(15, min(100, abs(sin(($i+1)*$audioSeedA*0.07+$i*1.9))*85+15)), range(0,51));
                            @endphp
                            <div style="background:rgba(178,255,0,0.12); border-radius:18px 18px 4px 18px; padding:12px 14px 10px; width:min(280px, 80vw); border:1px solid rgba(178,255,0,0.25);"
                                 x-data="{
                                    _interval: null,
                                    playing: false, progress: 0, currentTime: 0, duration: {{ $msg->media_duration ?? 0 }},
                                    speed: 1, speeds: [1, 1.5, 2],
                                    bars: {{ json_encode($audioBarsA) }},
                                    audioEl: null,
                                    init() {
                                        this.audioEl = this.$el.querySelector('audio');
                                    },
                                    cycleSpeed() {
                                        const i = this.speeds.indexOf(this.speed);
                                        this.speed = this.speeds[(i+1) % this.speeds.length];
                                        this.audioEl.playbackRate = this.speed;
                                    },
                                    toggle() {
                                        const a = this.audioEl;
                                        if (this.playing) {
                                            a.pause();
                                            this.playing = false;
                                            if (this._interval) { clearInterval(this._interval); this._interval = null; }
                                        } else {
                                            document.querySelectorAll('audio').forEach(x => { if(x!==a) x.pause(); });
                                            a.play().then(() => {
                                                this.playing = true;
                                                if (a.duration && isFinite(a.duration)) this.duration = a.duration;
                                                this._interval = setInterval(() => {
                                                    if (a.ended) {
                                                        this.playing = false; this.progress = 0; this.currentTime = 0;
                                                        a.currentTime = 0;
                                                        clearInterval(this._interval); this._interval = null;
                                                        return;
                                                    }
                                                    this.currentTime = a.currentTime;
                                                    if (a.duration && isFinite(a.duration)) this.duration = a.duration;
                                                    if (this.duration > 0) this.progress = a.currentTime / this.duration;
                                                }, 100);
                                            }).catch(e => console.error('play error', e));
                                        }
                                    },
                                    seek(e) {
                                        if (!this.duration) return;
                                        const r = Math.max(0, Math.min(1, (e.clientX - e.currentTarget.getBoundingClientRect().left) / e.currentTarget.offsetWidth));
                                        this.audioEl.currentTime = r * this.duration; this.progress = r;
                                    },
                                    fmt(s) { if(!s||isNaN(s)) return '0:00'; s=Math.floor(s); return Math.floor(s/60)+':'+(''+(s%60)).padStart(2,'0'); }
                                 }">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <button @click.stop="toggle()"
                                            style="flex-shrink:0; width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:50%; background:rgba(178,255,0,0.25); border:none; cursor:pointer; transition:all 0.15s; color:white;"
                                            onmouseover="this.style.background='rgba(178,255,0,0.4)'"
                                            onmouseout="this.style.background='rgba(178,255,0,0.25)'">
                                        <svg x-show="!playing" width="14" height="14" fill="white" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        <svg x-show="playing" width="14" height="14" fill="white" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                                    </button>
                                    <div style="flex:1; display:flex; flex-direction:column; gap:6px;">
                                        <div style="display:flex; align-items:center; gap:1px; height:32px; cursor:pointer; position:relative;" @click.stop="seek($event)">
                                            <div style="position:absolute; width:10px; height:10px; border-radius:50%; background:white; z-index:10; top:50%; transform:translateY(-50%); pointer-events:none;"
                                                 :style="'left: calc('+( progress*100 )+'% - 5px)'"></div>
                                            <template x-for="(bar, i) in bars" :key="i">
                                                <div style="border-radius:2px; width:3px; flex-shrink:0;"
                                                     :style="'height:'+bar+'%; background:'+ ((i/bars.length) <= progress ? 'white' : 'rgba(255,255,255,0.25)')"></div>
                                            </template>
                                        </div>
                                        <div style="display:flex; justify-content:space-between; align-items:center;">
                                            <span style="font-size:10px; color:rgba(255,255,255,0.6);" x-text="playing ? fmt(duration - currentTime) : fmt(duration)"></span>
                                            <button @click.stop="cycleSpeed()"
                                                    style="font-size:10px; font-weight:700; color:white; border:none; background:transparent; cursor:pointer; padding:0 2px;"
                                                    x-text="speed + 'x'"></button>
                                            <span style="font-size:10px; color:rgba(255,255,255,0.4);" x-text="playing ? fmt(currentTime) : ''"></span>
                                        </div>
                                    </div>
                                    <div style="flex-shrink:0; width:32px; height:32px; border-radius:50%; background:rgba(178,255,0,0.3); display:flex; align-items:center; justify-content:center;">
                                        <svg width="14" height="14" fill="white" viewBox="0 0 24 24">
                                            <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.91-3c-.49 0-.9.36-.98.85C16.52 14.2 14.47 16 12 16s-4.52-1.8-4.93-4.15c-.08-.49-.49-.85-.98-.85-.61 0-1.09.54-1 1.14.49 3 2.89 5.35 5.91 5.78V20c0 .55.45 1 1 1s1-.45 1-1v-2.08c3.02-.43 5.42-2.78 5.91-5.78.1-.6-.39-1.14-1-1.14z"/>
                                        </svg>
                                    </div>
                                </div>
                                <audio src="{{ $msg->media_url }}" preload="auto" style="display:none"></audio>
                            </div>
                        @elseif($msg->type === 'video')
                            <div style="background:rgba(73,101,10,0.3); border-radius:18px 18px 4px 18px; overflow:hidden; box-shadow:0 2px 12px rgba(73,101,10,0.3); width:min(320px, 80vw);">
                                <video src="{{ $msg->media_url }}" controls preload="metadata" playsinline
                                       @if($msg->media_thumb_url) poster="{{ $msg->media_thumb_url }}" @endif
                                       style="width:100%; min-height:180px; display:block; background:#000; object-fit:contain;">
                                </video>
                                @if($msg->content)
                                    <p style="padding:6px 10px 8px; font-size:11px; color:rgba(255,255,255,0.6);">{{ $msg->content }}</p>
                                @endif
                            </div>
                        @elseif($msg->type === 'document')
                            @php
                                $aDocFile  = $msg->media_filename ?? $msg->content ?? 'Arquivo';
                                $aDocExt   = strtolower(pathinfo($aDocFile, PATHINFO_EXTENSION));
                                $aDocCanPv = $msg->media_url && ($aDocExt === 'pdf' || in_array($aDocExt, ['doc','docx','xls','xlsx','ppt','pptx']));
                                $aDocPvUrl = in_array($aDocExt, ['doc','docx','xls','xlsx','ppt','pptx'])
                                    ? 'https://docs.google.com/viewer?url='.urlencode($msg->media_url ?? '').'&embedded=true'
                                    : ($msg->media_url ?? '');
                                $aDocColor = match(true) {
                                    $aDocExt === 'pdf'                           => '#ef4444',
                                    in_array($aDocExt, ['doc','docx'])           => '#3b82f6',
                                    in_array($aDocExt, ['xls','xlsx'])           => '#22c55e',
                                    in_array($aDocExt, ['ppt','pptx'])           => '#f97316',
                                    default                                      => 'white',
                                };
                            @endphp
                            <div x-data="{ pvOpen: false }" style="position:relative;">
                                <div style="background:rgba(178,255,0,0.12); border-radius:18px 18px 4px 18px; padding:10px 14px; display:flex; align-items:center; gap:8px; border:1px solid rgba(178,255,0,0.25);">
                                    <div style="width:34px; height:34px; border-radius:8px; background:rgba(178,255,0,0.2); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <svg width="16" height="16" fill="none" stroke="{{ $aDocColor }}" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div style="flex:1; min-width:0;">
                                        <p style="font-size:12px; color:rgba(255,255,255,0.85); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $aDocFile }}</p>
                                        @if($aDocExt)<p style="font-size:9px; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:0.06em; margin-top:1px;">{{ $aDocExt }}</p>@endif
                                    </div>
                                    @if($msg->media_url)
                                    <div style="display:flex; gap:6px; flex-shrink:0;">
                                        @if($aDocCanPv)
                                        <button @click.stop="pvOpen = true"
                                                style="font-size:11px; color:rgba(255,255,255,0.6); background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); border-radius:6px; padding:3px 9px; cursor:pointer;"
                                                onmouseover="this.style.color='white'; this.style.background='rgba(255,255,255,0.15)'"
                                                onmouseout="this.style.color='rgba(255,255,255,0.6)'; this.style.background='rgba(255,255,255,0.08)'">Ver</button>
                                        @endif
                                        <a href="{{ $msg->media_url }}" target="_blank" download
                                           style="font-size:11px; color:white; background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.2); border-radius:6px; padding:3px 9px; text-decoration:none; font-weight:600;"
                                           onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.12)'">↓</a>
                                    </div>
                                    @endif
                                </div>
                                @if($aDocCanPv)
                                <div x-show="pvOpen" x-cloak @click.self="pvOpen = false"
                                     style="position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.88); display:flex; align-items:center; justify-content:center; padding:20px;">
                                    <div style="width:100%; max-width:min(900px, 95vw); height:86vh; background:#0f172a; border-radius:16px; overflow:hidden; display:flex; flex-direction:column; border:1px solid rgba(255,255,255,0.08); box-shadow:0 24px 80px rgba(0,0,0,0.6);">
                                        <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid rgba(255,255,255,0.06); flex-shrink:0;">
                                            <p style="font-size:13px; color:rgba(255,255,255,0.6); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:70%;">{{ $aDocFile }}</p>
                                            <div style="display:flex; gap:8px;">
                                                <a href="{{ $msg->media_url }}" target="_blank" download
                                                   style="font-size:12px; color:#b2ff00; background:rgba(178,255,0,0.1); border:1px solid rgba(178,255,0,0.25); border-radius:8px; padding:5px 14px; text-decoration:none; font-weight:600;">Download</a>
                                                <button @click="pvOpen = false"
                                                        style="width:30px; height:30px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.5); cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center;"
                                                        onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.color='white'"
                                                        onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.color='rgba(255,255,255,0.5)'">✕</button>
                                            </div>
                                        </div>
                                        <iframe x-bind:src="pvOpen ? '{{ $aDocPvUrl }}' : ''"
                                                style="flex:1; width:100%; border:none; background:white;" allow="fullscreen"></iframe>
                                    </div>
                                </div>
                                @endif
                            </div>
                        @endif
                        {{-- Emoji reaction trigger (ao lado esquerdo da mensagem do agente) --}}
                        <div x-show="showMenu && !editing" x-transition.opacity
                             style="position:absolute; left:-36px; top:50%; transform:translateY(-50%); z-index:10;">
                            <button @click.stop="showMenu = 'open'"
                                    style="width:28px; height:28px; border-radius:50%; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.1); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.15s; font-size:14px; line-height:1;"
                                    onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.borderColor='rgba(255,255,255,0.2)'"
                                    onmouseout="this.style.background='rgba(255,255,255,0.08)'; this.style.borderColor='rgba(255,255,255,0.1)'">😊</button>
                        </div>
                        {{-- Reaction + actions popup --}}
                        <div x-show="showMenu === 'open' && !editing" x-transition @click.outside="showMenu = true"
                             style="position:absolute; left:-10px; top:-40px; z-index:20; background:rgba(17,24,39,0.97); border:1px solid rgba(255,255,255,0.12); border-radius:24px; padding:5px 8px; display:flex; gap:2px; align-items:center; box-shadow:0 8px 24px rgba(0,0,0,0.5);">
                            @foreach(['👍','❤️','😂','😮','😢','🙏'] as $e)
                            <button wire:click="reactToMessage({{ $msg->id }}, '{{ $e }}')" @click="showMenu = false"
                                    style="font-size:20px; padding:4px 5px; border:none; background:transparent; border-radius:8px; cursor:pointer; transition:all 0.15s; line-height:1;"
                                    onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.transform='scale(1.2)'"
                                    onmouseout="this.style.background='transparent'; this.style.transform='scale(1)'">{{ $e }}</button>
                            @endforeach
                            <span style="width:1px; height:20px; background:rgba(255,255,255,0.1); margin:0 2px;"></span>
                            @if($msg->type === 'text')
                            <button @click="showMenu = false; editing = true; editText = @js($msg->content)"
                                    style="font-size:10px; font-weight:600; padding:5px 8px; border:none; background:rgba(59,130,246,0.1); color:#60a5fa; border-radius:8px; cursor:pointer; transition:background 0.1s; white-space:nowrap;"
                                    onmouseover="this.style.background='rgba(59,130,246,0.2)'"
                                    onmouseout="this.style.background='rgba(59,130,246,0.1)'">Editar</button>
                            @endif
                            <button wire:click="deleteMessage({{ $msg->id }})"
                                    wire:confirm="Excluir esta mensagem?"
                                    style="font-size:10px; font-weight:600; padding:5px 8px; border:none; background:rgba(239,68,68,0.1); color:#f87171; border-radius:8px; cursor:pointer; transition:background 0.1s; white-space:nowrap;"
                                    onmouseover="this.style.background='rgba(239,68,68,0.2)'"
                                    onmouseout="this.style.background='rgba(239,68,68,0.1)'">Excluir</button>
                        </div>

                        {{-- Inline edit form --}}
                        <div x-show="editing" x-transition style="margin-top:6px;">
                            <textarea x-model="editText" rows="2"
                                      style="width:100%; background:rgba(255,255,255,0.06); border:1px solid rgba(59,130,246,0.4); border-radius:8px; padding:8px 10px; font-size:12px; color:white; outline:none; font-family:inherit; resize:vertical; box-sizing:border-box;"></textarea>
                            <div style="display:flex; gap:6px; justify-content:flex-end; margin-top:4px;">
                                <button @click="$wire.editMessage({{ $msg->id }}, editText); editing = false"
                                        style="font-size:10px; font-weight:600; padding:4px 12px; background:#3b82f6; color:white; border:none; border-radius:6px; cursor:pointer;">Salvar</button>
                                <button @click="editing = false"
                                        style="font-size:10px; font-weight:600; padding:4px 12px; background:rgba(255,255,255,0.06); color:rgba(255,255,255,0.5); border:none; border-radius:6px; cursor:pointer;">Cancelar</button>
                            </div>
                        </div>

                        {{-- Reactions (agent side, right-aligned) --}}
                        @if(!empty($msg->reactions))
                        @php
                            $rxGroupedA = collect($msg->reactions)->groupBy('emoji');
                            $myEmojisA = collect($msg->reactions)->where('phone', $myReactionPhone)->pluck('emoji')->toArray();
                        @endphp
                        <div style="display:flex; gap:4px; flex-wrap:wrap; justify-content:flex-end; margin-top:4px; margin-right:2px;">
                            @foreach($rxGroupedA as $rxEmoji => $rxListA)
                            @php $isMineA = in_array($rxEmoji, $myEmojisA); @endphp
                            <button wire:click="reactToMessage({{ $msg->id }}, '{{ $rxEmoji }}')"
                                    style="background:{{ $isMineA ? 'rgba(178,255,0,0.2)' : 'rgba(178,255,0,0.1)' }}; border:1px solid {{ $isMineA ? 'rgba(178,255,0,0.4)' : 'rgba(178,255,0,0.2)' }}; border-radius:20px; padding:2px 7px; font-size:13px; line-height:1.5; display:inline-flex; align-items:center; gap:3px; cursor:pointer; transition:all 0.15s;"
                                    onmouseover="this.style.background='{{ $isMineA ? 'rgba(178,255,0,0.3)' : 'rgba(178,255,0,0.15)' }}'"
                                    onmouseout="this.style.background='{{ $isMineA ? 'rgba(178,255,0,0.2)' : 'rgba(178,255,0,0.1)' }}'">{{ $rxEmoji }}@if($rxListA->count() > 1)<span style="font-size:10px; color:rgba(255,255,255,0.4);">{{ $rxListA->count() }}</span>@endif</button>
                            @endforeach
                        </div>
                        @endif
                        <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:3px; text-align:right; margin-right:4px;">
                            {{ $msg->created_at->format('H:i') }}
                            @if($msg->delivery_status === 'read')
                                <span style="color:#b2ff00; margin-left:2px;">✓✓</span>
                            @elseif($msg->delivery_status === 'delivered')
                                <span style="margin-left:2px; color:rgba(255,255,255,0.4);">✓✓</span>
                            @elseif($msg->delivery_status === 'sent')
                                <span style="margin-left:2px; color:rgba(255,255,255,0.4);">✓</span>
                            @elseif($msg->delivery_status === 'failed')
                                <span style="color:#f87171; margin-left:2px;">!</span>
                            @endif
                        </p>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Quick replies dropdown --}}
    @if($showQuickReplies)
    <div style="border-top:1px solid rgba(255,255,255,0.05); background:rgba(11,15,28,0.9); backdrop-filter:blur(8px); padding:12px 16px; max-height:200px; overflow-y:auto; flex-shrink:0;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
            <div style="display:flex; align-items:center; gap:6px;">
                <div style="width:2px; height:12px; background:#b2ff00; border-radius:2px;"></div>
                <p style="font-size:10px; font-weight:700; color:#b2ff00; text-transform:uppercase; letter-spacing:0.08em;">Respostas Rápidas</p>
            </div>
            <button wire:click="$set('showQuickReplies', false)"
                    style="color:rgba(255,255,255,0.25); background:transparent; border:none; cursor:pointer; padding:2px; transition:color 0.15s;"
                    onmouseover="this.style.color='rgba(255,255,255,0.6)'" onmouseout="this.style.color='rgba(255,255,255,0.25)'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <input wire:model.live.debounce.200ms="quickReplySearch" type="text" placeholder="Buscar..."
               style="width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07); border-radius:8px; padding:7px 12px; font-size:12px; color:white; outline:none; transition:all 0.2s; font-family:inherit; box-sizing:border-box; margin-bottom:6px;"
               onfocus="this.style.borderColor='rgba(178,255,0,0.4)'" onblur="this.style.borderColor='rgba(255,255,255,0.07)'">
        @forelse($quickReplies as $qr)
            <button wire:click="useQuickReply('{{ addslashes($qr->content) }}')"
                    style="width:100%; text-align:left; padding:8px 10px; border-radius:8px; border:none; background:transparent; cursor:pointer; transition:background 0.15s; margin-bottom:2px;"
                    onmouseover="this.style.background='rgba(178,255,0,0.06)'" onmouseout="this.style.background='transparent'">
                <p style="font-size:11px; font-weight:600; color:#b2ff00;">{{ $qr->title }}</p>
                <p style="font-size:11px; color:rgba(255,255,255,0.35); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:1px;">{{ $qr->content }}</p>
            </button>
        @empty
            <p style="font-size:11px; color:rgba(255,255,255,0.2); text-align:center; padding:8px;">Nenhuma resposta rápida encontrada</p>
        @endforelse
    </div>
    @endif

    {{-- CRM Panel --}}
    @if($showCrmPanel)
    <div style="border-top:1px solid rgba(255,255,255,0.05); background:rgba(11,15,28,0.9); backdrop-filter:blur(8px); padding:14px 16px; flex-shrink:0;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="width:2px; height:14px; background:#b2ff00; border-radius:2px;"></div>
                <p style="font-size:12px; font-weight:700; color:white;">Mover para o CRM</p>
            </div>
            <button wire:click="$set('showCrmPanel', false)"
                    style="color:rgba(255,255,255,0.2); background:transparent; border:none; cursor:pointer; transition:color 0.15s;"
                    onmouseover="this.style.color='rgba(255,255,255,0.6)'" onmouseout="this.style.color='rgba(255,255,255,0.2)'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Cards CRM existentes do contato --}}
        @if($crmCards->isNotEmpty())
        <div style="margin-bottom:10px;">
            <p style="font-size:9px; color:rgba(255,255,255,0.2); text-transform:uppercase; letter-spacing:0.08em; font-weight:700; margin-bottom:6px;">Já no CRM</p>
            @foreach($crmCards as $cc)
            <div style="display:flex; align-items:center; gap:6px; font-size:11px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); border-radius:8px; padding:7px 10px; margin-bottom:4px;">
                <span style="width:8px; height:8px; border-radius:50%; flex-shrink:0; background: {{ $cc->pipeline?->color }}"></span>
                <span style="color:rgba(255,255,255,0.6); font-weight:500;">{{ $cc->pipeline?->name }}</span>
                <svg width="10" height="10" fill="none" stroke="rgba(255,255,255,0.2)" viewBox="0 0 24 24" style="flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span style="padding:2px 8px; border-radius:5px; font-weight:600; font-size:10px; color:white; background: {{ $cc->stage?->color }}">
                    {{ $cc->stage?->name }}
                </span>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Seletor Pipeline + Etapa --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
            <div>
                <label style="display:block; font-size:9px; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em; font-weight:700; margin-bottom:5px;">Pipeline</label>
                <select wire:model.live="crmPipelineId"
                        style="width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:7px 10px; font-size:12px; color:white; outline:none; transition:all 0.2s; font-family:inherit;"
                        onfocus="this.style.borderColor='rgba(178,255,0,0.4)'" onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
                    <option value="">Selecionar...</option>
                    @foreach($crmPipelines as $pl)
                    <option value="{{ $pl->id }}">{{ $pl->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block; font-size:9px; color:rgba(255,255,255,0.25); text-transform:uppercase; letter-spacing:0.08em; font-weight:700; margin-bottom:5px;">Etapa</label>
                <select wire:model="crmStageId"
                        style="width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:7px 10px; font-size:12px; color:white; outline:none; transition:all 0.2s; font-family:inherit; {{ !$crmPipelineId ? 'opacity:0.4;' : '' }}"
                        onfocus="this.style.borderColor='rgba(178,255,0,0.4)'" onblur="this.style.borderColor='rgba(255,255,255,0.08)'"
                        @if(!$crmPipelineId) disabled @endif>
                    <option value="">Selecionar...</option>
                    @foreach($crmStages as $st)
                    <option value="{{ $st->id }}">{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @error('crmPipelineId') <p style="font-size:11px; color:#f87171; margin-bottom:6px;">{{ $message }}</p> @enderror
        @error('crmStageId')    <p style="font-size:11px; color:#f87171; margin-bottom:6px;">{{ $message }}</p> @enderror

        <div style="display:flex; gap:8px;">
            <button wire:click="saveCrmCard"
                    style="flex:1; display:flex; align-items:center; justify-content:center; gap:6px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-size:12px; font-weight:600; padding:8px; border-radius:8px; border:none; cursor:pointer; transition:all 0.2s; box-shadow:0 2px 12px rgba(178,255,0,0.25);"
                    onmouseover="this.style.boxShadow='0 4px 20px rgba(178,255,0,0.35)'; this.style.transform='translateY(-1px)'"
                    onmouseout="this.style.boxShadow='0 2px 12px rgba(178,255,0,0.25)'; this.style.transform='translateY(0)'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span wire:loading.remove wire:target="saveCrmCard">Confirmar</span>
                <span wire:loading wire:target="saveCrmCard">Salvando...</span>
            </button>
            <button wire:click="$set('showCrmPanel', false)"
                    style="padding:8px 16px; background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.4); font-size:12px; border-radius:8px; border:1px solid rgba(255,255,255,0.07); cursor:pointer; transition:all 0.2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.color='rgba(255,255,255,0.7)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.color='rgba(255,255,255,0.4)'">
                Cancelar
            </button>
        </div>
    </div>
    @endif

    {{-- Transfer modal --}}
    @if($showTransfer)
    <div style="border-top:1px solid rgba(255,255,255,0.05); background:rgba(11,15,28,0.9); backdrop-filter:blur(8px); padding:14px 16px; flex-shrink:0;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
            <div style="width:2px; height:14px; background:#3b82f6; border-radius:2px;"></div>
            <p style="font-size:12px; font-weight:700; color:white;">Transferir conversa</p>
        </div>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <select wire:model.live="transferTo"
                    style="width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:8px 12px; font-size:12px; color:white; outline:none; transition:all 0.2s; font-family:inherit;"
                    onfocus="this.style.borderColor='rgba(59,130,246,0.4)'" onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
                <option value="">Selecionar departamento...</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>

            {{-- Select de agente: aparece quando um departamento é escolhido --}}
            @if($transferTo)
                @if($transferAgents->count() > 0)
                <select wire:model="transferAgent"
                        style="width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:8px 12px; font-size:12px; color:white; outline:none; transition:all 0.2s; font-family:inherit;"
                        onfocus="this.style.borderColor='rgba(59,130,246,0.4)'" onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
                    <option value="">Fila do setor (sem agente específico)</option>
                    @foreach($transferAgents as $ag)
                        <option value="{{ $ag->id }}">{{ $ag->name }}{{ $ag->role === 'supervisor' ? ' (supervisor)' : '' }}</option>
                    @endforeach
                </select>
                @else
                <p style="font-size:11px; color:rgba(255,255,255,0.35); padding:2px 4px;">
                    Nenhum agente ativo neste departamento — vai para a fila do setor.
                </p>
                @endif
            @endif

            <input wire:model="transferReason" type="text" placeholder="Motivo (opcional)"
                   style="width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:8px 12px; font-size:12px; color:white; outline:none; transition:all 0.2s; font-family:inherit; box-sizing:border-box;"
                   onfocus="this.style.borderColor='rgba(59,130,246,0.4)'" onblur="this.style.borderColor='rgba(255,255,255,0.08)'"
                   placeholder-style="color:rgba(255,255,255,0.2)">
            <div style="display:flex; gap:8px;">
                <button wire:click="transferConversation"
                        style="flex:1; background:linear-gradient(135deg, #3b82f6, #2563eb); color:white; font-size:12px; font-weight:600; padding:8px; border-radius:8px; border:none; cursor:pointer; transition:all 0.2s;"
                        onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    Transferir
                </button>
                <button wire:click="$set('showTransfer', false)"
                        style="padding:8px 16px; background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.4); font-size:12px; border-radius:8px; border:1px solid rgba(255,255,255,0.07); cursor:pointer; transition:all 0.2s;"
                        onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.color='rgba(255,255,255,0.7)'"
                        onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.color='rgba(255,255,255,0.4)'">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Message input --}}
    @if($conversation->isOpen() || $conversation->isPending())
    <div style="border-top:1px solid rgba(255,255,255,0.05); padding:12px 16px; flex-shrink:0; background:rgba(8,12,22,0.7); backdrop-filter:blur(8px); position:relative;"
         x-data="{
            showEmoji: false,
            emojiPos: null,
            recording: false,
            recSeconds: 0,
            recTimer: null,
            mediaRecorder: null,
            audioChunks: [],
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
                    this.recording = true;
                    this.recSeconds = 0;
                    this.recTimer = setInterval(() => this.recSeconds++, 1000);
                } catch(e) { alert('Permissão de microfone negada.'); }
            },
            stopRec() {
                if(this.mediaRecorder && this.recording) {
                    this.mediaRecorder.stop();
                    this.recording = false;
                    clearInterval(this.recTimer);
                }
            },
            cancelRec() {
                if(this.mediaRecorder) {
                    this.mediaRecorder.ondataavailable = null;
                    this.mediaRecorder.onstop = null;
                    this.mediaRecorder.stop();
                    this.mediaRecorder.stream?.getTracks().forEach(t => t.stop());
                }
                this.recording = false;
                clearInterval(this.recTimer);
                this.audioChunks = [];
            },
            fmtRec(s) { return Math.floor(s/60)+':'+(''+(s%60)).padStart(2,'0'); }
         }">

        {{-- Preview de arquivo pendente --}}
        {{-- Upload loading indicator --}}
        <div wire:loading wire:target="pendingFile"
             style="display:flex; align-items:center; gap:10px; background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:10px 14px; margin-bottom:8px;">
            <svg style="animation:spin 1s linear infinite; flex-shrink:0;" width="18" height="18" fill="none" stroke="#b2ff00" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span style="font-size:12px; color:rgba(178,255,0,0.7);">Carregando arquivo...</span>
        </div>
        <style>@keyframes spin { to { transform: rotate(360deg); } }</style>

        @if($pendingFile)
        <div style="display:flex; align-items:center; gap:10px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07); border-radius:12px; padding:8px 12px; margin-bottom:8px;">
            @php $mime = $pendingFile->getMimeType() ?? ''; @endphp
            @if(str_starts_with($mime, 'image/'))
                <img src="{{ $pendingFile->temporaryUrl() }}" style="width:44px; height:44px; border-radius:8px; object-fit:cover; flex-shrink:0;">
            @elseif(str_starts_with($mime, 'video/'))
                <div style="width:44px; height:44px; border-radius:8px; background:rgba(96,165,250,0.15); border:1px solid rgba(96,165,250,0.3); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="18" height="18" fill="#60a5fa" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </div>
            @else
                <div style="width:36px; height:36px; border-radius:8px; background:rgba(178,255,0,0.1); border:1px solid rgba(178,255,0,0.2); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="16" height="16" fill="none" stroke="#b2ff00" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            @endif
            <div style="flex:1; min-width:0;">
                <p style="font-size:12px; color:rgba(255,255,255,0.8); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $pendingFile->getClientOriginalName() }}</p>
                <p style="font-size:10px; color:rgba(255,255,255,0.3); margin-top:1px;">{{ number_format($pendingFile->getSize() / 1024, 1) }} KB</p>
            </div>
            <button wire:click="sendFile" wire:loading.attr="disabled"
                    style="padding:6px 12px; background:rgba(178,255,0,0.15); border:1px solid rgba(178,255,0,0.3); color:#b2ff00; font-size:11px; font-weight:600; border-radius:8px; cursor:pointer; transition:all 0.15s;"
                    onmouseover="this.style.background='rgba(178,255,0,0.25)'" onmouseout="this.style.background='rgba(178,255,0,0.15)'">
                <span wire:loading.remove wire:target="sendFile">Enviar</span>
                <span wire:loading wire:target="sendFile">Enviando...</span>
            </button>
            <button wire:click="cancelFile"
                    style="color:rgba(255,255,255,0.2); background:transparent; border:none; cursor:pointer; padding:4px; transition:color 0.15s;"
                    onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='rgba(255,255,255,0.2)'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        @endif

        {{-- Gravação de áudio ativa --}}
        <div x-show="recording" style="display:flex; align-items:center; gap:10px; background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.2); border-radius:12px; padding:8px 12px; margin-bottom:8px;">
            <span style="width:8px; height:8px; border-radius:50%; background:#ef4444; flex-shrink:0; animation:pulse 1.5s ease-in-out infinite;"></span>
            <span style="font-size:12px; color:#f87171; font-family:monospace; flex:1;" x-text="'Gravando... ' + fmtRec(recSeconds)"></span>
            <button @click="cancelRec()"
                    style="font-size:11px; color:rgba(255,255,255,0.3); background:transparent; border:none; cursor:pointer; padding:2px 8px; transition:color 0.15s;"
                    onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='rgba(255,255,255,0.3)'">Cancelar</button>
            <button @click="stopRec()"
                    style="display:flex; align-items:center; gap:5px; font-size:11px; font-weight:600; background:#ef4444; color:white; padding:5px 10px; border-radius:7px; border:none; cursor:pointer; transition:all 0.15s;"
                    onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                <svg width="10" height="10" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12"/></svg>
                Enviar
            </button>
        </div>

        {{-- Emoji picker --}}
        <template x-teleport="body">
        <div x-show="showEmoji"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             @click.outside="showEmoji=false"
             :style="emojiPos ? `position:fixed; bottom:${emojiPos.bottom}px; right:${emojiPos.right}px; z-index:9999;` : 'position:fixed; bottom:80px; right:16px; z-index:9999;'"
             style="background:rgba(11,15,28,0.97); border:1px solid rgba(255,255,255,0.08); border-radius:16px; box-shadow:0 16px 48px rgba(0,0,0,0.5); padding:12px; width:min(300px, 90vw);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                <p style="font-size:9px; color:rgba(255,255,255,0.25); font-weight:700; text-transform:uppercase; letter-spacing:0.08em;">Emojis</p>
                <button @click="showEmoji=false" style="color:rgba(255,255,255,0.2); background:transparent; border:none; cursor:pointer;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div style="display:grid; grid-template-columns:repeat(9, 1fr); gap:2px; max-height:200px; overflow-y:auto;">
                @foreach(['😀','😁','😂','🤣','😃','😄','😅','😆','😉','😊','😋','😎','😍','🥰','😘','🤗','🤩','😐','🙄','😏','😔','😴','😷','🤒','🤧','🥵','🤯','😈','💀','💩','👻','🤖','💪','👋','👌','✌','🤞','👍','👎','✊','👏','🙌','🤝','🙏','❤','🧡','💛','💚','💙','💜','🖤','💔','💯','🔥','✨','⭐','🎉','🎊','🎈','🎁','🏆','🥇','👀','💡','💬','📱','💻','⏰','📅','✅','❌','⚠️','🔔','📢','💰','💳','🛒','🚀','✈️','🌍','🏠','🚗','🍕','🍔','🍟','🌮','🍜','🍣','🍦','🎂','🍺','🍻','☕','🧃','🌹','🌺','🌸','🌻','🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🦋','🌈','⛅','🌙','⚡','🌊'] as $emoji)
                    <button type="button"
                            @click.stop="$wire.set('messageText', ($wire.messageText||'') + '{{ $emoji }}'); showEmoji=false;"
                            style="font-size:18px; padding:4px; border-radius:6px; border:none; background:transparent; cursor:pointer; text-align:center; line-height:1; transition:background 0.1s;"
                            onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">{{ $emoji }}</button>
                @endforeach
            </div>
        </div>
        </template>

        <div style="display:flex; align-items:flex-end; gap:8px;">
            {{-- Attach button --}}
            <div x-data="{ open: false, clipPos: null }" style="position:relative; flex-shrink:0;">
                <button @click="const r=$el.getBoundingClientRect(); clipPos={bottom: window.innerHeight - r.top + 8, left: r.left}; open=!open"
                        title="Anexar"
                        style="width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:10px; border:none; cursor:pointer; transition:all 0.15s; background:rgba(255,255,255,0.04); color:{{ $pendingFile ? '#b2ff00' : 'rgba(255,255,255,0.3)' }}; margin-bottom:2px;"
                        onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.color='rgba(255,255,255,0.7)'"
                        onmouseout="this.style.background='rgba(255,255,255,0.04)'; this.style.color='{{ $pendingFile ? '#b2ff00' : 'rgba(255,255,255,0.3)' }}'">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                </button>
                <template x-teleport="body">
                    <div x-show="open" x-transition @click.outside="open=false"
                         :style="clipPos ? `position:fixed; bottom:${clipPos.bottom}px; left:${clipPos.left}px; z-index:9999;` : 'position:fixed; bottom:80px; left:80px; z-index:9999;'"
                         style="background:rgba(11,15,28,0.97); border:1px solid rgba(255,255,255,0.08); border-radius:12px; box-shadow:0 16px 40px rgba(0,0,0,0.5); overflow:hidden; width:160px;">
                        <label style="display:flex; align-items:center; gap:10px; padding:10px 14px; cursor:pointer; transition:background 0.15s;"
                               onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background='transparent'">
                            <svg width="14" height="14" fill="none" stroke="#60a5fa" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span style="font-size:12px; color:rgba(255,255,255,0.6);">Foto / Vídeo</span>
                            <input type="file" wire:model="pendingFile" @change="open=false" accept="image/*,video/*,.mp4,.mov,.avi,.webm" class="hidden">
                        </label>
                        <label style="display:flex; align-items:center; gap:10px; padding:10px 14px; cursor:pointer; transition:background 0.15s; border-top:1px solid rgba(255,255,255,0.04);"
                               onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background='transparent'">
                            <svg width="14" height="14" fill="none" stroke="#4ade80" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span style="font-size:12px; color:rgba(255,255,255,0.6);">Documento</span>
                            <input type="file" wire:model="pendingFile" @change="open=false" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar" class="hidden">
                        </label>
                    </div>
                </template>
            </div>

            {{-- Quick replies btn --}}
            <button wire:click="$toggle('showQuickReplies')" title="Respostas rápidas"
                    style="width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:10px; border:none; cursor:pointer; transition:all 0.15s; flex-shrink:0; margin-bottom:2px;
                           background:{{ $showQuickReplies ? 'rgba(178,255,0,0.15)' : 'rgba(255,255,255,0.04)' }};
                           color:{{ $showQuickReplies ? '#b2ff00' : 'rgba(255,255,255,0.3)' }};"
                    onmouseover="if(!{{ $showQuickReplies ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='rgba(255,255,255,0.7)'; }"
                    onmouseout="if(!{{ $showQuickReplies ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.04)'; this.style.color='rgba(255,255,255,0.3)'; }">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </button>

            {{-- Text input --}}
            <div style="flex:1; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:14px; overflow:hidden; display:flex; align-items:flex-end; transition:all 0.2s;"
                 onfocusin="this.style.borderColor='rgba(178,255,0,0.4)'; this.style.background='rgba(178,255,0,0.03)'; this.style.boxShadow='0 0 0 3px rgba(178,255,0,0.06)'"
                 onfocusout="this.style.borderColor='rgba(255,255,255,0.08)'; this.style.background='rgba(255,255,255,0.04)'; this.style.boxShadow='none'">
                <textarea
                    wire:model="messageText"
                    wire:keydown.enter.prevent="sendMessage"
                    placeholder="Digite uma mensagem..."
                    rows="1"
                    style="flex:1; background:transparent; padding:10px 12px; font-size:13px; color:white; outline:none; resize:none; max-height:128px; font-family:inherit; line-height:1.5;"
                    x-data
                    x-on:input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                    x-on:message-sent.window="$el.style.height = 'auto'; $el.value = ''; $el.focus()"
                ></textarea>
                {{-- Emoji button --}}
                <button type="button" @click.stop="const r=$el.getBoundingClientRect(); emojiPos={bottom: window.innerHeight - r.top + 8, right: window.innerWidth - r.right}; showEmoji=!showEmoji" title="Emojis"
                        style="padding:8px 10px 10px; color:rgba(255,255,255,0.2); background:transparent; border:none; cursor:pointer; transition:color 0.15s; flex-shrink:0;"
                        onmouseover="this.style.color='#fbbf24'" onmouseout="this.style.color='rgba(255,255,255,0.2)'">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>
            </div>

            {{-- Mic button --}}
            <button type="button" @click="recording ? stopRec() : startRec()" title="Gravar áudio"
                    :style="recording ? 'background:#ef4444; animation:pulse 1.5s ease-in-out infinite;' : ''"
                    style="width:38px; height:38px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:none; cursor:pointer; transition:all 0.2s; background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.4);"
                    onmouseover="this.style.background='rgba(255,255,255,0.09)'; this.style.color='rgba(255,255,255,0.7)'"
                    onmouseout="if(!this.classList.contains('recording')) { this.style.background='rgba(255,255,255,0.05)'; this.style.color='rgba(255,255,255,0.4)'; }">
                <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-7a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                </svg>
            </button>

            {{-- Send button --}}
            <button wire:click="sendMessage"
                    style="width:38px; height:38px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:none; cursor:pointer; transition:all 0.2s; box-shadow:0 2px 10px rgba(178,255,0,0.3);"
                    onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 16px rgba(178,255,0,0.4)'"
                    onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 10px rgba(178,255,0,0.3)'">
                <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
    </div>
    @else
        <div style="border-top:1px solid rgba(255,255,255,0.05); padding:16px; text-align:center; background:rgba(8,12,22,0.5);">
            <p style="font-size:12px; color:rgba(255,255,255,0.2);">Atendimento encerrado.</p>
            <button wire:click="reopenConversation"
                    style="margin-top:6px; font-size:11px; color:#b2ff00; background:transparent; border:none; cursor:pointer; text-decoration:underline; transition:opacity 0.15s;"
                    onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">Reabrir conversa</button>
        </div>
    @endif

    @else
    {{-- Empty state --}}
    <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:rgba(255,255,255,0.15);">
        <div style="width:72px; height:72px; border-radius:20px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity:0.3;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
        </div>
        <p style="font-size:13px; font-weight:500; color:rgba(255,255,255,0.2);">Selecione uma conversa</p>
        <p style="font-size:11px; color:rgba(255,255,255,0.1); margin-top:4px;">Escolha uma conversa na lista ao lado</p>
    </div>
    @endif
</div>

<script>
function chatArea() {
    return {
        searchOpen: false,
        searchQuery: '',
        searchMatches: [],
        searchIndex: -1,

        init() {
            this.$watch('$wire.conversationId', (val) => {
                if (val) {
                    this.clearSearch();
                    this.scrollToBottom(false);
                }
            });
        },

        scrollToBottom(smooth) {
            const go = () => {
                const el = this.$refs.msgContainer;
                if (!el) return;
                el.scrollTo({ top: el.scrollHeight, behavior: smooth ? 'smooth' : 'instant' });
            };
            this.$nextTick(() => requestAnimationFrame(() => requestAnimationFrame(go)));
        },

        doSearch() {
            this.clearHighlights();
            const q = this.searchQuery.trim();
            if (!q) {
                this.searchMatches = [];
                this.searchIndex = -1;
                return;
            }
            const container = this.$refs.msgContainer;
            if (!container) return;

            const els = container.querySelectorAll('[data-msg-text]');
            const matches = [];
            const lq = q.toLowerCase();

            els.forEach(el => {
                const text = el.getAttribute('data-msg-text') || '';
                if (text.toLowerCase().includes(lq)) {
                    // Highlight occurrences
                    const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    const re = new RegExp(`(${escaped})`, 'gi');
                    el.innerHTML = el.getAttribute('data-msg-text').replace(re, '<mark style="background:#facc15;color:#000;border-radius:2px;padding:0 1px;">$1</mark>');
                    matches.push(el);
                }
            });

            this.searchMatches = matches;
            this.searchIndex = matches.length > 0 ? 0 : -1;
            if (matches.length > 0) this.scrollToMatch(0);
        },

        clearHighlights() {
            const container = this.$refs.msgContainer;
            if (!container) return;
            container.querySelectorAll('[data-msg-text]').forEach(el => {
                el.innerHTML = el.getAttribute('data-msg-text');
            });
        },

        clearSearch() {
            this.clearHighlights();
            this.searchQuery = '';
            this.searchMatches = [];
            this.searchIndex = -1;
        },

        nextMatch() {
            if (!this.searchMatches.length) return;
            this.searchIndex = (this.searchIndex + 1) % this.searchMatches.length;
            this.scrollToMatch(this.searchIndex);
        },

        prevMatch() {
            if (!this.searchMatches.length) return;
            this.searchIndex = (this.searchIndex - 1 + this.searchMatches.length) % this.searchMatches.length;
            this.scrollToMatch(this.searchIndex);
        },

        scrollToMatch(index) {
            const el = this.searchMatches[index];
            if (!el) return;
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // Pulse the current match
            this.searchMatches.forEach((m, i) => {
                const marks = m.querySelectorAll('mark');
                marks.forEach(mk => {
                    mk.style.background = i === index ? '#f97316' : '#facc15';
                    mk.style.outline = i === index ? '2px solid #f97316' : 'none';
                });
            });
        }
    }
}
</script>
