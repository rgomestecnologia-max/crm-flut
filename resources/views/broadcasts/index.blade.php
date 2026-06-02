<x-layouts.app>
    <x-slot:title>Disparos — {{ config('app.name') }}</x-slot:title>
    <div class="flex-1 overflow-auto" x-data="{ broadcastTab: 'campaigns' }" style="padding:16px;">
        {{-- Tabs principais --}}
        <div style="display:flex; gap:8px; margin-bottom:16px; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:12px;">
            <button @click="broadcastTab='campaigns'" :style="broadcastTab==='campaigns' ? 'font-weight:700; border-color:rgba(178,255,0,0.3); background:rgba(178,255,0,0.1); color:#b2ff00;' : 'font-weight:400; border-color:rgba(255,255,255,0.08); background:transparent; color:rgba(255,255,255,0.4);'" style="padding:6px 16px; font-size:12px; border-radius:8px; cursor:pointer; border:1px solid;">Campanhas</button>
            <button @click="broadcastTab='funnels'" :style="broadcastTab==='funnels' ? 'font-weight:700; border-color:rgba(139,92,246,0.3); background:rgba(139,92,246,0.1); color:#a78bfa;' : 'font-weight:400; border-color:rgba(255,255,255,0.08); background:transparent; color:rgba(255,255,255,0.4);'" style="padding:6px 16px; font-size:12px; border-radius:8px; cursor:pointer; border:1px solid;">Funis de Email</button>
        </div>
        <div x-show="broadcastTab==='campaigns'"><livewire:broadcasts.campaign-manager /></div>
        <div x-show="broadcastTab==='funnels'"><livewire:broadcasts.email-funnel-manager /></div>
    </div>
</x-layouts.app>
