<div class="space-y-4">

    {{-- Lista de Pipelines --}}
    @forelse($pipelines as $pipeline)
    <div class="border rounded-2xl overflow-hidden transition-all
                {{ $openPipelineId === $pipeline->id ? 'border-accent/30 bg-surface-800/60' : 'border-surface-700 bg-surface-800/30' }}">

        {{-- ── Cabeçalho do pipeline ─────────────────── --}}
        <div class="flex items-center gap-3 px-5 py-4">

            {{-- Cor --}}
            <div class="w-9 h-9 rounded-xl shrink-0 flex items-center justify-center"
                 style="background: {{ $pipeline->color }}25; border: 1.5px solid {{ $pipeline->color }}50">
                <span class="w-3 h-3 rounded-full" style="background: {{ $pipeline->color }}"></span>
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0 cursor-pointer" wire:click="toggleOpen({{ $pipeline->id }})">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-semibold text-white">{{ $pipeline->name }}</p>
                    <span class="{{ $pipeline->is_active ? 'bg-green-500/15 text-green-400' : 'bg-surface-700 text-gray-500' }}
                                 text-[10px] font-bold px-2 py-0.5 rounded-full">
                        {{ $pipeline->is_active ? 'ATIVO' : 'INATIVO' }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $pipeline->stages->count() }} etapa(s) · {{ $pipeline->cards_count }} card(s)
                    @if($pipeline->description) · {{ $pipeline->description }} @endif
                </p>
            </div>

            {{-- Seta expand --}}
            <button wire:click="toggleOpen({{ $pipeline->id }})"
                    class="p-1.5 rounded-lg text-gray-500 hover:text-gray-300 transition-colors shrink-0">
                <svg class="w-4 h-4 transition-transform {{ $openPipelineId === $pipeline->id ? 'rotate-180' : '' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Reordenar --}}
            <div class="flex flex-col gap-0.5 shrink-0">
                <button wire:click="movePipelineUp({{ $pipeline->id }})" class="p-0.5 text-gray-600 hover:text-gray-400 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
                    </svg>
                </button>
                <button wire:click="movePipelineDown({{ $pipeline->id }})" class="p-0.5 text-gray-600 hover:text-gray-400 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>

            {{-- Ações --}}
            <div class="flex items-center gap-1 shrink-0">
                <button wire:click="openEditPipeline({{ $pipeline->id }})" title="Editar"
                        class="p-1.5 rounded-lg text-gray-500 hover:text-gray-300 hover:bg-surface-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <button wire:click="togglePipeline({{ $pipeline->id }})"
                        title="{{ $pipeline->is_active ? 'Desativar' : 'Ativar' }}"
                        class="p-1.5 rounded-lg text-gray-500 hover:text-gray-300 hover:bg-surface-700 transition-colors">
                    @if($pipeline->is_active)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                    @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    @endif
                </button>
                <button wire:click="deletePipeline({{ $pipeline->id }})"
                        wire:confirm="Remover o pipeline '{{ $pipeline->name }}' e todas as suas etapas?"
                        title="Remover"
                        class="p-1.5 rounded-lg text-gray-500 hover:text-red-400 hover:bg-surface-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ── Etapas (expandido) ────────────────────── --}}
        @if($openPipelineId === $pipeline->id)
        <div class="border-t border-surface-700 px-5 py-4 space-y-3">

            {{-- Preview do kanban --}}
            @if($pipeline->stages->isNotEmpty())
            <div class="flex gap-2 overflow-x-auto pb-1">
                @foreach($pipeline->stages as $stage)
                <div class="flex items-center gap-2 bg-surface-700/60 rounded-xl px-3 py-2 shrink-0">
                    <div class="flex items-center justify-between gap-3 px-3 py-1.5 rounded-lg text-xs font-bold text-white whitespace-nowrap"
                         style="background: {{ $stage->color }}">
                        {{ $stage->name }}
                        <span class="bg-black/20 px-1.5 py-0.5 rounded-full text-[10px]">{{ $stage->cards_count }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <button wire:click="moveStageUp({{ $stage->id }})" class="p-0.5 text-gray-600 hover:text-gray-400 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/>
                            </svg>
                        </button>
                        <button wire:click="moveStageDown({{ $stage->id }})" class="p-0.5 text-gray-600 hover:text-gray-400 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>
                    <button wire:click="openEditStage({{ $stage->id }})"
                            class="p-0.5 text-gray-600 hover:text-gray-400 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button wire:click="deleteStage({{ $stage->id }})"
                            wire:confirm="Remover etapa '{{ $stage->name }}'?"
                            class="p-0.5 text-gray-600 hover:text-red-400 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Form nova/editar etapa --}}
            @if($showStageForm)
            <div class="bg-surface-700/40 border border-accent/20 rounded-xl p-4 space-y-3">
                <p class="text-xs font-semibold text-white">{{ $editingStageId ? 'Editar etapa' : 'Nova etapa' }}</p>

                <div class="flex gap-3 items-end flex-wrap">
                    <div class="flex-1 min-w-40">
                        <input wire:model.live="stage_name" type="text" placeholder="Nome da etapa (ex: Novo Lead)"
                               class="w-full bg-surface-700 border border-surface-600 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-accent">
                        @error('stage_name') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Cores rápidas --}}
                    <div class="flex items-center gap-1.5 flex-wrap">
                        @foreach($presetColors as $c)
                        <button type="button" wire:click="$set('stage_color','{{ $c }}')"
                                class="w-6 h-6 rounded-md transition-transform hover:scale-110
                                       {{ $stage_color === $c ? 'ring-2 ring-white ring-offset-1 ring-offset-surface-800 scale-110' : '' }}"
                                style="background:{{ $c }}"></button>
                        @endforeach
                        <label class="w-6 h-6 rounded-md cursor-pointer overflow-hidden hover:scale-110 transition-transform
                                      {{ !in_array($stage_color, $presetColors) ? 'ring-2 ring-white ring-offset-1 ring-offset-surface-800' : '' }}">
                            <input wire:model.live="stage_color" type="color" class="w-9 h-9 -ml-1.5 -mt-1.5 cursor-pointer">
                        </label>
                    </div>

                    {{-- Preview --}}
                    @if($stage_name)
                    <div class="px-3 py-1.5 rounded-lg text-xs font-bold text-white whitespace-nowrap"
                         style="background: {{ $stage_color }}">
                        {{ $stage_name }}
                    </div>
                    @endif
                </div>

                <div class="flex gap-2">
                    <button wire:click="saveStage"
                            class="px-4 py-1.5 bg-accent hover:bg-accent-dark text-white text-xs font-semibold rounded-lg transition-colors">
                        Salvar etapa
                    </button>
                    <button wire:click="$set('showStageForm', false)"
                            class="px-4 py-1.5 bg-surface-700 hover:bg-surface-600 text-gray-400 text-xs rounded-lg transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
            @endif

            {{-- Botão nova etapa --}}
            @if(!$showStageForm)
            <button wire:click="openCreateStage"
                    class="w-full flex items-center justify-center gap-2 py-2.5 border border-dashed border-surface-600
                           hover:border-accent/50 hover:text-accent text-gray-600 text-xs font-medium rounded-xl transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Adicionar etapa
            </button>
            @endif

        </div>
        @endif
    </div>
    @empty
    <div class="text-center py-10 text-gray-600 text-sm">Nenhum pipeline criado ainda.</div>
    @endforelse

    {{-- Form criar/editar Pipeline --}}
    @if($showPipelineForm)
    <div class="bg-surface-700/40 border border-accent/30 rounded-2xl p-5 space-y-4">
        <p class="text-sm font-semibold text-white">{{ $editingPipelineId ? 'Editar pipeline' : 'Novo pipeline' }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Nome *</label>
                <input wire:model.live="pipeline_name" type="text" placeholder="Ex: Pipeline de Vendas"
                       class="w-full bg-surface-700 border border-surface-600 rounded-lg px-3 py-2.5 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-accent">
                @error('pipeline_name') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Descrição (opcional)</label>
                <input wire:model="pipeline_desc" type="text" placeholder="Ex: Acompanhamento de leads comerciais"
                       class="w-full bg-surface-700 border border-surface-600 rounded-lg px-3 py-2.5 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-accent">
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-400 mb-2">Cor identificadora</label>
            <div class="flex flex-wrap gap-2 mb-3">
                @foreach($presetColors as $c)
                <button type="button" wire:click="$set('pipeline_color','{{ $c }}')"
                        class="w-7 h-7 rounded-lg transition-transform hover:scale-110 ring-offset-2 ring-offset-surface-800
                               {{ $pipeline_color === $c ? 'ring-2 ring-white scale-110' : '' }}"
                        style="background: {{ $c }}"></button>
                @endforeach
                <label class="w-7 h-7 rounded-lg cursor-pointer hover:scale-110 transition-transform overflow-hidden
                              ring-offset-2 ring-offset-surface-800 {{ !in_array($pipeline_color, $presetColors) ? 'ring-2 ring-white' : '' }}">
                    <input wire:model.live="pipeline_color" type="color" class="w-10 h-10 -ml-1.5 -mt-1.5 cursor-pointer">
                </label>
            </div>
            @if($pipeline_name)
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500">Preview da tab:</span>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold"
                     style="color: {{ $pipeline_color }}; border-bottom: 2px solid {{ $pipeline_color }}; background: {{ $pipeline_color }}15">
                    <span class="w-2 h-2 rounded-full" style="background: {{ $pipeline_color }}"></span>
                    {{ $pipeline_name }}
                </div>
            </div>
            @endif
        </div>

        <div class="flex gap-3 pt-1">
            <button wire:click="savePipeline"
                    class="px-5 py-2 bg-accent hover:bg-accent-dark text-white text-sm font-medium rounded-xl transition-colors">
                {{ $editingPipelineId ? 'Salvar' : 'Criar pipeline' }}
            </button>
            <button wire:click="$set('showPipelineForm', false)"
                    class="px-5 py-2 bg-surface-700 hover:bg-surface-600 text-gray-300 text-sm rounded-xl transition-colors">
                Cancelar
            </button>
        </div>
    </div>
    @endif

    {{-- Botão novo pipeline --}}
    @if(!$showPipelineForm)
    <button wire:click="openCreatePipeline"
            class="w-full flex items-center justify-center gap-2 px-4 py-4 border-2 border-dashed border-surface-600
                   hover:border-accent/50 hover:bg-accent/5 text-gray-500 hover:text-accent text-sm font-medium
                   rounded-2xl transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Criar novo pipeline
    </button>
    @endif

</div>
