<div class="space-y-4">

    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-white">Campos Personalizados</h3>
            <p class="text-xs text-gray-500 mt-0.5">Aparecem no formulário de todos os cards do CRM.</p>
        </div>
        @if(!$showForm)
        <button wire:click="openCreate"
                class="flex items-center gap-1.5 px-3 py-2 bg-accent hover:bg-accent-dark text-white text-xs font-medium rounded-lg transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo campo
        </button>
        @endif
    </div>

    {{-- Campos fixos (informativos) --}}
    <div class="bg-surface-700/30 border border-surface-700 rounded-xl px-4 py-3">
        <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-2.5">Campos padrão (fixos)</p>
        <div class="flex flex-wrap gap-2">
            @foreach(['Título','Etapa','Prioridade','Contato','WhatsApp','Observações'] as $f)
            <span class="text-xs px-2.5 py-1 rounded-lg bg-surface-700 text-gray-400 border border-surface-600">
                {{ $f }}
            </span>
            @endforeach
        </div>
    </div>

    {{-- Formulário --}}
    @if($showForm)
    <div class="bg-surface-800 border border-accent/30 rounded-xl p-4 space-y-3">
        <h4 class="text-xs font-semibold text-white">{{ $editingId ? 'Editar campo' : 'Novo campo personalizado' }}</h4>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-[11px] font-medium text-gray-400 mb-1">Nome do campo *</label>
                <input wire:model="field_name" type="text" placeholder="ex: Valor da Reserva"
                       class="w-full bg-surface-700 border border-surface-600 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-accent">
                @error('field_name') <p class="text-[10px] text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-400 mb-1">Tipo</label>
                <select wire:model="field_type"
                        class="w-full bg-surface-700 border border-surface-600 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-accent">
                    @foreach($types as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <label class="flex items-center gap-2 cursor-pointer w-fit">
            <input wire:model="field_required" type="checkbox"
                   class="w-3.5 h-3.5 rounded border-surface-600 bg-surface-700 text-accent focus:ring-accent">
            <span class="text-xs text-gray-400">Campo obrigatório</span>
        </label>

        <div class="flex gap-2 pt-1">
            <button wire:click="save"
                    class="px-4 py-2 bg-accent hover:bg-accent-dark text-white text-xs font-semibold rounded-lg transition-colors">
                {{ $editingId ? 'Salvar' : 'Criar campo' }}
            </button>
            <button wire:click="$set('showForm', false)"
                    class="px-4 py-2 text-xs text-gray-400 hover:text-white transition-colors">
                Cancelar
            </button>
        </div>
    </div>
    @endif

    {{-- Lista de campos personalizados --}}
    @if($fields->isEmpty())
    <div class="text-center py-8 text-gray-600 text-sm">
        Nenhum campo personalizado criado ainda.
    </div>
    @else
    <div class="space-y-2">
        @foreach($fields as $i => $field)
        <div class="flex items-center gap-3 bg-surface-800 border border-surface-700 rounded-xl px-4 py-3">

            {{-- Ordenação --}}
            <div class="flex flex-col gap-0.5">
                <button wire:click="moveUp({{ $field->id }})"
                        class="text-gray-700 hover:text-gray-400 transition-colors {{ $i === 0 ? 'invisible' : '' }}">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                    </svg>
                </button>
                <button wire:click="moveDown({{ $field->id }})"
                        class="text-gray-700 hover:text-gray-400 transition-colors {{ $i === $fields->count()-1 ? 'invisible' : '' }}">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-white">{{ $field->name }}</span>
                    @if($field->is_required)
                    <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-red-500/20 text-red-400">Obrigatório</span>
                    @endif
                </div>
                <div class="flex items-center gap-3 mt-0.5">
                    <span class="text-[11px] text-gray-500">{{ $field->type_label }}</span>
                    <code class="text-[10px] text-gray-600 font-mono">{{ $field->key }}</code>
                </div>
            </div>

            {{-- Ações --}}
            <div class="flex items-center gap-1">
                <button wire:click="openEdit({{ $field->id }})"
                        class="p-1.5 text-gray-600 hover:text-white hover:bg-surface-700 rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <button wire:click="delete({{ $field->id }})" wire:confirm="Excluir o campo "{{ $field->name }}"? Os valores preenchidos nos cards serão perdidos."
                        class="p-1.5 text-gray-600 hover:text-red-400 hover:bg-surface-700 rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
