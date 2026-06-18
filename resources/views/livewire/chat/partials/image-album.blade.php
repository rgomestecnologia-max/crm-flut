@php
    $count = count($album);
    $visibleCount = min($count, 4);
    $extraCount = $count - 4;
    $isIncoming = $side === 'incoming';
    $bubbleBg = $isIncoming ? 'background:rgba(31,41,55,0.8)' : 'background:rgba(45,74,8,0.5)';
    $bubbleBorder = $isIncoming ? 'border:1px solid rgba(255,255,255,0.06)' : 'border:1px solid rgba(45,74,8,0.6)';
    $bubbleRadius = $isIncoming ? 'border-radius:18px 18px 18px 4px' : 'border-radius:18px 18px 4px 18px';
    $albumJson = collect($album)->map(fn($a) => ['src' => $a->media_url, 'msgId' => $a->id])->values()->toJson();
    $albumIds = collect($album)->pluck('id')->join(',');

    // Grid layout
    if ($count === 2) {
        $gridCols = 'grid-template-columns:1fr 1fr';
        $gridRows = '';
    } elseif ($count === 3) {
        $gridCols = 'grid-template-columns:1fr 1fr';
        $gridRows = '';
    } else {
        $gridCols = 'grid-template-columns:1fr 1fr';
        $gridRows = '';
    }
@endphp
<div style="{{ $bubbleBg }}; {{ $bubbleRadius }}; {{ $bubbleBorder }}; overflow:hidden; max-width:min(300px, 70vw);">
    {{-- Sender name (groups, incoming only) --}}
    @if($isIncoming && !empty($album[0]->sender_name))
        <p style="font-size:11px; font-weight:700; color:{{ senderColor($album[0]->sender_phone ?? $album[0]->sender_name) }}; padding:8px 10px 4px;">{{ $album[0]->sender_name }}</p>
    @endif

    {{-- Image grid --}}
    <div style="display:grid; {{ $gridCols }}; {{ $gridRows }}; gap:2px; padding:2px;">
        @foreach($album as $index => $img)
            @if($index >= 4)
                @break
            @endif
            <div style="position:relative; overflow:hidden; cursor:zoom-in; {{ $count === 3 && $index === 0 ? 'grid-column:1/-1;' : '' }} aspect-ratio: {{ $count === 3 && $index === 0 ? '2/1' : '1/1' }};"
                 @click="$dispatch('open-lightbox-album', { album: {{ $albumJson }}, startIndex: {{ $index }} })">
                <img src="{{ $img->media_thumb_url ?? $img->media_url }}" alt="Imagem"
                     loading="lazy"
                     onerror="if(!this.dataset.fb){this.dataset.fb=1;this.src='{{ $img->media_url }}'}"
                     style="width:100%; height:100%; object-fit:cover; display:block; transition:opacity 0.15s;"
                     onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                @if($index === 3 && $extraCount > 0)
                    <div @click="$dispatch('open-lightbox-album', { album: {{ $albumJson }}, startIndex: 3 })"
                         style="position:absolute; inset:0; background:rgba(0,0,0,0.55); display:flex; align-items:center; justify-content:center;">
                        <span style="font-size:24px; font-weight:800; color:white; font-family:'Syne',sans-serif;">+{{ $extraCount }}</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Caption (first image only) --}}
    @if(!empty($album[0]->content))
        <p style="padding:6px 10px 4px; font-size:11px; color:{{ $isIncoming ? 'rgba(255,255,255,0.6)' : 'rgba(255,255,255,0.7)' }}; white-space:pre-wrap;">{!! \App\Helpers\WhatsAppFormatter::format($album[0]->content) !!}</p>
    @endif

    {{-- Download all button --}}
    <a href="{{ url('/media/download-album?ids=' . $albumIds) }}"
       style="display:flex; align-items:center; justify-content:center; gap:6px; padding:7px 10px; font-size:11px; color:rgba(255,255,255,0.4); text-decoration:none; border-top:1px solid rgba(255,255,255,0.06); transition:all 0.15s;"
       onmouseover="this.style.color='rgba(255,255,255,0.7)'; this.style.background='rgba(255,255,255,0.04)'"
       onmouseout="this.style.color='rgba(255,255,255,0.4)'; this.style.background='transparent'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Baixar todas ({{ $count }})
    </a>
</div>
