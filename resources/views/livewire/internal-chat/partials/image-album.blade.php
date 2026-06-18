@php
    $count = count($album);
    $extraCount = $count - 4;
    $albumJson = collect($album)->map(fn($a) => ['src' => $a->media_url, 'msgId' => $a->id])->values()->toJson();
    $albumIds = collect($album)->pluck('id')->join(',');
    $bubbleBg = $isMe ? 'background:rgba(178,255,0,0.1)' : 'background:rgba(255,255,255,0.04)';
    $bubbleBorder = $isMe ? 'border:1px solid rgba(178,255,0,0.15)' : 'border:1px solid rgba(255,255,255,0.06)';
    $bubbleRadius = $isMe ? 'border-radius:14px 14px 4px 14px' : 'border-radius:14px 14px 14px 4px';

    $gridCols = 'grid-template-columns:1fr 1fr';
@endphp
<div style="{{ $bubbleBg }}; {{ $bubbleRadius }}; {{ $bubbleBorder }}; overflow:hidden; max-width:280px;">
    {{-- Sender name (groups, not me) --}}
    @if(!$isMe && !empty($album[0]->sender?->name) && ($isGroup ?? false))
        <p style="font-size:10px; font-weight:700; color:#a78bfa; padding:6px 10px 2px;">{{ $album[0]->sender->name }}</p>
    @endif

    {{-- Image grid --}}
    <div style="display:grid; {{ $gridCols }}; gap:2px; padding:2px;">
        @foreach($album as $index => $img)
            @if($index >= 4)
                @break
            @endif
            <div style="position:relative; overflow:hidden; cursor:zoom-in; {{ $count === 3 && $index === 0 ? 'grid-column:1/-1;' : '' }} aspect-ratio:{{ $count === 3 && $index === 0 ? '2/1' : '1/1' }};"
                 @click="$dispatch('open-lightbox-album', { album: {{ $albumJson }}, startIndex: {{ $index }} })">
                <img src="{{ $img->media_url }}" alt="Imagem" loading="lazy"
                     style="width:100%; height:100%; object-fit:cover; display:block; transition:opacity 0.15s;"
                     onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                @if($index === 3 && $extraCount > 0)
                    <div style="position:absolute; inset:0; background:rgba(0,0,0,0.55); display:flex; align-items:center; justify-content:center;">
                        <span style="font-size:22px; font-weight:800; color:white; font-family:'Syne',sans-serif;">+{{ $extraCount }}</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Download all --}}
    <a href="{{ url('/media/download-album?ids=' . $albumIds) }}"
       style="display:flex; align-items:center; justify-content:center; gap:5px; padding:6px 8px; font-size:10px; color:rgba(255,255,255,0.35); text-decoration:none; border-top:1px solid rgba(255,255,255,0.05); transition:all 0.15s;"
       onmouseover="this.style.color='rgba(255,255,255,0.6)'" onmouseout="this.style.color='rgba(255,255,255,0.35)'">
        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Baixar todas ({{ $count }})
    </a>
</div>
