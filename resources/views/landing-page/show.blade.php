<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title }}</title>
    @if($page->description)<meta name="description" content="{{ $page->description }}">@endif
    @if($page->og_image)<meta property="og:image" content="{{ $page->og_image }}">@endif
    <meta property="og:title" content="{{ $page->title }}">
    <meta property="og:type" content="website">
    @if($page->favicon)<link rel="icon" href="{{ $page->favicon }}">@endif
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;overflow-x:hidden}
        .lp-section{padding:60px 20px}
        .lp-container{max-width:1100px;margin:0 auto}
        .lp-btn{display:inline-block;padding:14px 32px;border-radius:8px;font-size:16px;font-weight:700;text-decoration:none;cursor:pointer;border:none;transition:all .2s}
        .lp-btn:hover{opacity:.9;transform:translateY(-1px)}
        .lp-grid{display:grid;gap:24px}
        .lp-grid-3{grid-template-columns:repeat(auto-fit,minmax(280px,1fr))}
        .lp-input{width:100%;padding:12px 16px;border-radius:8px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.06);color:inherit;font-size:14px;outline:none;font-family:inherit}
        .lp-input:focus{border-color:rgba(178,255,0,.5)}
        @media(max-width:768px){.lp-section{padding:40px 16px} .lp-grid-3{grid-template-columns:1fr}}
        {{ $page->custom_css }}
    </style>
    @if($page->fb_pixel)
    <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{{ $page->fb_pixel }}');fbq('track','PageView');</script>
    @endif
    @if($page->ga_id)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $page->ga_id }}"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','{{ $page->ga_id }}');</script>
    @endif
</head>
<body>
@foreach($sections as $section)
@php $cfg = $section->config ?? []; @endphp

@if($section->type === 'header')
<nav style="background:{{ $cfg['bg_color'] ?? '#111827' }};color:{{ $cfg['text_color'] ?? '#fff' }};padding:16px 20px;display:flex;align-items:center;justify-content:space-between;">
    <div style="display:flex;align-items:center;gap:12px;">
        @if(!empty($cfg['logo']))<img src="{{ $cfg['logo'] }}" alt="" style="height:36px;">@endif
        <span style="font-weight:700;font-size:16px;">{{ $company->name }}</span>
    </div>
    <div style="display:flex;gap:20px;">
        @foreach($cfg['links'] ?? [] as $link)
        <a href="{{ $link['url'] }}" style="color:inherit;text-decoration:none;font-size:14px;opacity:.8;">{{ $link['label'] }}</a>
        @endforeach
    </div>
</nav>

@elseif($section->type === 'hero')
<section class="lp-section" style="background:{{ $cfg['bg_color'] ?? '#111827' }};color:{{ $cfg['text_color'] ?? '#fff' }};text-align:center;padding:80px 20px;{{ !empty($cfg['bg_image']) ? 'background-image:url('.$cfg['bg_image'].');background-size:cover;background-position:center;' : '' }}">
    <div class="lp-container">
        <h1 style="font-size:clamp(28px,5vw,48px);font-weight:800;margin-bottom:16px;line-height:1.2;">{{ $cfg['title'] ?? '' }}</h1>
        @if(!empty($cfg['subtitle']))<p style="font-size:clamp(16px,2vw,20px);opacity:.7;margin-bottom:32px;max-width:600px;margin-left:auto;margin-right:auto;">{{ $cfg['subtitle'] }}</p>@endif
        @if(!empty($cfg['cta_text']))<a href="{{ $cfg['cta_url'] ?? '#' }}" class="lp-btn" style="background:{{ $cfg['button_color'] ?? '#b2ff00' }};color:#111;">{{ $cfg['cta_text'] }}</a>@endif
    </div>
</section>

@elseif($section->type === 'features')
<section class="lp-section" style="background:{{ $cfg['bg_color'] ?? '#0f172a' }};color:{{ $cfg['text_color'] ?? '#fff' }};">
    <div class="lp-container">
        @if(!empty($cfg['title']))<h2 style="text-align:center;font-size:28px;font-weight:700;margin-bottom:40px;">{{ $cfg['title'] }}</h2>@endif
        <div class="lp-grid lp-grid-3">
            @foreach($cfg['items'] ?? [] as $item)
            <div style="text-align:center;padding:24px;">
                <div style="font-size:36px;margin-bottom:12px;">{{ $item['icon'] ?? '⭐' }}</div>
                <h3 style="font-size:18px;font-weight:700;margin-bottom:8px;">{{ $item['title'] ?? '' }}</h3>
                <p style="opacity:.7;font-size:14px;">{{ $item['desc'] ?? '' }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

@elseif($section->type === 'testimonials')
<section class="lp-section" style="background:{{ $cfg['bg_color'] ?? '#1e293b' }};color:{{ $cfg['text_color'] ?? '#fff' }};">
    <div class="lp-container">
        @if(!empty($cfg['title']))<h2 style="text-align:center;font-size:28px;font-weight:700;margin-bottom:40px;">{{ $cfg['title'] }}</h2>@endif
        <div class="lp-grid lp-grid-3">
            @foreach($cfg['items'] ?? [] as $item)
            <div style="background:rgba(255,255,255,.05);border-radius:12px;padding:24px;">
                <p style="font-size:14px;font-style:italic;opacity:.8;margin-bottom:16px;">"{{ $item['text'] ?? '' }}"</p>
                <p style="font-weight:700;font-size:13px;">{{ $item['name'] ?? '' }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

@elseif($section->type === 'form')
<section id="form" class="lp-section" style="background:{{ $cfg['bg_color'] ?? '#0f172a' }};color:{{ $cfg['text_color'] ?? '#fff' }};">
    <div class="lp-container" style="max-width:500px;">
        @if(!empty($cfg['title']))<h2 style="text-align:center;font-size:28px;font-weight:700;margin-bottom:32px;">{{ $cfg['title'] }}</h2>@endif
        <form id="lp-form-{{ $section->id }}" onsubmit="return submitLpForm(event, {{ $page->id }}, {{ $section->id }})">
            @foreach($cfg['fields'] ?? [] as $field)
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;margin-bottom:4px;opacity:.6;">{{ $field['label'] }}{{ !empty($field['required']) ? ' *' : '' }}</label>
                <input type="{{ $field['type'] ?? 'text' }}" name="{{ $field['key'] }}" class="lp-input" placeholder="{{ $field['label'] }}" {{ !empty($field['required']) ? 'required' : '' }}>
            </div>
            @endforeach
            <button type="submit" class="lp-btn" style="width:100%;background:{{ $cfg['button_color'] ?? '#b2ff00' }};color:#111;margin-top:8px;">{{ $cfg['button_text'] ?? 'Enviar' }}</button>
        </form>
    </div>
</section>

@elseif($section->type === 'cta')
<section class="lp-section" style="background:{{ $cfg['bg_color'] ?? '#b2ff00' }};color:{{ $cfg['text_color'] ?? '#111' }};text-align:center;">
    <div class="lp-container">
        <h2 style="font-size:32px;font-weight:800;margin-bottom:12px;">{{ $cfg['title'] ?? '' }}</h2>
        @if(!empty($cfg['subtitle']))<p style="font-size:16px;opacity:.7;margin-bottom:24px;">{{ $cfg['subtitle'] }}</p>@endif
        @if(!empty($cfg['button_text']))<a href="{{ $cfg['button_url'] ?? '#' }}" class="lp-btn" style="background:{{ $cfg['text_color'] ?? '#111' }};color:{{ $cfg['bg_color'] ?? '#b2ff00' }};">{{ $cfg['button_text'] }}</a>@endif
    </div>
</section>

@elseif($section->type === 'text')
<section class="lp-section" style="background:{{ $cfg['bg_color'] ?? '#fff' }};color:{{ $cfg['text_color'] ?? '#333' }};">
    <div class="lp-container" style="max-width:800px;font-size:16px;line-height:1.8;">{!! nl2br(e($cfg['content'] ?? '')) !!}</div>
</section>

@elseif($section->type === 'faq')
<section class="lp-section" style="background:{{ $cfg['bg_color'] ?? '#1e293b' }};color:{{ $cfg['text_color'] ?? '#fff' }};">
    <div class="lp-container" style="max-width:700px;">
        @if(!empty($cfg['title']))<h2 style="text-align:center;font-size:28px;font-weight:700;margin-bottom:32px;">{{ $cfg['title'] }}</h2>@endif
        @foreach($cfg['items'] ?? [] as $i => $faq)
        <details style="margin-bottom:8px;background:rgba(255,255,255,.05);border-radius:8px;padding:16px;">
            <summary style="font-weight:600;cursor:pointer;font-size:15px;">{{ $faq['q'] ?? '' }}</summary>
            <p style="margin-top:10px;opacity:.7;font-size:14px;">{{ $faq['a'] ?? '' }}</p>
        </details>
        @endforeach
    </div>
</section>

@elseif($section->type === 'stats')
<section class="lp-section" style="background:{{ $cfg['bg_color'] ?? '#111827' }};color:{{ $cfg['text_color'] ?? '#fff' }};text-align:center;">
    <div class="lp-container">
        <div class="lp-grid lp-grid-3">
            @foreach($cfg['items'] ?? [] as $stat)
            <div><p style="font-size:42px;font-weight:800;">{{ $stat['value'] ?? '' }}</p><p style="opacity:.6;font-size:14px;">{{ $stat['label'] ?? '' }}</p></div>
            @endforeach
        </div>
    </div>
</section>

@elseif($section->type === 'video')
<section class="lp-section" style="background:{{ $cfg['bg_color'] ?? '#0f172a' }};text-align:center;">
    <div class="lp-container" style="max-width:800px;">
        @if(!empty($cfg['title']))<h2 style="color:#fff;font-size:28px;margin-bottom:24px;">{{ $cfg['title'] }}</h2>@endif
        @if(!empty($cfg['url']))
        @php $videoId = preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/', $cfg['url'], $m) ? $m[1] : null; @endphp
        @if($videoId)<div style="position:relative;padding-bottom:56.25%;height:0;"><iframe src="https://www.youtube.com/embed/{{ $videoId }}" style="position:absolute;top:0;left:0;width:100%;height:100%;border:none;border-radius:12px;" allowfullscreen></iframe></div>@endif
        @endif
    </div>
</section>

@elseif($section->type === 'footer')
<footer style="background:{{ $cfg['bg_color'] ?? '#0f172a' }};color:{{ $cfg['text_color'] ?? 'rgba(255,255,255,0.5)' }};padding:24px 20px;text-align:center;font-size:13px;">
    <p>{{ $cfg['text'] ?? '' }}</p>
    <p style="margin-top:8px;font-size:10px;opacity:.5;"><a href="https://flut.com.br" target="_blank" style="color:inherit;text-decoration:none;">Feito com ⚡ Flut</a></p>
</footer>
@endif

@endforeach

{{-- FlutChat widget --}}
@if($page->flutchat_widget_id)
@php $widget = \App\Models\FlutChatWidget::find($page->flutchat_widget_id); @endphp
@if($widget && $widget->is_active)
<script src="{{ url('/js/flut-chat.js') }}?id={{ $widget->public_id }}&v={{ filemtime(public_path('js/flut-chat.js')) }}"></script>
@endif
@endif

<script>
function submitLpForm(e, pageId, sectionId) {
    e.preventDefault();
    const form = e.target;
    const data = {};
    new FormData(form).forEach((v,k) => data[k] = v);
    const params = new URLSearchParams(window.location.search);

    fetch('/api/lp/' + pageId + '/lead', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            data: data,
            page_url: window.location.href,
            utm_source: params.get('utm_source'),
            utm_medium: params.get('utm_medium'),
            utm_campaign: params.get('utm_campaign'),
        })
    }).then(r => r.json()).then(res => {
        if (res.redirect) { window.location.href = res.redirect; }
        else {
            form.innerHTML = '<p style="text-align:center;font-size:18px;font-weight:700;padding:20px;">✅ Enviado com sucesso! Entraremos em contato em breve.</p>';
        }
    }).catch(() => alert('Erro ao enviar. Tente novamente.'));
    return false;
}
</script>
</body>
</html>
