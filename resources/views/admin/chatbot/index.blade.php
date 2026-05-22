<x-layouts.app>
    <x-slot:title>Chatbot — {{ config('app.name') }}</x-slot:title>

    <div style="height:60px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; padding:0 24px; gap:16px; flex-shrink:0; background:rgba(11,15,28,0.5); backdrop-filter:blur(6px);">
        <svg class="w-5 h-5 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
        </svg>
        <h1 style="font-size:15px; font-weight:800; color:white; font-family:Syne,sans-serif; letter-spacing:-0.02em;">Chatbot — Menu de Atendimento</h1>
    </div>

    <div style="flex:1; overflow-y:auto; padding:24px;" class="mobile-p-sm">
        <div class="max-w-4xl mx-auto space-y-6">


            <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6">
                <livewire:admin.chatbot-menu-manager />
            </div>

        </div>
    </div>
</x-layouts.app>
