<x-layouts.app>
    <x-slot:title>Dashboard — {{ config('app.name') }}</x-slot:title>

    @php
        $currentCompany = app(\App\Services\CurrentCompany::class)->model();
        $isManager      = auth()->user()->canManageCompany();
        $hasCrm         = $currentCompany?->hasModule('admin.crm');
    @endphp

    {{-- Header --}}
    <div style="height:60px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; padding:0 24px; flex-shrink:0; background:rgba(11,15,28,0.5); backdrop-filter:blur(6px);" class="mobile-p-sm">
        <div>
            <h1 style="font-size:15px; font-weight:800; color:white; font-family:'Syne',sans-serif; letter-spacing:-0.02em;">Dashboard</h1>
            <p style="font-size:10px; color:rgba(255,255,255,0.25); margin-top:1px;">Visão geral dos atendimentos</p>
        </div>
        <div style="margin-left:auto; display:flex; align-items:center; gap:10px;">
            @if($currentCompany)
            <div style="display:flex; align-items:center; gap:8px; background:{{ $currentCompany->color }}12; border:1px solid {{ $currentCompany->color }}40; border-radius:8px; padding:5px 12px 5px 5px;">
                <div style="width:22px; height:22px; border-radius:6px; display:flex; align-items:center; justify-content:center; background:{{ $currentCompany->color }}25; flex-shrink:0;">
                    <span style="font-size:10px; font-weight:800; color:{{ $currentCompany->color }};">{{ strtoupper(substr($currentCompany->name, 0, 1)) }}</span>
                </div>
                <span style="font-size:11px; font-weight:700; color:{{ $currentCompany->color }};">{{ $currentCompany->name }}</span>
            </div>
            @endif
            <div style="display:flex; align-items:center; gap:6px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:8px; padding:5px 12px;">
                <svg width="11" height="11" fill="none" stroke="rgba(255,255,255,0.3)" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span style="font-size:11px; color:rgba(255,255,255,0.3);">{{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>

    <div style="flex:1; overflow-y:auto; padding:24px;" class="mobile-p-sm">

        {{-- Row 1: Metric Cards --}}
        <livewire:dashboard.metrics-cards />

        {{-- Row 2: Conversas por Departamento + Atividade Recente (admin/supervisor) --}}
        @if($isManager)
        <div style="display:grid; grid-template-columns:3fr 2fr; gap:16px; margin-top:16px;" class="mobile-grid-1">
            <livewire:dashboard.conversations-by-department />
            <livewire:dashboard.recent-activity />
        </div>

        {{-- Row 3: Performance dos Agentes --}}
        <div style="margin-top:16px;">
            <livewire:dashboard.agent-performance />
        </div>
        @endif

        {{-- Row 4: Pipeline CRM (condicional) --}}
        @if($isManager && $hasCrm)
        <div style="margin-top:16px;">
            <livewire:dashboard.pipeline-summary />
        </div>
        @endif

        {{-- Quick Actions --}}
        <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-top:20px;" class="mobile-grid-1">
            <a href="{{ route('chat') }}" style="display:flex; align-items:center; gap:12px; padding:16px; background:linear-gradient(135deg, rgba(178,255,0,0.08), rgba(13,148,136,0.04)); border:1px solid rgba(178,255,0,0.2); border-radius:14px; text-decoration:none; transition:all 0.2s;"
               onmouseover="this.style.borderColor='rgba(178,255,0,0.4)'; this.style.transform='translateY(-1px)'"
               onmouseout="this.style.borderColor='rgba(178,255,0,0.2)'; this.style.transform='translateY(0)'">
                <div style="width:38px; height:38px; border-radius:10px; background:rgba(178,255,0,0.15); border:1px solid rgba(178,255,0,0.25); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="18" height="18" fill="none" stroke="#b2ff00" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                </div>
                <div>
                    <p style="font-size:13px; font-weight:600; color:white;">Atendimento</p>
                    <p style="font-size:11px; color:rgba(255,255,255,0.3);">Ir para conversas</p>
                </div>
            </a>

            <a href="{{ route('chat') }}?filter=queue" style="display:flex; align-items:center; gap:12px; padding:16px; background:linear-gradient(135deg, rgba(245,158,11,0.08), rgba(217,119,6,0.04)); border:1px solid rgba(245,158,11,0.2); border-radius:14px; text-decoration:none; transition:all 0.2s;"
               onmouseover="this.style.borderColor='rgba(245,158,11,0.4)'; this.style.transform='translateY(-1px)'"
               onmouseout="this.style.borderColor='rgba(245,158,11,0.2)'; this.style.transform='translateY(0)'">
                <div style="width:38px; height:38px; border-radius:10px; background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.25); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="18" height="18" fill="none" stroke="#f59e0b" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p style="font-size:13px; font-weight:600; color:white;">Ver Fila</p>
                    <p style="font-size:11px; color:rgba(255,255,255,0.3);">Conversas aguardando</p>
                </div>
            </a>

            @if($isManager)
            <a href="{{ route('admin.agents.index') }}" style="display:flex; align-items:center; gap:12px; padding:16px; background:linear-gradient(135deg, rgba(59,130,246,0.08), rgba(37,99,235,0.04)); border:1px solid rgba(59,130,246,0.2); border-radius:14px; text-decoration:none; transition:all 0.2s;"
               onmouseover="this.style.borderColor='rgba(59,130,246,0.4)'; this.style.transform='translateY(-1px)'"
               onmouseout="this.style.borderColor='rgba(59,130,246,0.2)'; this.style.transform='translateY(0)'">
                <div style="width:38px; height:38px; border-radius:10px; background:rgba(59,130,246,0.15); border:1px solid rgba(59,130,246,0.25); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="18" height="18" fill="none" stroke="#3b82f6" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <p style="font-size:13px; font-weight:600; color:white;">Gerenciar Agentes</p>
                    <p style="font-size:11px; color:rgba(255,255,255,0.3);">Criar e editar logins</p>
                </div>
            </a>
            @else
            <div></div>
            @endif
        </div>

    </div>
</x-layouts.app>
