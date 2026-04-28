<x-layouts.app>
    <x-slot:title>Disparos — {{ config('app.name') }}</x-slot:title>
    <div class="flex-1 overflow-auto">
        <livewire:broadcasts.campaign-manager />
    </div>
</x-layouts.app>
