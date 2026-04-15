<x-layouts.app>
    <x-slot:title>Auditoria — {{ config('app.name') }}</x-slot:title>

    <div style="height:60px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; padding:0 24px; flex-shrink:0; background:rgba(11,15,28,0.5); backdrop-filter:blur(6px);">
        <h1 style="font-size:15px; font-weight:800; color:white; font-family:Syne,sans-serif; letter-spacing:-0.02em;">Administração</h1>
    </div>

    <div style="flex:1; overflow-y:auto; padding:24px;" class="mobile-p-sm">
        <livewire:admin.audit-log-viewer />
    </div>
</x-layouts.app>
