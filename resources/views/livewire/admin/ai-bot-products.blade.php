<div class="space-y-5">

    {{-- Lista de produtos/serviços --}}
    @if($products->isNotEmpty())
    <div class="space-y-4">
        @foreach($products->groupBy('type') as $groupType => $items)
        <div>
            <p class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold mb-2">
                {{ $groupType === 'produto' ? 'Produtos' : 'Serviços' }}
                <span class="ml-1 text-gray-600">({{ $items->count() }})</span>
            </p>
            <div class="space-y-2">
                @foreach($items as $item)
                <div class="flex items-center gap-3 bg-surface-700/50 border border-surface-700 rounded-xl px-4 py-3
                            {{ !$item->is_active ? 'opacity-50' : '' }}
                            {{ $editingId === $item->id ? 'border-accent/40' : '' }}">

                    {{-- Foto --}}
                    @if($item->photo_path)
                    <img src="{{ \App\Services\MediaStorage::url($item->photo_path) }}" alt="{{ $item->name }}"
                         style="width:36px; height:36px; object-fit:cover; border-radius:8px; flex-shrink:0;">
                    @else
                    <div style="width:36px; height:36px; background:rgba(31,41,55,0.8); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        @if($item->type === 'produto')
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        @else
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        @endif
                    </div>
                    @endif

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ $item->name }}</p>
                        @if($item->description)
                        <p class="text-xs text-gray-500 truncate mt-0.5">{{ $item->description }}</p>
                        @endif
                        @if($item->show_price && $item->price)
                        <p class="text-xs text-accent font-semibold mt-0.5">{{ $item->getPriceFormatted() }}</p>
                        @endif
                        @if($item->document_path)
                        <p class="text-[10px] text-blue-400 mt-0.5 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Documento anexado ({{ number_format(strlen($item->document_content ?? '') / 1024, 1) }}KB texto)
                        </p>
                        @endif
                    </div>

                    {{-- Badge ativo --}}
                    <span class="shrink-0 text-[10px] font-semibold px-2 py-0.5 rounded-full
                                 {{ $item->is_active ? 'bg-green-500/20 text-green-400' : 'bg-surface-700 text-gray-500' }}">
                        {{ $item->is_active ? 'ATIVO' : 'INATIVO' }}
                    </span>

                    {{-- Ações --}}
                    <div class="flex items-center gap-1 shrink-0">
                        <button wire:click="toggleActive({{ $item->id }})"
                                title="{{ $item->is_active ? 'Desativar' : 'Ativar' }}"
                                class="p-1.5 rounded-lg text-gray-500 hover:text-gray-300 hover:bg-surface-700 transition-colors">
                            @if($item->is_active)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            @endif
                        </button>
                        <button wire:click="openEdit({{ $item->id }})"
                                title="Editar"
                                class="p-1.5 rounded-lg text-gray-500 hover:text-gray-300 hover:bg-surface-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button wire:click="delete({{ $item->id }})"
                                wire:confirm="Remover '{{ $item->name }}'?"
                                title="Remover"
                                class="p-1.5 rounded-lg text-gray-500 hover:text-red-400 hover:bg-surface-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Form de adição/edição --}}
    @if($showForm)
    <div class="bg-surface-700/40 border border-accent/30 rounded-2xl p-5 space-y-4">
        <h4 class="text-sm font-semibold text-white flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
            {{ $editingId ? 'Editar item' : 'Novo item' }}
        </h4>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Tipo --}}
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Tipo *</label>
                <div class="flex gap-2">
                    <button type="button"
                            wire:click="$set('type', 'produto')"
                            class="flex-1 py-2 text-sm font-medium rounded-lg border transition-colors
                                   {{ $type === 'produto' ? 'bg-accent/20 border-accent text-accent' : 'bg-surface-700 border-surface-600 text-gray-400 hover:border-gray-500' }}">
                        Produto
                    </button>
                    <button type="button"
                            wire:click="$set('type', 'servico')"
                            class="flex-1 py-2 text-sm font-medium rounded-lg border transition-colors
                                   {{ $type === 'servico' ? 'bg-accent/20 border-accent text-accent' : 'bg-surface-700 border-surface-600 text-gray-400 hover:border-gray-500' }}">
                        Serviço
                    </button>
                </div>
                @error('type') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Nome --}}
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Nome *</label>
                <input wire:model="name"
                       type="text"
                       placeholder="{{ $type === 'produto' ? 'Ex: Smartphone Samsung Galaxy S25' : 'Ex: Consultoria de TI' }}"
                       class="w-full bg-surface-700 border border-surface-600 rounded-lg px-3 py-2.5 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-accent">
                @error('name') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Descrição --}}
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-400 mb-1.5">
                    Descrição
                    <span class="text-gray-600 font-normal">
                        {{ $type === 'produto' ? '(características, especificações)' : '(o que inclui, prazo, benefícios)' }}
                    </span>
                </label>
                <textarea wire:model="description"
                          rows="2"
                          placeholder="{{ $type === 'produto' ? 'Ex: Tela AMOLED 6.2\", 256GB, câmera 200MP, resistente à água IP68' : 'Ex: 10 horas mensais de suporte, SLA 4h, relatórios semanais' }}"
                          class="w-full bg-surface-700 border border-surface-600 rounded-lg px-3 py-2.5 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-accent resize-none"></textarea>
                @error('description') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Preço --}}
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <label class="text-xs font-medium text-gray-400">Exibir preço</label>
                    <button type="button"
                            wire:click="$set('show_price', {{ $show_price ? 'false' : 'true' }})"
                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors
                                   {{ $show_price ? 'bg-accent' : 'bg-surface-600' }}">
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-sm transition-transform
                                     {{ $show_price ? 'translate-x-4' : 'translate-x-1' }}"></span>
                    </button>
                </div>
                @if($show_price)
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-400 shrink-0">R$</span>
                    <input wire:model="price"
                           type="number"
                           step="0.01"
                           min="0"
                           placeholder="0,00"
                           class="w-full bg-surface-700 border border-surface-600 rounded-lg px-3 py-2.5 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-accent">
                </div>
                @error('price') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                @endif
            </div>

            {{-- Foto --}}
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">
                    Foto <span class="text-gray-600 font-normal">(opcional, máx. 4MB)</span>
                </label>

                @if($existingPhoto && !$photo)
                <div class="flex items-center gap-3 mb-2">
                    <img src="{{ \App\Services\MediaStorage::url($existingPhoto) }}" alt="Foto atual"
                         class="w-14 h-14 object-cover rounded-lg border border-surface-600">
                    <button type="button" wire:click="$set('existingPhoto', null)"
                            class="text-xs text-red-400 hover:text-red-300">Remover</button>
                </div>
                @endif

                @if($photo)
                <div class="mb-2">
                    <img src="{{ $photo->temporaryUrl() }}" alt="Nova foto"
                         class="w-14 h-14 object-cover rounded-lg border border-accent/50">
                </div>
                @endif

                <label class="flex items-center gap-2 cursor-pointer px-3 py-2 bg-surface-700 border border-surface-600 border-dashed rounded-lg hover:border-accent/50 transition-colors w-fit">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-xs text-gray-500">{{ $photo ? 'Trocar foto' : 'Escolher foto' }}</span>
                    <input wire:model="photo" type="file" accept="image/*" class="sr-only">
                </label>
                @error('photo') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Arquivo PDF (para a IA enviar ao cliente) --}}
            <div class="md:col-span-2">
                <p class="text-xs text-gray-400 mb-2">
                    Arquivo PDF <span class="text-gray-600 font-normal">(catálogo, ficha técnica — a IA envia ao cliente quando solicitado)</span>
                </p>

                @if($existingDocument)
                <div class="flex items-center gap-3 mb-2">
                    <div style="display:flex; align-items:center; gap:6px; padding:6px 12px; background:rgba(59,130,246,0.08); border:1px solid rgba(59,130,246,0.2); border-radius:8px;">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="text-xs text-blue-400">PDF anexado</span>
                    </div>
                    <button wire:click="removeDocument" type="button" class="text-xs text-red-400 hover:text-red-300">Remover</button>
                </div>
                @endif

                <input wire:model="document" type="file" accept=".pdf"
                       style="font-size:12px; color:rgba(255,255,255,0.5); padding:8px; background:rgba(255,255,255,0.04); border:1px dashed rgba(59,130,246,0.3); border-radius:8px; width:100%; cursor:pointer;">
                @if($document)
                <p style="font-size:11px; color:#4ade80; margin-top:4px;">{{ $document->getClientOriginalName() }} — pronto para salvar</p>
                @endif
                @error('document') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            {{-- Base de conhecimento (texto) --}}
            <div class="md:col-span-2">
                <p class="text-xs text-gray-400 mb-2">
                    Base de conhecimento <span class="text-gray-600 font-normal">(cole o conteúdo do PDF ou informações extras que a IA deve usar como referência)</span>
                </p>
                <textarea wire:model="documentText" rows="6"
                          placeholder="Cole aqui o conteúdo do catálogo, ficha técnica, ou qualquer informação que a IA deve conhecer..."
                          class="w-full bg-surface-800 border border-surface-600 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500/50 focus:ring-1 focus:ring-blue-500/30 resize-none font-mono"></textarea>
                @error('documentText') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Ativo --}}
            <div class="md:col-span-2 flex items-center gap-3">
                <button type="button"
                        wire:click="$set('is_active', {{ $is_active ? 'false' : 'true' }})"
                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors
                               {{ $is_active ? 'bg-accent' : 'bg-surface-600' }}">
                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-sm transition-transform
                                 {{ $is_active ? 'translate-x-4' : 'translate-x-1' }}"></span>
                </button>
                <span class="text-xs text-gray-400">
                    {{ $is_active ? 'Ativo — IA menciona este item nas conversas' : 'Inativo — IA ignora este item' }}
                </span>
            </div>

        </div>

        {{-- Botões do form --}}
        <div class="flex items-center gap-3 pt-1 border-t border-surface-700">
            <button wire:click="save"
                    class="flex items-center gap-2 px-5 py-2 bg-accent hover:bg-accent-dark text-white text-sm font-medium rounded-xl transition-colors">
                <span wire:loading.remove wire:target="save">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
                <span wire:loading wire:target="save">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </span>
                <span wire:loading.remove wire:target="save">{{ $editingId ? 'Salvar alterações' : 'Salvar' }}</span>
                <span wire:loading wire:target="save">{{ $document ? 'Extraindo texto e salvando...' : 'Salvando...' }}</span>
            </button>
            <button wire:click="cancel"
                    class="px-5 py-2 bg-surface-700 hover:bg-surface-600 text-gray-300 text-sm font-medium rounded-xl transition-colors">
                Cancelar
            </button>
        </div>
    </div>
    @endif

    {{-- Botão adicionar (sempre visível quando form fechado) --}}
    @if(!$showForm)
    <button wire:click="openCreate"
            class="w-full flex items-center justify-center gap-2 px-4 py-3 border-2 border-dashed border-surface-600
                   hover:border-accent/50 hover:bg-accent/5 text-gray-500 hover:text-accent text-sm font-medium
                   rounded-xl transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Adicionar produto ou serviço
    </button>
    @endif

</div>
