<x-layouts.app>
    <x-slot:title>IA de Atendimento — {{ config('app.name') }}</x-slot:title>

    <div style="height:60px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; padding:0 24px; gap:16px; flex-shrink:0; background:rgba(11,15,28,0.5); backdrop-filter:blur(6px);">
        <svg class="w-5 h-5 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
        </svg>
        <h1 style="font-size:15px; font-weight:800; color:white; font-family:Syne,sans-serif; letter-spacing:-0.02em;">IA de Atendimento</h1>
    </div>

    <div style="flex:1; overflow-y:auto; padding:24px;">
        <div class="max-w-4xl mx-auto space-y-8">

            {{-- Aviso de exclusividade --}}
            @php $chatbotActive = \App\Models\ChatbotMenuConfig::current()?->is_active; @endphp
            @if($chatbotActive)
            <div class="flex items-start gap-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl px-4 py-3 text-sm text-yellow-400">
                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
                </svg>
                <span>
                    O <strong>Chatbot</strong> está ativo no momento.
                    Ao ativar a IA, o Chatbot será desativado automaticamente.
                    <a href="{{ route('admin.chatbot.index') }}" class="underline hover:text-yellow-300 ml-1">Gerenciar Chatbot →</a>
                </span>
            </div>
            @endif

            {{-- IA Manager --}}
            <livewire:admin.ai-bot-manager />

            {{-- Catálogo de Produtos & Serviços --}}
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
