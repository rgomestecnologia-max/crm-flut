<x-layouts.app>
    <x-slot:title>Templates — {{ config('app.name') }}</x-slot:title>
    <div style="padding:24px;" x-data="{ tab: '{{ request('tab', 'campaign') }}' }">

        {{-- Tabs --}}
        <div style="display:flex; gap:10px; margin-bottom:24px; flex-wrap:wrap;">
            <button @click="tab='campaign'"
                    :style="tab==='campaign' ? 'background:linear-gradient(135deg,#b2ff00,#8fcc00); color:#111; border-color:transparent;' : 'background:rgba(255,255,255,0.03); color:rgba(255,255,255,0.5); border-color:rgba(255,255,255,0.08);'"
                    style="padding:9px 20px; font-size:13px; font-weight:600; border:1px solid rgba(255,255,255,0.08); border-radius:10px; cursor:pointer; transition:all 0.2s;">
                Templates de Campanha
            </button>
            <button @click="tab='meta'"
                    :style="tab==='meta' ? 'background:linear-gradient(135deg,#b2ff00,#8fcc00); color:#111; border-color:transparent;' : 'background:rgba(255,255,255,0.03); color:rgba(255,255,255,0.5); border-color:rgba(255,255,255,0.08);'"
                    style="padding:9px 20px; font-size:13px; font-weight:600; border:1px solid rgba(255,255,255,0.08); border-radius:10px; cursor:pointer; transition:all 0.2s;">
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
