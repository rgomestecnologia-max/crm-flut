<x-layouts.app>
    <x-slot:title>Leads — {{ config('app.name') }}</x-slot:title>
    <div class="flex-1 overflow-auto">
        <livewire:leads.lead-manager />
    </div>
</x-layouts.app>
