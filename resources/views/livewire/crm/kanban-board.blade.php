<div class="flex flex-col h-full"
     x-data="{
         dragging: null,
         dragOver: null,
         start(id) { this.dragging = id; },
         end()     { this.dragging = null; this.dragOver = null; },
         over(sid) { this.dragOver = sid; },
         drop(sid) {
             if (this.dragging !== null) $wire.moveCard(this.dragging, sid);
             this.dragging = null; this.dragOver = null;
         }
     }">

    @if($pipelines->isEmpty())
    {{-- Empty state --}}
    <div class="flex-1 flex flex-col items-center justify-center text-center p-10">
        <div class="w-16 h-16 bg-surface-700 rounded-2xl flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
            </svg>
        </div>
        <p class="text-white font-semibold mb-1">Nenhum pipeline criado</p>
        <p class="text-sm text-gray-500 mb-5">Crie pipelines e suas etapas no painel administrativo.</p>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.crm.index') }}"
           class="flex items-center gap-2 px-5 py-2.5 bg-accent hover:bg-accent-dark text-white text-sm font-medium rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Criar pipeline
        </a>
        @endif
    </div>

    @else

    {{-- ── Tabs de Pipelines + Filtro de Data ──────────────── --}}
    <div class="shrink-0 flex items-end gap-1 px-6 pt-4 border-b border-surface-700 overflow-x-auto">
        @foreach($pipelines as $pl)
        <button wire:click="selectPipeline({{ $pl->id }})"
                class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-t-xl border-b-2 whitespace-nowrap transition-all
                       {{ $selectedPipelineId === $pl->id
                          ? 'text-white border-current bg-surface-800'
                          : 'border-transparent text-gray-500 hover:text-gray-300 hover:bg-surface-800/50' }}"
                @if($selectedPipelineId === $pl->id)
                style="color: {{ $pl->color }}; border-color: {{ $pl->color }}"
                @endif>
            <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ $pl->color }}"></span>
            {{ $pl->name }}
            <span class="text-[11px] font-medium opacity-60">
                {{ \App\Models\CrmCard::where('pipeline_id', $pl->id)->count() }}
            </span>
        </button>
        @endforeach

        @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.crm.index') }}"
           class="flex items-center gap-1.5 px-3 py-2.5 mb-0.5 text-xs text-gray-600 hover:text-accent transition-colors whitespace-nowrap ml-2">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Gerenciar
        </a>
        @endif

        {{-- Filtro de data + Export --}}
        <div style="margin-left:auto; display:flex; align-items:center; gap:6px; padding-bottom:8px; flex-shrink:0;">
            <input wire:model.live="dateFrom" type="date"
                   style="padding:4px 8px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:6px; color:white; outline:none; color-scheme:dark;">
            <span style="font-size:10px; color:rgba(255,255,255,0.3);">até</span>
            <input wire:model.live="dateTo" type="date"
                   style="padding:4px 8px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:6px; color:white; outline:none; color-scheme:dark;">
            @if($dateFrom || $dateTo)
            <button wire:click="$set('dateFrom', ''); $wire.set('dateTo', '')"
                    style="padding:4px 8px; font-size:10px; color:#f87171; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); border-radius:6px; cursor:pointer;">
                Limpar
            </button>
            @endif
            <a href="{{ route('crm.export', ['pipeline_id' => $selectedPipelineId, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
               style="padding:4px 10px; font-size:10px; font-weight:600; color:#10b981; background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.2); border-radius:6px; text-decoration:none;"
               onmouseover="this.style.background='rgba(16,185,129,0.16)'" onmouseout="this.style.background='rgba(16,185,129,0.08)'">
                Excel
            </a>
        </div>
    </div>

    {{-- ── Board ─────────────────────────────────────────── --}}
    @if($stages->isEmpty())
    <div class="flex-1 flex flex-col items-center justify-center text-center p-10">
        <div class="w-12 h-12 bg-surface-700 rounded-xl flex items-center justify-center mb-3">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </div>
        <p class="text-white font-medium mb-1">Nenhuma etapa neste pipeline</p>
        <p class="text-sm text-gray-500 mb-4">Adicione etapas (colunas) para começar a usar o kanban.</p>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.crm.index') }}"
           class="text-sm text-accent hover:underline">Configurar etapas →</a>
        @endif
    </div>

    @else
    <div class="flex-1 overflow-x-auto overflow-y-hidden">
        <div class="flex gap-5 px-6 py-5 min-w-max">

            @foreach($stages as $stage)
            @php $stageCards = $cards[$stage->id] ?? collect(); @endphp

            <div class="flex flex-col w-64 shrink-0"
                 @dragover.prevent="over({{ $stage->id }})"
                 @dragleave.self="dragOver = null"
                 @drop.prevent="drop({{ $stage->id }})"
                 :class="dragOver === {{ $stage->id }} ? 'opacity-80' : ''">

                {{-- Cabeçalho colorido (estilo da imagem) --}}
                <div class="flex items-center justify-between px-4 py-2.5 rounded-xl mb-3 select-none"
                     style="background: {{ $stage->color }}">
                    <span class="text-sm font-bold text-white drop-shadow-sm">{{ $stage->name }}</span>
                    <span class="bg-black/25 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                        {{ $stageCards->count() }}
                    </span>
                </div>

                {{-- Lista de cards --}}
                <div class="overflow-y-auto space-y-2.5 pb-2 pr-0.5" style="max-height: calc(100vh - 280px)">

                    @forelse($stageCards as $card)
                    <div class="group bg-surface-800 rounded-xl cursor-pointer select-none
                                border border-surface-700 hover:border-surface-500
                                hover:shadow-lg hover:-translate-y-0.5 transition-all duration-150 overflow-hidden"
                         draggable="true"
                         @dragstart="start({{ $card->id }})"
                         @dragend="end()"
                         wire:click="openEditCard({{ $card->id }})"
                         :class="dragging === {{ $card->id }} ? 'opacity-25 scale-95' : ''">

                        {{-- Barra colorida no topo --}}
                        <div class="h-1 w-full" style="background: {{ $stage->color }}"></div>

                        <div class="p-3">
                            {{-- Título + prioridade --}}
                            <div class="flex items-start justify-between gap-2 mb-2.5">
                                <p class="text-sm text-white font-semibold leading-snug flex-1">{{ $card->title }}</p>
                                @if($card->priority)
                                <span class="shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded-md border {{ $card->priority_color }}">
                                    {{ $card->priority_label }}
                                </span>
                                @endif
                            </div>

                            {{-- Contato: nome + WhatsApp + e-mail --}}
                            @if($card->contact)
                            <div class="space-y-1 mb-2.5">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3 h-3 text-gray-600 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    <span class="text-[11px] text-gray-400">{{ $card->contact->phone }}</span>
                                </div>
                                @if($card->contact->email)
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3 h-3 text-gray-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-[11px] text-gray-400 truncate">{{ $card->contact->email }}</span>
                                </div>
                                @endif
                            </div>
                            @endif

                            {{-- Campos personalizados com valor --}}
                            @php
                                $filledValues = $card->fieldValues->filter(fn($v) => $v->value !== null && $v->value !== '');
                            @endphp
                            @if($filledValues->isNotEmpty())
                            <div class="space-y-1 mb-2.5">
                                @foreach($filledValues as $fv)
                                @php
                                    $isDatetime = in_array($fv->field?->type, ['datetime','date','time']);
                                    $isCurrency = $fv->field?->type === 'currency';
                                @endphp
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[10px] text-gray-600 truncate">{{ $fv->field?->name }}</span>
                                    <span class="text-[11px] font-medium shrink-0
                                                 {{ $isCurrency ? 'text-emerald-400' : ($isDatetime ? 'text-accent' : 'text-gray-300') }}">
                                        @if($isCurrency)
                                            R$ {{ number_format((float)$fv->value, 2, ',', '.') }}
                                        @elseif($isDatetime && $fv->value)
                                            {{ \Carbon\Carbon::parse($fv->value)->format($fv->field?->type === 'time' ? 'H:i' : ($fv->field?->type === 'date' ? 'd/m/Y' : 'd/m/Y H:i')) }}
                                        @else
                                            {{ $fv->value }}
                                        @endif
                                    </span>
                                </div>
                                @endforeach
                            </div>
                            @endif

                            {{-- Footer: responsável + data de criação --}}
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-surface-700/60">
                                <div class="flex items-center">
                                    @if($card->assignedTo)
                                    <img src="{{ $card->assignedTo->avatar_url ?? '' }}" alt="{{ $card->assignedTo->name }}"
                                         class="w-5 h-5 rounded-full object-cover ring-1 ring-surface-600"
                                         title="{{ $card->assignedTo->name }}">
                                    <span class="text-[10px] text-gray-500 ml-1.5 truncate">{{ $card->assignedTo->name }}</span>
                                    @else
                                    <div class="w-5 h-5 rounded-full bg-surface-700 border border-dashed border-surface-600"></div>
                                    @endif
                                </div>
                                <span class="text-[9px] text-gray-600 shrink-0" title="Criado em {{ $card->created_at->format('d/m/Y H:i') }}">
                                    {{ $card->created_at->format('d/m/Y H:i') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="h-14 rounded-xl border-2 border-dashed flex items-center justify-center text-xs transition-colors"
                         :class="dragOver === {{ $stage->id }}
                             ? 'border-accent/60 bg-accent/5 text-accent/50'
                             : 'border-surface-700 text-gray-700'">
                        Solte aqui
                    </div>
                    @endforelse

                </div>

                {{-- Botão adicionar --}}
                <button wire:click="openCreateCard({{ $stage->id }})"
                        class="mt-3 w-full flex items-center gap-2 px-3 py-2 rounded-xl text-gray-600
                               hover:text-white hover:bg-surface-700 transition-colors text-sm">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Adicionar card
                </button>

            </div>
            @endforeach

        </div>
    </div>
    @endif

    @endif

    {{-- ══════════════════════════════════════
         PAINEL LATERAL — CARD
    ══════════════════════════════════════ --}}
    @if($showCardPanel)
    <div class="fixed inset-0 bg-black/50 z-40" wire:click="closePanel"></div>

    <div class="fixed inset-y-0 right-0 w-full bg-surface-900 z-50 flex flex-col shadow-2xl"
         style="border-left: 1px solid #1f2937">

        {{-- Barra teal no topo --}}
        <div class="h-0.5 w-full shrink-0" style="background: linear-gradient(90deg, #b2ff00, #8fcc00, transparent)"></div>

        {{-- Header --}}
        <div class="shrink-0 px-6 py-4" style="border-bottom: 1px solid #1a2234; background: linear-gradient(180deg, #0d1117 0%, transparent 100%)">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                         style="background: linear-gradient(135deg, #b2ff00 0%, #8fcc00 100%); box-shadow: 0 0 12px rgba(178,255,0,0.35)">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-white leading-tight">
                            {{ $editingCardId ? 'Editar Card' : 'Novo Card' }}
                        </h2>
                        <p class="text-[11px] text-gray-500 leading-tight mt-0.5">
                            {{ $editingCardId ? 'Atualize as informações do lead' : 'Preencha os dados do novo lead' }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    @if($editingCardId)
                    <button wire:click="deleteCard({{ $editingCardId }})" wire:confirm="Remover este card?"
                            class="p-2 rounded-lg text-gray-600 hover:text-red-400 transition-colors"
                            style="background: transparent; border: 1px solid #1f2937"
                            onmouseover="this.style.background='rgba(239,68,68,0.08)'; this.style.borderColor='rgba(239,68,68,0.3)'"
                            onmouseout="this.style.background='transparent'; this.style.borderColor='#1f2937'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                    @endif
                    <button wire:click="closePanel"
                            class="p-2 rounded-lg text-gray-600 hover:text-white transition-colors"
                            style="background: transparent; border: 1px solid #1f2937"
                            onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='#374151'"
                            onmouseout="this.style.background='transparent'; this.style.borderColor='#1f2937'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Campos --}}
        <div class="flex-1 overflow-y-auto px-6 py-5">

            @php
            $inputClass = "w-full text-sm text-white placeholder-gray-600 focus:outline-none transition-all duration-200";
            $inputStyle = "background: rgba(255,255,255,0.04); border: 1px solid #1f2937; border-radius: 10px; padding: 9px 12px;";
            $inputFocus = "onfocus=\"this.style.borderColor='rgba(178,255,0,0.6)'; this.style.boxShadow='0 0 0 3px rgba(178,255,0,0.08)'\" onblur=\"this.style.borderColor='#1f2937'; this.style.boxShadow='none'\"";
            $labelClass = "block text-[11px] font-medium mb-1.5 tracking-wide";
            $labelStyle = "color: #6b7280; text-transform: uppercase; letter-spacing: 0.06em";
            @endphp

            {{-- ── SEÇÃO: Informações básicas ── --}}
            <div class="mb-5">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-0.5 h-3.5 rounded-full" style="background: #b2ff00"></div>
                    <span class="text-[10px] font-bold tracking-widest" style="color: #b2ff00; text-transform: uppercase">Informações</span>
                </div>

                <div class="grid grid-cols-2 gap-y-3" style="column-gap: 2rem">

                    <div class="col-span-2">
                        <label class="{{ $labelClass }}" style="{{ $labelStyle }}">Título *</label>
                        <input wire:model="card_title" type="text" placeholder="Nome do lead ou oportunidade"
                               class="{{ $inputClass }}" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                        @error('card_title') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}" style="{{ $labelStyle }}">Etapa</label>
                        <select wire:model="card_stage_id" class="{{ $inputClass }}" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                            @foreach($stages as $stage)
                            <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}" style="{{ $labelStyle }}">Responsável</label>
                        <select wire:model="card_assigned_to" class="{{ $inputClass }}" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                            <option value="">— Nenhum —</option>
                            @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-span-2">
                        <label class="{{ $labelClass }}" style="{{ $labelStyle }}">Contato</label>
                        <select wire:model="card_contact_id" class="{{ $inputClass }}" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                            <option value="">— Nenhum —</option>
                            @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}">{{ $contact->name ?: $contact->phone }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Telefone do contato (editável) --}}
                    @if($card_contact_id)
                    <div class="col-span-2">
                        <label class="{{ $labelClass }}" style="{{ $labelStyle }}">Telefone do contato</label>
                        <input type="text" wire:model="contact_phone"
                               class="{{ $inputClass }}" style="{{ $inputStyle }}" {!! $inputFocus !!}
                               placeholder="5511999999999">
                    </div>
                    @endif
                </div>
            </div>

            {{-- ── SEÇÃO: Prioridade ── --}}
            <div class="mb-5">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-0.5 h-3.5 rounded-full" style="background: #f59e0b"></div>
                    <span class="text-[10px] font-bold tracking-widest" style="color: #f59e0b; text-transform: uppercase">Prioridade</span>
                </div>
                <div class="flex gap-2 flex-wrap">
                    @foreach(['' => ['label' => 'Nenhuma', 'color' => '#6b7280', 'bg' => 'rgba(107,114,128,0.12)'],
                               'baixo'   => ['label' => 'Baixo',    'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.1)'],
                               'medio'   => ['label' => 'Médio',    'color' => '#eab308', 'bg' => 'rgba(234,179,8,0.1)'],
                               'alto'    => ['label' => 'Alto',     'color' => '#f97316', 'bg' => 'rgba(249,115,22,0.1)'],
                               'critico' => ['label' => 'Crítico',  'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.1)']]
                        as $val => $cfg)
                    <button type="button" wire:click="$set('card_priority', '{{ $val }}')"
                            class="px-4 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150"
                            style="{{ $card_priority === $val
                                ? "background: {$cfg['bg']}; border: 1px solid {$cfg['color']}; color: {$cfg['color']}; box-shadow: 0 0 8px {$cfg['bg']}"
                                : 'background: rgba(255,255,255,0.03); border: 1px solid #1f2937; color: #6b7280' }}">
                        {{ $cfg['label'] }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- ── SEÇÃO: Campos personalizados ── --}}
            @if($customFields->isNotEmpty())
            <div class="mb-5">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-0.5 h-3.5 rounded-full" style="background: #8b5cf6"></div>
                    <span class="text-[10px] font-bold tracking-widest" style="color: #8b5cf6; text-transform: uppercase">Campos personalizados</span>
                </div>
                <div class="grid grid-cols-2 gap-y-3" style="column-gap: 2rem">
                    @foreach($customFields as $field)
                    @php $fullWidth = in_array($field->type, ['textarea', 'datetime', 'url']); @endphp
                    <div class="{{ $fullWidth ? 'col-span-2' : '' }}">
                        <label class="{{ $labelClass }}" style="{{ $labelStyle }}">
                            {{ $field->name }}@if($field->is_required)<span style="color:#ef4444"> *</span>@endif
                        </label>

                        @if($field->type === 'textarea')
                            <textarea wire:model="customValues.{{ $field->id }}" rows="2"
                                      class="{{ $inputClass }} resize-none" style="{{ $inputStyle }}"
                                      {!! $inputFocus !!} placeholder="{{ $field->name }}..."></textarea>

                        @elseif($field->type === 'datetime')
                            <div class="grid grid-cols-2 gap-2">
                                <input type="date"
                                       x-data="{
                                           get val() { const v = $wire.customValues['{{ $field->id }}'] || ''; return v.split(' ')[0] || ''; },
                                           set val(d) { const t = ($wire.customValues['{{ $field->id }}'] || '').split(' ')[1] || '00:00'; $wire.set('customValues.{{ $field->id }}', d ? d+' '+t : ''); }
                                       }"
                                       :value="val" @change="val = $event.target.value"
                                       class="{{ $inputClass }} [color-scheme:dark]" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                                <input type="time"
                                       x-data="{
                                           get val() { const v = $wire.customValues['{{ $field->id }}'] || ''; return v.split(' ')[1] || ''; },
                                           set val(t) { const d = ($wire.customValues['{{ $field->id }}'] || '').split(' ')[0] || ''; $wire.set('customValues.{{ $field->id }}', d ? d+' '+t : ''); }
                                       }"
                                       :value="val" @change="val = $event.target.value"
                                       class="{{ $inputClass }} [color-scheme:dark]" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                            </div>

                        @elseif($field->type === 'date')
                            <input wire:model="customValues.{{ $field->id }}" type="date"
                                   class="{{ $inputClass }} [color-scheme:dark]" style="{{ $inputStyle }}" {!! $inputFocus !!}>

                        @elseif($field->type === 'time')
                            <input wire:model="customValues.{{ $field->id }}" type="time"
                                   class="{{ $inputClass }} [color-scheme:dark]" style="{{ $inputStyle }}" {!! $inputFocus !!}>

                        @elseif($field->type === 'number')
                            <input wire:model="customValues.{{ $field->id }}" type="number" step="any"
                                   class="{{ $inputClass }}" style="{{ $inputStyle }}" {!! $inputFocus !!} placeholder="0">

                        @elseif($field->type === 'currency')
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs" style="color:#6b7280">R$</span>
                                <input wire:model="customValues.{{ $field->id }}" type="number" step="0.01" min="0"
                                       class="{{ $inputClass }}" style="{{ $inputStyle }} padding-left: 2rem" {!! $inputFocus !!} placeholder="0,00">
                            </div>

                        @elseif($field->type === 'email')
                            <input wire:model="customValues.{{ $field->id }}" type="email"
                                   class="{{ $inputClass }}" style="{{ $inputStyle }}" {!! $inputFocus !!} placeholder="email@exemplo.com">

                        @elseif($field->type === 'phone')
                            <input wire:model="customValues.{{ $field->id }}" type="tel"
                                   class="{{ $inputClass }}" style="{{ $inputStyle }}" {!! $inputFocus !!} placeholder="(00) 00000-0000">

                        @elseif($field->type === 'url')
                            <input wire:model="customValues.{{ $field->id }}" type="url"
                                   class="{{ $inputClass }}" style="{{ $inputStyle }}" {!! $inputFocus !!} placeholder="https://...">

                        @else
                            <input wire:model="customValues.{{ $field->id }}" type="text"
                                   class="{{ $inputClass }}" style="{{ $inputStyle }}" {!! $inputFocus !!} placeholder="{{ $field->name }}">
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- ── SEÇÃO: Observações ── --}}
            <div class="mb-5">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-0.5 h-3.5 rounded-full" style="background: #64748b"></div>
                    <span class="text-[10px] font-bold tracking-widest" style="color: #64748b; text-transform: uppercase">Observações</span>
                </div>
                <textarea wire:model="card_description" rows="2" placeholder="Detalhes da oportunidade..."
                          class="{{ $inputClass }} resize-none" style="{{ $inputStyle }}" {!! $inputFocus !!}></textarea>
            </div>

            {{-- ── SEÇÃO: Notas & Histórico ── --}}
            @if($editingCardId)
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-0.5 h-3.5 rounded-full" style="background: #b2ff00"></div>
                    <span class="text-[10px] font-bold tracking-widest" style="color: #b2ff00; text-transform: uppercase">Notas & Histórico</span>
                </div>
                <div class="flex gap-2 mb-3">
                    <input wire:model="newNote" wire:keydown.enter="addNote" type="text"
                           placeholder="Adicionar nota... (Enter)"
                           class="{{ $inputClass }} flex-1" style="{{ $inputStyle }}" {!! $inputFocus !!}>
                    <button wire:click="addNote"
                            class="px-3 rounded-lg text-gray-400 hover:text-white transition-colors shrink-0"
                            style="background: rgba(178,255,0,0.1); border: 1px solid rgba(178,255,0,0.25)"
                            onmouseover="this.style.background='rgba(178,255,0,0.2)'"
                            onmouseout="this.style.background='rgba(178,255,0,0.1)'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#b2ff00">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </div>
                @if($activities->isNotEmpty())
                <div class="space-y-0 max-h-40 overflow-y-auto relative pl-3"
                     style="border-left: 1px solid #1f2937">
                    @foreach($activities as $act)
                    <div class="relative flex gap-3 pb-3 text-xs">
                        <div class="absolute -left-4 mt-1.5 w-2 h-2 rounded-full ring-2 shrink-0"
                             style="{{ $act->type === 'stage_change'
                                ? 'background:#b2ff00; ring-color:#0d1117'
                                : 'background:#374151; ring-color:#0d1117' }}"></div>
                        <div class="pt-0.5">
                            <p class="text-gray-300 leading-relaxed">{{ $act->content }}</p>
                            <p class="mt-0.5" style="color:#4b5563">{{ $act->user?->name ?? 'Sistema' }} · {{ $act->created_at->format('d/m H:i') }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif

        </div>

        {{-- Footer --}}
        <div class="shrink-0 px-6 py-4" style="border-top: 1px solid #1a2234">
            <button wire:click="saveCard"
                    class="w-full flex items-center justify-center gap-2 py-2.5 text-sm font-semibold text-white rounded-xl transition-all duration-200"
                    style="background: linear-gradient(135deg, #b2ff00 0%, #8fcc00 100%); box-shadow: 0 4px 14px rgba(178,255,0,0.25)"
                    onmouseover="this.style.boxShadow='0 4px 20px rgba(178,255,0,0.4)'; this.style.transform='translateY(-1px)'"
                    onmouseout="this.style.boxShadow='0 4px 14px rgba(178,255,0,0.25)'; this.style.transform='translateY(0)'">
                <span wire:loading.remove wire:target="saveCard">
                    {{ $editingCardId ? 'Salvar alterações' : 'Criar card' }}
                </span>
                <span wire:loading wire:target="saveCard" class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Salvando...
                </span>
            </button>
        </div>

    </div>
    @endif

</div>
