<div style="display:flex; height:100%; overflow:hidden;" wire:poll.3s>
    {{-- Lista de conversas --}}
    <div style="width:300px; flex-shrink:0; border-right:1px solid rgba(255,255,255,0.06); overflow-y:auto; background:rgba(8,12,22,0.5);">
        <div style="padding:12px 14px; border-bottom:1px solid rgba(255,255,255,0.06);">
            <h3 style="font-size:13px; font-weight:700; color:white; display:flex; align-items:center; gap:8px;">
                <svg width="16" height="16" fill="none" stroke="#b2ff00" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                FlutChat ao Vivo
                @if($conversations->count())
                <span style="min-width:18px; height:18px; padding:0 5px; border-radius:20px; background:#b2ff00; color:#111; font-size:10px; font-weight:800; display:flex; align-items:center; justify-content:center;">{{ $conversations->count() }}</span>
                @endif
            </h3>
        </div>

        @forelse($conversations as $conv)
        <button wire:click="selectConversation({{ $conv->id }})"
                style="width:100%; text-align:left; padding:12px 14px; border:none; cursor:pointer; transition:background 0.1s; display:flex; align-items:center; gap:10px;
                       background:{{ $activeConversationId === $conv->id ? 'rgba(178,255,0,0.08)' : 'transparent' }};
                       border-bottom:1px solid rgba(255,255,255,0.04);"
                onmouseover="this.style.background='{{ $activeConversationId === $conv->id ? 'rgba(178,255,0,0.12)' : 'rgba(255,255,255,0.03)' }}'"
                onmouseout="this.style.background='{{ $activeConversationId === $conv->id ? 'rgba(178,255,0,0.08)' : 'transparent' }}'">
            <div style="width:36px; height:36px; border-radius:50%; background:rgba(178,255,0,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <span style="font-size:13px; font-weight:700; color:#b2ff00;">{{ mb_strtoupper(mb_substr($conv->visitor_name ?? '?', 0, 1)) }}</span>
            </div>
            <div style="flex:1; min-width:0;">
                <p style="font-size:12px; font-weight:600; color:white; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $conv->visitor_name ?: 'Visitante' }}</p>
                <p style="font-size:10px; color:rgba(255,255,255,0.3); margin:1px 0 0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    {{ $conv->widget?->name }} · {{ $conv->last_message_at?->diffForHumans() }}
                </p>
            </div>
        </button>
        @empty
        <p style="padding:30px; text-align:center; font-size:12px; color:rgba(255,255,255,0.2);">Nenhuma conversa ativa</p>
        @endforelse
    </div>

    {{-- Área de mensagens --}}
    <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
        @if($activeConversationId)
        @php $activeConv = $conversations->firstWhere('id', $activeConversationId); @endphp

        {{-- Header --}}
        <div style="padding:12px 16px; border-bottom:1px solid rgba(255,255,255,0.06); display:flex; align-items:center; gap:10px; flex-shrink:0; background:rgba(11,15,28,0.6);">
            <div style="width:32px; height:32px; border-radius:50%; background:rgba(178,255,0,0.1); display:flex; align-items:center; justify-content:center;">
                <span style="font-size:12px; font-weight:700; color:#b2ff00;">{{ mb_strtoupper(mb_substr($activeConv?->visitor_name ?? '?', 0, 1)) }}</span>
            </div>
            <div style="flex:1;">
                <p style="font-size:13px; font-weight:700; color:white; margin:0;">{{ $activeConv?->visitor_name ?: 'Visitante' }}</p>
                <p style="font-size:10px; color:rgba(255,255,255,0.3); margin:0;">{{ $activeConv?->widget?->name }}</p>
            </div>
            <button wire:click="closeConversation({{ $activeConversationId }})" wire:confirm="Encerrar esta conversa?"
                    style="padding:4px 10px; font-size:10px; color:#f87171; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); border-radius:6px; cursor:pointer;">
                Encerrar
            </button>
        </div>

        {{-- Messages --}}
        <div style="flex:1; overflow-y:auto; padding:16px; display:flex; flex-direction:column; gap:8px;"
             x-data x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
             x-effect="$el.scrollTop = $el.scrollHeight">
            @foreach($messages as $msg)
            @if($msg->sender_type === 'visitor')
            <div style="max-width:75%; align-self:flex-start;">
                <div style="background:rgba(31,41,55,0.8); color:rgba(255,255,255,0.88); border-radius:14px 14px 14px 4px; padding:8px 12px; font-size:13px; line-height:1.5;">
                    {{ $msg->content }}
                </div>
                <p style="font-size:9px; color:rgba(255,255,255,0.2); margin-top:2px;">{{ $msg->created_at->format('H:i') }}</p>
            </div>
            @elseif($msg->sender_type === 'agent')
            <div style="max-width:75%; align-self:flex-end;">
                <div style="background:rgba(45,74,8,0.5); color:white; border-radius:14px 14px 4px 14px; padding:8px 12px; font-size:13px; line-height:1.5;">
                    {{ $msg->content }}
                </div>
                <p style="font-size:9px; color:rgba(255,255,255,0.2); margin-top:2px; text-align:right;">{{ $msg->sender?->name ?? 'Agente' }} · {{ $msg->created_at->format('H:i') }}</p>
            </div>
            @else
            <div style="max-width:75%; align-self:flex-start;">
                <div style="background:rgba(99,102,241,0.1); color:rgba(255,255,255,0.7); border-radius:14px; padding:8px 12px; font-size:12px; line-height:1.5; border:1px solid rgba(99,102,241,0.2);">
                    🤖 {{ $msg->content }}
                </div>
                <p style="font-size:9px; color:rgba(255,255,255,0.2); margin-top:2px;">IA · {{ $msg->created_at->format('H:i') }}</p>
            </div>
            @endif
            @endforeach
        </div>

        {{-- Input --}}
        <div style="padding:10px 14px; border-top:1px solid rgba(255,255,255,0.06); display:flex; gap:8px; flex-shrink:0; background:rgba(8,12,22,0.7);">
            <input wire:model="replyText" wire:keydown.enter="sendReply" type="text" placeholder="Digite sua resposta..."
                   style="flex:1; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:8px 14px; font-size:13px; color:white; outline:none;">
            <button wire:click="sendReply"
                    style="width:36px; height:36px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; border-radius:10px; display:flex; align-items:center; justify-content:center; border:none; cursor:pointer; flex-shrink:0;">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </button>
        </div>
        @else
        <div style="flex:1; display:flex; align-items:center; justify-content:center;">
            <p style="font-size:13px; color:rgba(255,255,255,0.2);">Selecione uma conversa para responder</p>
        </div>
        @endif
    </div>
</div>
