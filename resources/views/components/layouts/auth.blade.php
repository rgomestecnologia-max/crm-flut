<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        body { font-family: 'DM Sans', sans-serif; }
        .font-display { font-family: 'Syne', sans-serif; }

        /* Animated grid background */
        .grid-bg {
            background-image:
                linear-gradient(rgba(178,255,0,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(178,255,0,0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            animation: grid-drift 20s ease-in-out infinite alternate;
        }
        @keyframes grid-drift {
            0%   { background-position: 0 0; }
            100% { background-position: 24px 24px; }
        }

        /* Orb pulse */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            animation: orb-float 8s ease-in-out infinite;
        }
        .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(178,255,0,0.15) 0%, transparent 70%);
            top: -100px; left: -100px;
            animation-delay: 0s;
        }
        .orb-2 {
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(13,148,136,0.1) 0%, transparent 70%);
            bottom: -80px; right: -80px;
            animation-delay: -4s;
        }
        @keyframes orb-float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%       { transform: translate(30px, -20px) scale(1.05); }
            66%       { transform: translate(-20px, 15px) scale(0.97); }
        }

        /* Signal lines decoration */
        .signal-line {
            position: absolute;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(178,255,0,0.4), transparent);
            animation: signal-scan 4s ease-in-out infinite;
        }
        @keyframes signal-scan {
            0%   { opacity: 0; transform: scaleX(0); }
            50%  { opacity: 1; transform: scaleX(1); }
            100% { opacity: 0; transform: scaleX(0); }
        }

        /* Form animations */
        .form-reveal {
            animation: form-in 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        @keyframes form-in {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .form-reveal:nth-child(1) { animation-delay: 0.1s; }
        .form-reveal:nth-child(2) { animation-delay: 0.2s; }
        .form-reveal:nth-child(3) { animation-delay: 0.3s; }
        .form-reveal:nth-child(4) { animation-delay: 0.4s; }
        .form-reveal:nth-child(5) { animation-delay: 0.5s; }

        /* Custom input styling */
        .auth-input {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 13px 16px;
            color: white;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            width: 100%;
            outline: none;
            transition: all 0.2s ease;
        }
        .auth-input::placeholder { color: rgba(255,255,255,0.2); }
        .auth-input:focus {
            border-color: rgba(178,255,0,0.5);
            background: rgba(178,255,0,0.04);
            box-shadow: 0 0 0 3px rgba(178,255,0,0.08), inset 0 1px 0 rgba(255,255,255,0.04);
        }
        .auth-input.error { border-color: rgba(239,68,68,0.5); }

        /* Checkbox */
        .auth-checkbox {
            width: 16px; height: 16px;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 4px;
            background: rgba(255,255,255,0.03);
            accent-color: #b2ff00;
            cursor: pointer;
        }

        /* Submit button */
        .auth-btn {
            width: 100%;
            padding: 13px;
            border-radius: 12px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: white;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #b2ff00 0%, #8fcc00 50%, #0f766e 100%);
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(178,255,0,0.3), inset 0 1px 0 rgba(255,255,255,0.15);
        }
        .auth-btn::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s ease;
        }
        .auth-btn:hover::before { left: 100%; }
        .auth-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 30px rgba(178,255,0,0.4), inset 0 1px 0 rgba(255,255,255,0.15);
        }
        .auth-btn:active { transform: translateY(0); }

        /* Card */
        .auth-card {
            background: linear-gradient(160deg, rgba(17,24,39,0.95) 0%, rgba(11,15,28,0.98) 100%);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 24px;
            backdrop-filter: blur(20px);
            box-shadow:
                0 0 0 1px rgba(178,255,0,0.06) inset,
                0 32px 64px rgba(0,0,0,0.5),
                0 0 60px rgba(178,255,0,0.06);
        }

        /* Logo mark */
        .logo-mark {
            background: linear-gradient(135deg, rgba(178,255,0,0.2) 0%, rgba(13,148,136,0.1) 100%);
            border: 1px solid rgba(178,255,0,0.25);
            box-shadow: 0 0 24px rgba(178,255,0,0.15), inset 0 1px 0 rgba(255,255,255,0.05);
        }

        /* Dot decoration */
        .dot-grid {
            background-image: radial-gradient(rgba(178,255,0,0.15) 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body style="background: #080C16; min-height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;" class="antialiased">

    {{-- Animated background --}}
    <div class="grid-bg" style="position: absolute; inset: 0; pointer-events: none;"></div>
    <div class="orb orb-1" style="pointer-events:none"></div>
    <div class="orb orb-2" style="pointer-events:none"></div>

    {{-- Decorative signal lines --}}
    <div class="signal-line" style="width: 300px; top: 25%; left: 10%; animation-delay: 0s;"></div>
    <div class="signal-line" style="width: 200px; bottom: 30%; right: 15%; animation-delay: 2s;"></div>
    <div class="signal-line" style="width: 150px; top: 60%; left: 5%; animation-delay: 3.5s;"></div>

    {{-- Dot grid corner decoration --}}
    <div class="dot-grid" style="position:absolute; top:0; right:0; width:200px; height:200px; opacity:0.5; pointer-events:none; mask-image: radial-gradient(circle at top right, black, transparent 70%)"></div>
    <div class="dot-grid" style="position:absolute; bottom:0; left:0; width:200px; height:200px; opacity:0.5; pointer-events:none; mask-image: radial-gradient(circle at bottom left, black, transparent 70%)"></div>

    {{ $slot }}

    @livewireScripts
</body>
</html>
