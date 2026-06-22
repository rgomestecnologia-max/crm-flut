<x-layouts.app>
    <x-slot:title>WhatsApp API — {{ config('app.name') }}</x-slot:title>

    <div style="padding:24px;" x-data="{ tab: '{{ request('tab', 'meta') }}' }">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:20px;">
            <h1 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif;">WhatsApp API</h1>
            <span style="font-size:10px; color:rgba(255,255,255,0.25);">Configure as integrações de WhatsApp</span>
        </div>

        {{-- Tab content --}}
        <div x-show="tab==='evolution'" x-cloak>
            <livewire:admin.evolution-api-manager />
        </div>
        <div x-show="tab==='meta'" x-cloak>
            <livewire:admin.meta-whats-app-manager />
        </div>
        <div x-show="tab==='zapi'" x-cloak>
            <livewire:admin.zapi-config-form />
        </div>
    </div>
</x-layouts.app>
