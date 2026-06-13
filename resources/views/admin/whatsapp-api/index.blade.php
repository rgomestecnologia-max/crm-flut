<x-layouts.app>
    <x-slot:title>WhatsApp API — {{ config('app.name') }}</x-slot:title>

    <div style="padding:24px;" x-data="{ tab: '{{ request('tab', 'evolution') }}' }">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:20px;">
            <h1 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif;">WhatsApp API</h1>
            <span style="font-size:10px; color:rgba(255,255,255,0.25);">Configure as integrações de WhatsApp</span>
        </div>

        {{-- Tabs --}}
        <div style="display:flex; gap:10px; margin-bottom:24px; flex-wrap:wrap;">
            <button @click="tab='evolution'"
                    :style="tab==='evolution' ? 'background:linear-gradient(135deg,#b2ff00,#8fcc00); color:#111; border-color:transparent;' : 'background:rgba(255,255,255,0.03); color:rgba(255,255,255,0.5); border-color:rgba(255,255,255,0.08);'"
                    style="display:flex; align-items:center; gap:8px; padding:9px 20px; font-size:13px; font-weight:600; border:1px solid rgba(255,255,255,0.08); border-radius:10px; cursor:pointer; transition:all 0.2s;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Evolution API
            </button>
            <button @click="tab='meta'"
                    :style="tab==='meta' ? 'background:linear-gradient(135deg,#b2ff00,#8fcc00); color:#111; border-color:transparent;' : 'background:rgba(255,255,255,0.03); color:rgba(255,255,255,0.5); border-color:rgba(255,255,255,0.08);'"
                    style="display:flex; align-items:center; gap:8px; padding:9px 20px; font-size:13px; font-weight:600; border:1px solid rgba(255,255,255,0.08); border-radius:10px; cursor:pointer; transition:all 0.2s;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                Meta WhatsApp
            </button>
            <button @click="tab='zapi'"
                    :style="tab==='zapi' ? 'background:linear-gradient(135deg,#b2ff00,#8fcc00); color:#111; border-color:transparent;' : 'background:rgba(255,255,255,0.03); color:rgba(255,255,255,0.5); border-color:rgba(255,255,255,0.08);'"
                    style="display:flex; align-items:center; gap:8px; padding:9px 20px; font-size:13px; font-weight:600; border:1px solid rgba(255,255,255,0.08); border-radius:10px; cursor:pointer; transition:all 0.2s;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Z-API
            </button>
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
