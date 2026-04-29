<x-layouts.app>
    <x-slot:title>Leads — {{ config('app.name') }}</x-slot:title>
    <div style="display:flex; flex-direction:column; height:100%; overflow:hidden;">
        <livewire:leads.lead-manager />
    </div>
</x-layouts.app>
