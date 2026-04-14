<x-layouts.app>
    <x-slot:title>Pipelines CRM — {{ config('app.name') }}</x-slot:title>

    <div class="h-16 border-b border-surface-700 flex items-center justify-between px-6 shrink-0">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
            </svg>
            <h1 style="font-size:15px; font-weight:800; color:white; font-family:Syne,sans-serif; letter-spacing:-0.02em;">Pipelines CRM</h1>
        </div>
        <a href="{{ route('crm.index') }}"
           class="flex items-center gap-2 px-3 py-1.5 text-xs text-gray-400 hover:text-white bg-surface-700 hover:bg-surface-600 rounded-lg transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Voltar ao Kanban
        </a>
    </div>

    <div style="flex:1; overflow-y:auto; padding:24px;">
        <div class="max-w-3xl mx-auto space-y-6">

            <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6">
                <livewire:admin.crm-pipeline-manager />
            </div>

            <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6">
                <livewire:admin.crm-custom-field-manager />
            </div>

        </div>
    </div>
</x-layouts.app>
