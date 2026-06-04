<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<script>document.documentElement.className = localStorage.getItem('crm_theme') || 'dark';</script>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#080C16">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="CRM Flut">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        [x-cloak] { display: none !important; }
        html { font-size: 115%; }
        body { font-family: 'DM Sans', sans-serif; }
        .font-display { font-family: 'Syne', sans-serif; }

        /* Sidebar nav item hover */
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px; border-radius: 10px;
            font-size: 12.5px; font-weight: 500;
            text-decoration: none; transition: all 0.15s ease;
            position: relative; white-space: nowrap; overflow: hidden;
        }
        .nav-item:not(.active):hover {
            background: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.8);
        }
        .nav-item.active {
            background: rgba(178,255,0,0.12);
            color: #b2ff00;
        }
        .nav-item.active::before {
            content: '';
            position: absolute; left: 0; top: 20%; bottom: 20%;
            width: 2px; border-radius: 0 2px 2px 0;
            background: #b2ff00;
        }

        /* Toast */
        .toast-success { border-color: rgba(178,255,0,0.5); }
        .toast-error   { border-color: rgba(239,68,68,0.5); }
        .toast-warning { border-color: rgba(245,158,11,0.5); }

        /* Subtle scrollbar */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 2px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.15); }

        aside::-webkit-scrollbar { width: 6px; }
        aside::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 3px; }
        aside::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }

        /* Lightbox overlay */
        .lightbox-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.88);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* ── Mobile overlay para sidebar ─────────────────── */
        .sidebar-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 40;
        }

        /* ── Responsive ──────────────────────────────────── */
        @media (max-width: 768px) {
            html { font-size: 100%; }
            .mobile-top-bar { display: flex !important; }
            .mobile-sidebar {
                position: fixed !important;
                top: 0; left: 0; bottom: 0;
                width: 260px !important;
                height: 100dvh !important;
                z-index: 41;
                background: #0B0F1C !important;
                overflow-x: hidden !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch;
                overscroll-behavior: contain;
            }
            body.mobile-menu-open { overflow: hidden !important; touch-action: none; }
            .mobile-full { width: 100% !important; min-width: 0 !important; max-width: 100% !important; }
            .mobile-hide { display: none !important; }
            .mobile-col { flex-direction: column !important; }
            .mobile-grid-1 { grid-template-columns: 1fr !important; }
            .mobile-grid-2 { grid-template-columns: repeat(2, 1fr) !important; }
            .mobile-p-sm { padding: 12px !important; }
            .mobile-scroll-x { overflow-x: auto !important; -webkit-overflow-scrolling: touch; }
        }
        /* Fix: opções de select visíveis no tema escuro */
        select option { background: #1a1f2e; color: #e5e7eb; }
    </style>
</head>
<body style="background:#080C16; color:#e5e7eb;" class="antialiased" x-data="{ sidebarOpen: window.innerWidth > 768, mobileMenu: false }" x-effect="document.body.classList.toggle('mobile-menu-open', mobileMenu && window.innerWidth <= 768)" @resize.window="if(window.innerWidth > 768) { mobileMenu = false; sidebarOpen = true; }">

{{-- Toast notifications --}}
<div
    x-data="toastManager()"
    @toast.window="add($event.detail)"
    style="position:fixed; top:16px; right:16px; z-index:9999; display:flex; flex-direction:column; gap:8px; width:320px; max-width:calc(100vw - 32px);"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.visible"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             :class="{
                'toast-success': toast.type === 'success',
                'toast-error':   toast.type === 'error',
                'toast-warning': toast.type === 'warning',
             }"
             style="background:rgba(17,24,39,0.95); border:1px solid; border-radius:12px; padding:12px 16px; box-shadow:0 8px 24px rgba(0,0,0,0.4); display:flex; align-items:center; gap:10px; font-size:13px; backdrop-filter:blur(8px);">
            <span x-show="toast.type === 'success'" style="color:#b2ff00; font-size:16px; flex-shrink:0;">✓</span>
            <span x-show="toast.type === 'error'"   style="color:#f87171; font-size:16px; flex-shrink:0;">✗</span>
            <span x-show="toast.type === 'warning'" style="color:#fbbf24; font-size:16px; flex-shrink:0;">!</span>
            <span x-text="toast.message" style="flex:1; color:rgba(255,255,255,0.8);"></span>
        </div>
    </template>
</div>

<div style="display:flex; flex-direction:column; height:100vh; height:100dvh; overflow:hidden;">

    {{-- MOBILE TOP BAR --}}
    <div class="mobile-top-bar"
         style="display:none; height:52px; align-items:center; padding:0 14px; background:linear-gradient(180deg, #0B0F1C, #080C16); border-bottom:1px solid rgba(255,255,255,0.05); flex-shrink:0; gap:12px; position:relative; z-index:39;">
        <button @click="mobileMenu = !mobileMenu; if(mobileMenu) sidebarOpen = true;"
                style="color:rgba(255,255,255,0.5); background:rgba(255,255,255,0.05); border:none; cursor:pointer; padding:8px; border-radius:8px;">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <img src="/images/logo-flut.webp" alt="CRM Flut" style="height:22px;">
        <div style="margin-left:auto;">
            <livewire:notification-bell />
        </div>
    </div>

    <div style="display:flex; flex:1; overflow:hidden; min-height:0;">

    {{-- Mobile overlay (só fecha ao clicar no overlay, não na sidebar) --}}
    <template x-if="mobileMenu && window.innerWidth <= 768">
        <div @click="mobileMenu = false" class="sidebar-overlay"></div>
    </template>

    {{-- SIDEBAR --}}
    <aside
        :style="window.innerWidth > 768 ? { width: sidebarOpen ? '220px' : '56px' } : {}"
        x-show="window.innerWidth > 768 || mobileMenu"
        :class="{ 'mobile-sidebar': window.innerWidth <= 768 }"
        style="background: linear-gradient(180deg, #0B0F1C 0%, #080C16 100%); border-right:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; max-height:100vh; transition:width 0.25s cubic-bezier(0.4,0,0.2,1); flex-shrink:0; position:relative; overflow-x:hidden; overflow-y:auto;"
    >
        {{-- Subtle top glow --}}
        <div style="position:absolute; top:0; left:0; right:0; height:1px; background:linear-gradient(90deg, transparent, rgba(178,255,0,0.3), transparent); pointer-events:none;"></div>

        {{-- Logo (desktop only — mobile has top bar) --}}
        <div style="height:60px; display:flex; align-items:center; padding:0 14px; border-bottom:1px solid rgba(255,255,255,0.05); gap:10px; flex-shrink:0;"
             :class="{ 'mobile-hide': window.innerWidth <= 768 && !mobileMenu ? false : false }">
            <template x-if="window.innerWidth > 768">
                <img src="/images/logo-flut.webp" alt="CRM Flut"
                     :style="sidebarOpen ? 'height:28px; width:auto;' : 'height:24px; width:24px; object-fit:contain; object-position:left;'"
                     style="flex-shrink:0; transition:all 0.25s;">
            </template>
            <template x-if="window.innerWidth <= 768">
                <span style="font-size:13px; font-weight:700; color:rgba(255,255,255,0.6); font-family:'Syne',sans-serif;">Menu</span>
            </template>
            {{-- Notification bell (desktop only) --}}
            <div x-show="window.innerWidth > 768" style="margin-left:auto; flex-shrink:0;">
                <livewire:notification-bell />
            </div>
            {{-- Desktop: toggle sidebar | Mobile: fechar menu --}}
            <button @click="window.innerWidth <= 768 ? (mobileMenu = false) : (sidebarOpen = !sidebarOpen)"
                    style="color:rgba(255,255,255,0.2); background:transparent; border:none; cursor:pointer; padding:4px; border-radius:6px; transition:all 0.15s; flex-shrink:0; margin-left:auto;"
                    onmouseover="this.style.color='rgba(255,255,255,0.6)'; this.style.background='rgba(255,255,255,0.05)'"
                    onmouseout="this.style.color='rgba(255,255,255,0.2)'; this.style.background='transparent'">
                <svg x-show="window.innerWidth > 768" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="window.innerWidth <= 768" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Current company switcher (com dropdown rápido pra admin) --}}
        <livewire:company-switcher />

        {{-- Navigation --}}
        @php
            // Admin do sistema vê TODOS os módulos. Agentes/supervisores veem só os da empresa.
            $isSystemAdmin = auth()->user()->isAdmin();
            $isSupervisor  = auth()->user()->isSupervisor();
            $companyModules = app(\App\Services\CurrentCompany::class)->model()?->modules ?? [];
            $userModules = auth()->user()->modules; // null = todos, array = restritos
            // Admin vê tudo. Supervisor vê todos os módulos da empresa.
            // Agentes seguem a restrição de $userModules.
            $canSee = fn(string $mod) => $isSystemAdmin
                || ($isSupervisor && in_array($mod, $companyModules, true))
                || (!$isSupervisor && in_array($mod, $companyModules, true) && ($userModules === null || in_array($mod, $userModules, true)));
        @endphp
        <nav @click="if(window.innerWidth <= 768 && $event.target.closest('a')) mobileMenu = false"
             style="padding:10px 8px; display:flex; flex-direction:column; gap:2px;">
            @if(!auth()->user()->isVendedor())
            <p x-show="sidebarOpen" style="padding:8px 6px 4px; font-size:9px; font-weight:700; color:rgba(255,255,255,0.2); text-transform:uppercase; letter-spacing:0.1em;">
                Principal
            </p>

            @if($canSee('dashboard'))
            <a href="{{ route('dashboard') }}"
               class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
               style="color:{{ request()->routeIs('dashboard') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span x-show="sidebarOpen">Dashboard</span>
            </a>
            @endif

            @if($canSee('chat'))
            <a href="{{ route('chat') }}"
               class="nav-item {{ request()->routeIs('chat*') ? 'active' : '' }}"
               style="color:{{ request()->routeIs('chat*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
                <span x-show="sidebarOpen">Atendimento</span>
            </a>
            @endif

            @if($canSee('internal-chat'))
            <a href="{{ route('internal-chat.index') }}"
               class="nav-item {{ request()->routeIs('internal-chat*') ? 'active' : '' }}"
               style="color:{{ request()->routeIs('internal-chat*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2v-1m0-3V6a2 2 0 012-2h6l4 4v4a2 2 0 01-2 2h-1"/>
                </svg>
                <span x-show="sidebarOpen">Chat Interno</span>
                @php $internalUnread = \App\Models\InternalMessage::where('recipient_id', auth()->id())->where('is_read', false)->count(); @endphp
                @if($internalUnread > 0)
                <span style="min-width:16px; height:16px; padding:0 4px; border-radius:20px; background:#b2ff00; color:#111; font-size:9px; font-weight:800; display:flex; align-items:center; justify-content:center;">{{ $internalUnread }}</span>
                @endif
            </a>
            @endif

            @if($canSee('crm'))
            <a href="{{ route('crm.index') }}"
               class="nav-item {{ request()->routeIs('crm.index') ? 'active' : '' }}"
               style="color:{{ request()->routeIs('crm.index') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
                <span x-show="sidebarOpen">CRM</span>
            </a>
            <a href="{{ route('crm.tasks') }}"
               class="nav-item {{ request()->routeIs('crm.tasks') ? 'active' : '' }}"
               style="color:{{ request()->routeIs('crm.tasks') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}; padding-left:28px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <span x-show="sidebarOpen">Tarefas</span>
            </a>
            @endif

            @if($canSee('leads'))
            <a href="{{ route('leads.index') }}"
               class="nav-item {{ request()->routeIs('leads*') ? 'active' : '' }}"
               style="color:{{ request()->routeIs('leads*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span x-show="sidebarOpen">Leads</span>
            </a>
            @endif

            @if($canSee('broadcasts'))
            <a href="{{ route('broadcasts.index') }}"
               class="nav-item {{ request()->routeIs('broadcasts*') ? 'active' : '' }}"
               style="color:{{ request()->routeIs('broadcasts*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
                <span x-show="sidebarOpen">Disparos</span>
            </a>
            @endif
            @endif{{-- /!isVendedor --}}

            {{-- Gestão da empresa — admin + supervisor, filtrado por módulos contratados --}}
            @if(auth()->user()->canManageCompany())
            @php
                $hasAnyGestao = $isSystemAdmin || $isSupervisor || count(array_intersect($companyModules, array_keys(\App\Models\Company::AVAILABLE_MODULES['gestao']))) > 0;
            @endphp
            @if($hasAnyGestao)
            <div style="margin-top:8px; display:flex; flex-direction:column; gap:2px;">
                <p x-show="sidebarOpen" style="padding:8px 6px 4px; font-size:9px; font-weight:700; color:rgba(255,255,255,0.2); text-transform:uppercase; letter-spacing:0.1em;">
                    Gestão
                </p>

                @if($canSee('admin.crm'))
                <a href="{{ route('admin.crm.index') }}"
                   class="nav-item {{ request()->routeIs('admin.crm*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.crm*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                    <span x-show="sidebarOpen">Pipelines CRM</span>
                </a>
                @endif

                @if($canSee('admin.departments'))
                <a href="{{ route('admin.departments.index') }}"
                   class="nav-item {{ request()->routeIs('admin.departments*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.departments*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span x-show="sidebarOpen">Departamentos</span>
                </a>
                @endif

                @if($canSee('admin.agents'))
                <a href="{{ route('admin.agents.index') }}"
                   class="nav-item {{ request()->routeIs('admin.agents*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.agents*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span x-show="sidebarOpen">Agentes</span>
                </a>
                @endif

                @if($canSee('admin.chatbot'))
                <a href="{{ route('admin.chatbot.index') }}"
                   class="nav-item {{ request()->routeIs('admin.chatbot*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.chatbot*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                    <span x-show="sidebarOpen">Chatbot</span>
                </a>
                @endif

                @if($canSee('admin.ia'))
                <a href="{{ route('admin.ia.index') }}"
                   class="nav-item {{ request()->routeIs('admin.ia*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.ia*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <span x-show="sidebarOpen">IA de Atendimento</span>
                </a>
                @endif

                <a href="{{ route('admin.backups.index') }}"
                   class="nav-item {{ request()->routeIs('admin.backups*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.backups*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    <span x-show="sidebarOpen">Backup</span>
                </a>

                <a href="{{ route('admin.quick-replies.index') }}"
                   class="nav-item {{ request()->routeIs('admin.quick-replies*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.quick-replies*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                    <span x-show="sidebarOpen">Respostas Rápidas</span>
                </a>

                @if($canSee('admin.automation'))
                <a href="{{ route('admin.api.index') }}"
                   class="nav-item {{ request()->routeIs('admin.api*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.api*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span x-show="sidebarOpen">Automação</span>
                </a>
                @endif

                @if($canSee('admin.flut-chat'))
                <a href="{{ route('admin.flut-chat.index') }}"
                   class="nav-item {{ request()->routeIs('admin.flut-chat*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.flut-chat*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <span x-show="sidebarOpen">Flut Chat</span>
                </a>
                @endif

                @if($canSee('admin.landing-pages'))
                <a href="{{ route('admin.landing-pages.index') }}"
                   class="nav-item {{ request()->routeIs('admin.landing-pages*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.landing-pages*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V9a2 2 0 012-2h2a2 2 0 012 2v9a2 2 0 01-2 2h-2z"/>
                    </svg>
                    <span x-show="sidebarOpen">Landing Pages</span>
                </a>
                @endif

                @if($canSee('admin.link-in-bio'))
                <a href="{{ route('admin.link-in-bio.index') }}"
                   class="nav-item {{ request()->routeIs('admin.link-in-bio*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.link-in-bio*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <span x-show="sidebarOpen">Link in Bio</span>
                </a>
                @endif

                @if($canSee('admin.audit'))
                <a href="{{ route('admin.audit.index') }}"
                   class="nav-item {{ request()->routeIs('admin.audit*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.audit*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span x-show="sidebarOpen">Auditoria</span>
                </a>
                @endif
            </div>
            @endif
            @endif

            {{-- Sistema — somente admin --}}
            @if(auth()->user()->isAdmin())
            <div style="margin-top:8px; display:flex; flex-direction:column; gap:2px;">
                <p x-show="sidebarOpen" style="padding:8px 6px 4px; font-size:9px; font-weight:700; color:rgba(255,255,255,0.2); text-transform:uppercase; letter-spacing:0.1em;">
                    Sistema
                </p>

                <a href="{{ route('admin.companies.index') }}"
                   class="nav-item {{ request()->routeIs('admin.companies*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.companies*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9h.01M9 13h.01M9 17h.01"/>
                    </svg>
                    <span x-show="sidebarOpen">Empresas</span>
                </a>

                <a href="{{ route('admin.global-settings.index') }}"
                   class="nav-item {{ request()->routeIs('admin.global-settings*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.global-settings*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <span x-show="sidebarOpen">Config. Globais</span>
                </a>

                {{-- Evolution API (temporariamente oculto)
                <a href="{{ route('admin.evolution.index') }}"
                   class="nav-item {{ request()->routeIs('admin.evolution*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.evolution*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span x-show="sidebarOpen">Evolution API</span>
                </a>
                --}}

                <a href="{{ route('admin.meta-whatsapp.index') }}"
                   class="nav-item {{ request()->routeIs('admin.meta-whatsapp*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.meta-whatsapp*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
                    </svg>
                    <span x-show="sidebarOpen">Meta WhatsApp</span>
                </a>

                <a href="{{ route('admin.templates.index') }}"
                   class="nav-item {{ request()->routeIs('admin.templates*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.templates*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                    <span x-show="sidebarOpen">Templates</span>
                </a>

            </div>
            @endif

            {{-- Comercial — admin e vendedor --}}
            @if(auth()->user()->isAdmin() || auth()->user()->isVendedor())
            <div style="margin-top:8px; display:flex; flex-direction:column; gap:2px;">
                <p x-show="sidebarOpen" style="padding:8px 6px 4px; font-size:9px; font-weight:700; color:rgba(255,255,255,0.2); text-transform:uppercase; letter-spacing:0.1em;">
                    Comercial
                </p>

                <a href="{{ route('admin.onboardings.index') }}"
                   class="nav-item {{ request()->routeIs('admin.onboardings*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.onboardings*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <span x-show="sidebarOpen">Onboardings</span>
                </a>

                <a href="{{ route('admin.pricing.index') }}"
                   class="nav-item {{ request()->routeIs('admin.pricing*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.pricing*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-show="sidebarOpen">Precificação</span>
                </a>

                <a href="{{ route('admin.proposals.index') }}"
                   class="nav-item {{ request()->routeIs('admin.proposals*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.proposals*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span x-show="sidebarOpen">Propostas</span>
                </a>
            </div>
            @endif
        </nav>

        {{-- User info --}}
        <div style="padding:10px 8px; border-top:1px solid rgba(255,255,255,0.05); flex-shrink:0;">
            <div style="display:flex; align-items:center; gap:10px; padding:8px 6px; border-radius:10px; background:rgba(255,255,255,0.02);">
                <div style="position:relative; flex-shrink:0;">
                    <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}"
                         style="width:30px; height:30px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.1);">
                    <span style="position:absolute; bottom:-1px; right:-1px; width:9px; height:9px; border-radius:50%; border:2px solid #0B0F1C;
                                 background:{{ auth()->user()->status === 'online' ? '#22c55e' : (auth()->user()->status === 'busy' ? '#eab308' : '#6b7280') }};">
                    </span>
                </div>
                <div x-show="sidebarOpen" style="flex:1; min-width:0; overflow:hidden;">
                    <p style="font-size:12px; font-weight:600; color:rgba(255,255,255,0.8); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ auth()->user()->name }}</p>
                    <p style="font-size:10px; color:rgba(255,255,255,0.25); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ auth()->user()->department?->name ?? 'Admin' }}</p>
                </div>
                <form x-show="sidebarOpen" method="POST" action="{{ route('logout') }}" style="flex-shrink:0;">
                    @csrf
                    <button type="submit"
                            style="color:rgba(255,255,255,0.2); background:transparent; border:none; cursor:pointer; padding:4px; transition:color 0.15s;"
                            onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='rgba(255,255,255,0.2)'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- MAIN CONTENT --}}
    <main style="flex:1; min-height:0; overflow:hidden; display:flex; flex-direction:column;">
        {{ $slot }}
    </main>
    </div>{{-- /flex row --}}
</div>{{-- /flex col --}}

<script>
function toastManager() {
    return {
        toasts: [],
        add(detail) {
            const id = Date.now();
            const toast = { id, type: detail.type ?? 'success', message: detail.message ?? '', visible: true };
            this.toasts.push(toast);
            setTimeout(() => { toast.visible = false; setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 300); }, 3500);
        }
    }
}
</script>

{{-- Lightbox global (imagem + vídeo) --}}
<div x-data="{
        src: null, alt: '', isVideo: false, msgId: null,
        closeLightbox() {
            if (this.$refs.lbVideo) { this.$refs.lbVideo.pause(); this.$refs.lbVideo.removeAttribute('src'); this.$refs.lbVideo.load(); }
            this.src = null; this.isVideo = false; this.msgId = null;
        }
     }"
     @open-lightbox.window="src = $event.detail.src; alt = $event.detail.alt || ''; isVideo = $event.detail.video || false; msgId = $event.detail.msgId || null"
     x-show="src" x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="closeLightbox()"
     @keydown.escape.window="closeLightbox()"
     class="lightbox-overlay">
    <div @click.stop style="position:absolute; top:16px; right:16px; display:flex; gap:8px; z-index:10;">
        {{-- Download --}}
        <a x-show="!isVideo && msgId" x-bind:href="'/media/download/' + msgId" @click.stop
                style="color:rgba(255,255,255,0.5); background:rgba(255,255,255,0.06); border:none; cursor:pointer; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; transition:all 0.15s; text-decoration:none;"
                onmouseover="this.style.background='rgba(255,255,255,0.12)'; this.style.color='white'"
                onmouseout="this.style.background='rgba(255,255,255,0.06)'; this.style.color='rgba(255,255,255,0.5)'"
                title="Download">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
        </a>
        {{-- Download vídeo --}}
        <a x-show="isVideo" :href="src" download @click.stop
           style="color:rgba(255,255,255,0.5); background:rgba(255,255,255,0.06); border:none; cursor:pointer; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; transition:all 0.15s; text-decoration:none;"
           onmouseover="this.style.background='rgba(255,255,255,0.12)'; this.style.color='white'"
           onmouseout="this.style.background='rgba(255,255,255,0.06)'; this.style.color='rgba(255,255,255,0.5)'"
           title="Download vídeo">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
        </a>
        {{-- Fechar --}}
        <button @click.stop="closeLightbox()"
                style="color:rgba(255,255,255,0.5); background:rgba(255,255,255,0.06); border:none; cursor:pointer; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; transition:all 0.15s;"
                onmouseover="this.style.background='rgba(255,255,255,0.12)'; this.style.color='white'"
                onmouseout="this.style.background='rgba(255,255,255,0.06)'; this.style.color='rgba(255,255,255,0.5)'">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    {{-- Imagem --}}
    <img x-show="!isVideo" :src="src" :alt="alt"
         @click.stop
         style="max-height:90vh; max-width:90vw; object-fit:contain; border-radius:12px; box-shadow:0 24px 64px rgba(0,0,0,0.6);">
    {{-- Vídeo --}}
    <video x-ref="lbVideo" x-show="isVideo" x-cloak :src="src" controls autoplay
           @click.stop
           style="max-height:90vh; max-width:90vw; border-radius:12px; box-shadow:0 24px 64px rgba(0,0,0,0.6);">
    </video>
</div>

@livewireScripts
@stack('scripts')
{{-- Banner de ativar notificações --}}
<div id="push-banner" style="display:none; position:fixed; bottom:20px; left:50%; transform:translateX(-50%); z-index:9998; background:linear-gradient(135deg, #0f172a, #1e293b); border:1px solid rgba(178,255,0,0.3); border-radius:14px; padding:14px 20px; box-shadow:0 8px 32px rgba(0,0,0,0.5); max-width:400px; width:calc(100% - 32px);">
    <div style="display:flex; align-items:center; gap:12px;">
        <div style="width:36px; height:36px; border-radius:10px; background:rgba(178,255,0,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <svg width="18" height="18" fill="none" stroke="#b2ff00" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        </div>
        <div style="flex:1; min-width:0;">
            <p style="font-size:13px; font-weight:600; color:white;">Ativar notificações</p>
            <p style="font-size:11px; color:rgba(255,255,255,0.4);">Receba alertas de novas mensagens</p>
        </div>
        <button onclick="enablePush()" style="padding:8px 16px; font-size:12px; font-weight:700; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; border:none; border-radius:8px; cursor:pointer; flex-shrink:0;">
            Ativar
        </button>
        <button onclick="document.getElementById('push-banner').style.display='none'; localStorage.setItem('push-dismissed','1')" style="background:none; border:none; color:rgba(255,255,255,0.3); cursor:pointer; padding:4px; flex-shrink:0;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>

<script>
// Registrar Service Worker (limpar SWs estranhos)
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(regs => {
        // Remover SWs que não são o nosso sw.js
        regs.forEach(r => {
            if (!r.active?.scriptURL?.endsWith('/sw.js')) {
                console.log('[Push] Removing foreign SW:', r.active?.scriptURL || r.scope);
                r.unregister();
            }
        });
        // Registrar o nosso
        navigator.serviceWorker.register('/sw.js');
    }).catch(() => {
        navigator.serviceWorker.register('/sw.js');
    });
}

// Função para ativar push (chamada pelo botão)
async function enablePush() {
    try {
        const perm = await Notification.requestPermission();
        if (perm !== 'granted') { alert('Permissão de notificação negada pelo navegador.'); return; }
        await subscribePush();
        document.getElementById('push-banner').style.display = 'none';
        localStorage.setItem('push-subscribed', '1');
    } catch(e) {
        console.error('Push subscribe error:', e);
        alert('Erro ao ativar notificações. Tente novamente.');
    }
}

// Converter base64url para Uint8Array
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
}

// Registrar subscription no backend
async function subscribePush() {
    const reg = await navigator.serviceWorker.ready;
    let sub = await reg.pushManager.getSubscription();
    if (!sub) {
        const vapidKey = '{{ config("services.vapid.public_key") }}';
        if (!vapidKey) throw new Error('VAPID key not configured');
        sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: urlBase64ToUint8Array(vapidKey) });
    }
    const subJson = sub.toJSON();
    const res = await fetch('/push/subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ endpoint: subJson.endpoint, keys: subJson.keys })
    });
    if (!res.ok) throw new Error('Failed to save subscription');
}

// Auto-subscribe se já tem permissão, senão mostrar banner
(async function() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) return;

    if (Notification.permission === 'granted') {
        // Já tem permissão — subscribe silenciosamente
        try { await subscribePush(); console.log('[Push] Subscribed successfully'); } catch(e) { console.error('[Push] Auto-subscribe failed:', e); }
    } else if (Notification.permission === 'default' && !localStorage.getItem('push-dismissed')) {
        // Nunca decidiu — mostrar banner
        setTimeout(() => { document.getElementById('push-banner').style.display = 'block'; }, 3000);
    }
})();

// Teste manual
window.testNotification = async function() {
    if (Notification.permission !== 'granted') { const p = await Notification.requestPermission(); if(p !== 'granted') return alert('Negada'); }
    const reg = await navigator.serviceWorker.ready;
    reg.showNotification('CRM Flut', { body: 'Notificação de teste!', icon: '/icons/icon-192x192.png', badge: '/icons/icon-72x72.png', vibrate: [200,100,200], data: { url: '/dashboard' } });
};
</script>
</body>
</html>
