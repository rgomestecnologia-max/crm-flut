<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{{ $page->title }}</title>
    <meta name="description" content="{{ $page->bio_text ?? $page->title }}">
    <meta property="og:title" content="{{ $page->title }}">
    <meta property="og:description" content="{{ $page->bio_text ?? '' }}">
    @if($page->avatar_url)<meta property="og:image" content="{{ $page->avatar_url }}">@endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    @if($page->fb_pixel)
    <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{{ $page->fb_pixel }}');fbq('track','PageView');</script>
    @endif
    @if($page->ga_id)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $page->ga_id }}"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','{{ $page->ga_id }}');</script>
    @endif
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex; justify-content: center;
            background: {{ $theme['bg_gradient'] ?? $theme['bg_color'] ?? '#0b0f1c' }};
            font-family: {{ $theme['font_family'] ?? 'Inter, sans-serif' }};
            color: {{ $theme['text_color'] ?? '#ffffff' }};
            -webkit-font-smoothing: antialiased;
        }
        .container {
            width: 100%; max-width: 480px; padding: 48px 20px 60px; text-align: center;
        }
        .avatar {
            width: 96px; height: 96px; border-radius: 50%; object-fit: cover;
            border: {{ $theme['avatar_border'] ?? '3px solid rgba(255,255,255,0.3)' }};
            margin: 0 auto 16px;
            animation: fadeInDown 0.6s ease;
        }
        .avatar-placeholder {
            width: 96px; height: 96px; border-radius: 50%; margin: 0 auto 16px;
            border: {{ $theme['avatar_border'] ?? '3px solid rgba(255,255,255,0.3)' }};
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.08);
            animation: fadeInDown 0.6s ease;
        }
        .title { font-size: 22px; font-weight: 700; margin-bottom: 6px; animation: fadeInDown 0.6s ease 0.1s both; }
        .bio { font-size: 14px; opacity: 0.65; margin-bottom: 28px; line-height: 1.6; animation: fadeInDown 0.6s ease 0.2s both; }
        .link-btn {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 15px 24px; margin-bottom: 12px;
            background: {{ $theme['button_bg'] ?? '#b2ff00' }};
            color: {{ $theme['button_text'] ?? '#111' }};
            border: {{ $theme['button_border'] ?? 'none' }};
            border-radius: {{ $theme['button_radius'] ?? '12px' }};
            font-size: 15px; font-weight: 600; text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s, filter 0.2s;
            cursor: pointer;
            animation: fadeInUp 0.5s ease both;
        }
        .link-btn:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 8px 24px rgba(0,0,0,0.25); }
        .link-btn:active { transform: translateY(0) scale(0.98); }
        .header-item { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; margin: 24px 0 10px; opacity: 0.45; }
        .divider { height: 1px; background: currentColor; opacity: 0.08; margin: 18px 0; }
        .social-row { display: flex; justify-content: center; gap: 14px; margin-top: 24px; flex-wrap: wrap; animation: fadeInUp 0.5s ease 0.4s both; }
        .social-btn {
            width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.1); color: inherit; text-decoration: none; font-size: 18px;
            transition: transform 0.2s, background 0.2s, box-shadow 0.2s;
        }
        .social-btn:hover { transform: scale(1.15); background: rgba(255,255,255,0.2); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .powered { margin-top: 48px; font-size: 11px; opacity: 0.25; animation: fadeInUp 0.5s ease 0.6s both; }
        .powered a { color: inherit; text-decoration: none; }
        .powered a:hover { opacity: 0.6; }
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @if($page->custom_css){!! $page->custom_css !!}@endif
    </style>
</head>
<body>
    <div class="container">
        @if($page->avatar_url)
            <img src="{{ $page->avatar_url }}" alt="{{ $page->title }}" class="avatar">
        @else
            <div class="avatar-placeholder">
                <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
            </div>
        @endif

        <h1 class="title">{{ $page->title }}</h1>
        @if($page->bio_text)
            <p class="bio">{{ $page->bio_text }}</p>
        @endif

        @php $socialLinks = $links->where('type', 'social'); $regularLinks = $links->where('type', '!=', 'social'); @endphp

        @foreach($regularLinks as $i => $link)
            @if($link->type === 'header')
                <div class="header-item">{{ $link->title }}</div>
            @elseif($link->type === 'divider')
                <div class="divider"></div>
            @else
                <a href="{{ $link->url }}" target="_blank" rel="noopener" class="link-btn" style="animation-delay: {{ 0.1 + $i * 0.06 }}s" onclick="trackClick({{ $link->id }})">
                    @if($link->icon)<span>{{ $link->icon }}</span>@endif
                    {{ $link->title }}
                </a>
            @endif
        @endforeach

        @if($socialLinks->isNotEmpty())
        <div class="social-row">
            @foreach($socialLinks as $social)
            <a href="{{ $social->url }}" target="_blank" rel="noopener" class="social-btn" title="{{ $social->title }}" onclick="trackClick({{ $social->id }})">
                {{ $social->icon ?? '🔗' }}
            </a>
            @endforeach
        </div>
        @endif

        <div class="powered">Feito com <a href="https://flut.com.br" target="_blank">Flut</a></div>
    </div>

    <script>
    function trackClick(linkId) {
        fetch('/api/bio/click/' + linkId, { method: 'POST', headers: { 'Content-Type': 'application/json' } }).catch(() => {});
    }
    </script>
</body>
</html>
