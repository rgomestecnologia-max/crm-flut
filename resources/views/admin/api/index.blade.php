<x-layouts.app>
    <x-slot:title>Automação — {{ config('app.name') }}</x-slot:title>

    <div style="height:60px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; padding:0 24px; gap:16px; flex-shrink:0; background:rgba(11,15,28,0.5); backdrop-filter:blur(6px);">
        <svg class="w-5 h-5 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        <h1 style="font-size:15px; font-weight:800; color:white; font-family:Syne,sans-serif; letter-spacing:-0.02em;">Automação</h1>
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-6">
        <div class="max-w-3xl mx-auto space-y-6">

            {{-- ══════════════════════════════════════════ --}}
            {{-- AUTOMAÇÕES DE MENSAGEM                    --}}
            {{-- ══════════════════════════════════════════ --}}
            <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6">
                <livewire:admin.automation-manager />
            </div>

            {{-- ══════════════════════════════════════════ --}}
            {{-- API DE INTEGRAÇÃO (mantida)               --}}
            {{-- ══════════════════════════════════════════ --}}
            <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6">
                <div class="flex items-center gap-2 mb-5">
                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    <h2 class="text-base font-semibold text-white">API de Integração</h2>
                </div>
                <livewire:admin.api-token-manager />
            </div>

        </div>
    </div>
</x-layouts.app>
