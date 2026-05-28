<x-layouts.app>
    <x-slot:title>Flut Chat — {{ config('app.name') }}</x-slot:title>

    <div style="height:60px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; padding:0 24px; gap:16px; flex-shrink:0; background:rgba(11,15,28,0.5); backdrop-filter:blur(6px);">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="#b2ff00" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
        <h1 style="font-size:15px; font-weight:800; color:white; font-family:Syne,sans-serif; letter-spacing:-0.02em;">Flut Chat</h1>
    </div>

    <div style="flex:1; overflow-y:auto; padding:24px;" class="mobile-p-sm">
        <div class="max-w-5xl mx-auto">
            <livewire:admin.flut-chat-manager />
        </div>
    </div>
</x-layouts.app>
