<x-layouts.app>
    <x-slot:title>IA de Atendimento — {{ config('app.name') }}</x-slot:title>

    <div style="height:60px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; padding:0 24px; flex-shrink:0; background:rgba(11,15,28,0.5); backdrop-filter:blur(6px);">
        <h1 style="font-size:15px; font-weight:800; color:white; font-family:Syne,sans-serif; letter-spacing:-0.02em;">IA de Atendimento</h1>
    </div>

    <div style="flex:1; overflow-y:auto; padding:24px;" class="mobile-p-sm">
        <div class="max-w-4xl mx-auto space-y-8">

            {{-- Menu Chatbot --}}
            <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6">
                <div class="flex items-center gap-2 mb-5">
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Menu de Chatbot</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Direciona automaticamente o primeiro contato ao departamento correto.</p>
                    </div>
                </div>
                <livewire:admin.chatbot-menu-manager />
            </div>

            <livewire:admin.ai-bot-manager />

            {{-- Produtos & Serviços --}}
            <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6">
                <div class="flex items-center gap-2 mb-5">
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Catálogo de Produtos & Serviços</h3>
                        <p class="text-xs text-gray-500 mt-0.5">A IA usa este catálogo para responder perguntas sobre o que você oferece.</p>
                    </div>
                </div>
                <livewire:admin.ai-bot-products />
            </div>
        </div>
    </div>
</x-layouts.app>
