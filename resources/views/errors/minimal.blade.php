<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — CRM Flut</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            background: #080C16;
            color: #e5e7eb;
            font-family: 'DM Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container { text-align: center; padding: 40px 24px; max-width: 480px; }
        .logo { height: 40px; margin-bottom: 40px; }
        .code {
            font-family: 'Syne', sans-serif;
            font-size: 120px;
            font-weight: 800;
            letter-spacing: -0.04em;
            background: linear-gradient(135deg, #b2ff00, #8fcc00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 16px;
        }
        .title { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 700; color: white; margin-bottom: 12px; }
        .desc { font-size: 14px; color: rgba(255,255,255,0.35); line-height: 1.6; margin-bottom: 32px; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 28px; background: linear-gradient(135deg, #b2ff00, #8fcc00);
            color: #111; font-size: 14px; font-weight: 700; border-radius: 12px;
            text-decoration: none; transition: all 0.2s; box-shadow: 0 2px 16px rgba(178,255,0,0.25);
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(178,255,0,0.35); }
        .glow { position: fixed; width: 300px; height: 300px; border-radius: 50%; filter: blur(120px); opacity: 0.06; pointer-events: none; }
        .glow-1 { background: #b2ff00; top: 10%; left: 15%; }
        .glow-2 { background: #b2ff00; bottom: 10%; right: 15%; }
    </style>
</head>
<body>
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>
    <div class="container">
        <img src="/images/logo-flut.webp" alt="CRM Flut" class="logo">
        <div class="code">@yield('code')</div>
        <h1 class="title">@yield('title')</h1>
        <p class="desc">@yield('message')</p>
        <a href="{{ url('/') }}" class="btn">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Voltar ao início
        </a>
    </div>
</body>
</html>
