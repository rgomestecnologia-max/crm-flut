<x-layouts.app>
    <x-slot:title>Templates — {{ config('app.name') }}</x-slot:title>
    <div style="padding:24px;" x-data="{ tab: '{{ request('tab', 'campaign') }}' }">

        {{-- Tabs --}}
        <div style="display:flex; gap:4px; margin-bottom:24px; border-bottom:1px solid rgba(255,255,255,0.06);">
            <button @click="tab='campaign'" :style="tab==='campaign' ? 'color:#b2ff00; border-bottom:2px solid #b2ff00; background:rgba(178,255,0,0.05);' : 'color:rgba(255,255,255,0.4); border-bottom:2px solid transparent;'"
                    style="padding:10px 20px; font-size:13px; font-weight:600; border:none; cursor:pointer; background:transparent; border-radius:8px 8px 0 0;">
                Templates de Campanha
            </button>
            <button @click="tab='meta'" :style="tab==='meta' ? 'color:#1877f2; border-bottom:2px solid #1877f2; background:rgba(24,119,242,0.05);' : 'color:rgba(255,255,255,0.4); border-bottom:2px solid transparent;'"
                    style="padding:10px 20px; font-size:13px; font-weight:600; border:none; cursor:pointer; background:transparent; border-radius:8px 8px 0 0;">
                Templates Meta (Oficial)
            </button>
        </div>

        <div x-show="tab==='campaign'" x-cloak>
            <livewire:broadcasts.template-builder />
        </div>
        <div x-show="tab==='meta'" x-cloak>
            <livewire:admin.template-manager />
        </div>
    </div>
</x-layouts.app>
