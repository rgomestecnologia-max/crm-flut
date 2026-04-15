<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
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

        /* Lightbox overlay — display fica em classe porque Alpine x-show
           remove a propriedade display:flex inline e quebra o centering. */
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
    </style>
</head>
<body style="background:#080C16; color:#e5e7eb;" class="antialiased" x-data="{ sidebarOpen: true }">

{{-- Toast notifications --}}
<div
    x-data="toastManager()"
    @toast.window="add($event.detail)"
    style="position:fixed; top:16px; right:16px; z-index:9999; display:flex; flex-direction:column; gap:8px; width:320px;"
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

<div style="display:flex; height:100vh; overflow:hidden;">

    {{-- SIDEBAR --}}
    <aside
        :style="sidebarOpen ? 'width:220px;' : 'width:56px;'"
        style="background: linear-gradient(180deg, #0B0F1C 0%, #080C16 100%); border-right:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; transition:width 0.25s cubic-bezier(0.4,0,0.2,1); flex-shrink:0; overflow:hidden; position:relative;"
    >
        {{-- Subtle top glow --}}
        <div style="position:absolute; top:0; left:0; right:0; height:1px; background:linear-gradient(90deg, transparent, rgba(178,255,0,0.3), transparent); pointer-events:none;"></div>

        {{-- Logo --}}
        <div style="height:60px; display:flex; align-items:center; padding:0 14px; border-bottom:1px solid rgba(255,255,255,0.05); gap:10px; flex-shrink:0;">
            <img src="/images/logo-flut.webp" alt="CRM Flut"
                 :style="sidebarOpen ? 'height:28px; width:auto;' : 'height:24px; width:24px; object-fit:contain; object-position:left;'"
                 style="flex-shrink:0; transition:all 0.25s;">
            <button @click="sidebarOpen = !sidebarOpen"
                    style="margin-left:auto; color:rgba(255,255,255,0.2); background:transparent; border:none; cursor:pointer; padding:4px; border-radius:6px; transition:all 0.15s; flex-shrink:0;"
                    onmouseover="this.style.color='rgba(255,255,255,0.6)'; this.style.background='rgba(255,255,255,0.05)'"
                    onmouseout="this.style.color='rgba(255,255,255,0.2)'; this.style.background='transparent'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
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
            // Admin vê tudo. Supervisor vê módulos de gestão (admin.*) automaticamente + módulos da empresa.
            // Agentes seguem a restrição de $userModules.
            $canSee = fn(string $mod) => $isSystemAdmin
                || ($isSupervisor && in_array($mod, $companyModules, true) && (str_starts_with($mod, 'admin.') || $userModules === null || in_array($mod, $userModules, true)))
                || (!$isSupervisor && in_array($mod, $companyModules, true) && ($userModules === null || in_array($mod, $userModules, true)));
        @endphp
        <nav style="flex:1; padding:10px 8px; overflow-y:auto; display:flex; flex-direction:column; gap:2px;">
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

            @if($canSee('crm'))
            <a href="{{ route('crm.index') }}"
               class="nav-item {{ request()->routeIs('crm.index') ? 'active' : '' }}"
               style="color:{{ request()->routeIs('crm.index') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
                <span x-show="sidebarOpen">CRM</span>
            </a>
            @endif

            {{-- Gestão da empresa — admin + supervisor, filtrado por módulos contratados --}}
            @if(auth()->user()->canManageCompany())
            @php
                $hasAnyGestao = $isSystemAdmin || count(array_intersect($companyModules, array_keys(\App\Models\Company::AVAILABLE_MODULES['gestao']))) > 0;
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

                <a href="{{ route('admin.zapi.index') }}"
                   class="nav-item {{ request()->routeIs('admin.zapi*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.zapi*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                    </svg>
                    <span x-show="sidebarOpen">Z-API / WhatsApp</span>
                </a>

                <a href="{{ route('admin.evolution.index') }}"
                   class="nav-item {{ request()->routeIs('admin.evolution*') ? 'active' : '' }}"
                   style="color:{{ request()->routeIs('admin.evolution*') ? '#b2ff00' : 'rgba(255,255,255,0.4)' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span x-show="sidebarOpen">Evolution API</span>
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
</div>

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

{{-- Lightbox global --}}
<div x-data="{ src: null, alt: '' }"
     @open-lightbox.window="src = $event.detail.src; alt = $event.detail.alt || ''"
     x-show="src"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="src = null"
     @keydown.escape.window="src = null"
     class="lightbox-overlay">
    <button @click.stop="src = null"
            style="position:absolute; top:16px; right:16px; color:rgba(255,255,255,0.5); background:rgba(255,255,255,0.06); border:none; cursor:pointer; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; transition:all 0.15s;"
            onmouseover="this.style.background='rgba(255,255,255,0.12)'; this.style.color='white'"
            onmouseout="this.style.background='rgba(255,255,255,0.06)'; this.style.color='rgba(255,255,255,0.5)'">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
    <img :src="src" :alt="alt"
         @click.stop
         style="max-height:90vh; max-width:90vw; object-fit:contain; border-radius:12px; box-shadow:0 24px 64px rgba(0,0,0,0.6); ring:1px solid rgba(255,255,255,0.08);">
</div>

@livewireScripts
@stack('scripts')
</body>
</html>
