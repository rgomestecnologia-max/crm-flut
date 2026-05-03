<div>
    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- CABEÇALHO DA SEÇÃO                                      --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-base font-semibold text-white">Automações de Mensagem</h2>
            <p class="text-xs text-gray-500 mt-0.5">Envie mensagens automáticas no WhatsApp quando um lead entrar em um Pipeline via API.</p>
        </div>
        <button wire:click="openCreate"
                class="flex items-center gap-2 px-3 py-2 bg-accent hover:bg-accent-dark text-white text-xs font-semibold rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nova Automação
        </button>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- FORMULÁRIO CREATE / EDIT                               --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    @if($showForm)
    <div class="bg-surface-700/50 border border-surface-600 rounded-xl p-5 mb-6">
        <h3 class="text-sm font-semibold text-white mb-4">
            {{ $editingId ? 'Editar Automação' : 'Nova Automação' }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            {{-- Nome --}}
            <div>
                <label class="block text-xs text-gray-400 mb-1">Nome da automação <span class="text-red-400">*</span></label>
                <input wire:model="name" type="text" placeholder="Ex: Boas-vindas - Estacionamento"
                       class="w-full bg-surface-800 border border-surface-600 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Pipeline gatilho --}}
            <div>
                <label class="block text-xs text-gray-400 mb-1">Pipeline gatilho</label>
                <select wire:model="pipeline_id"
                        class="w-full bg-surface-800 border border-surface-600 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                    <option value="">Qualquer pipeline</option>
                    @foreach($pipelines as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
                <p class="text-[10px] text-gray-600 mt-1">Disparar apenas quando o lead entrar neste pipeline.</p>
            </div>
        </div>

        {{-- Variáveis disponíveis --}}
        <div class="mb-3">
            <p class="text-xs text-gray-400 mb-2">Variáveis disponíveis — clique para inserir no texto:</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach(['{nome}', '{telefone}', '{email}', '{pipeline}', '{etapa}', '{data}'] as $var)
                    <button type="button" wire:click="insertVariable('{{ $var }}')"
                            class="px-2 py-1 bg-accent/10 hover:bg-accent/20 text-accent text-[11px] font-mono rounded border border-accent/20 transition-colors">
                        {{ $var }}
                    </button>
                @endforeach
                @foreach($customFields as $cf)
                    <button type="button" wire:click="insertVariable('{!! '{' . $cf->key . '}' !!}')"
                            class="px-2 py-1 bg-purple-500/10 hover:bg-purple-500/20 text-purple-400 text-[11px] font-mono rounded border border-purple-500/20 transition-colors">
                        {!! '{' . $cf->key . '}' !!}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Template da mensagem (oculto quando ai_first_response ativo) --}}
        @if(!$ai_first_response)
            @if($isMeta && $metaTemplates->isNotEmpty())
            {{-- Seletor de template Meta --}}
            <div class="mb-4">
                <label class="block text-xs text-gray-400 mb-1">Template Meta WhatsApp <span class="text-red-400">*</span></label>
                <select wire:model="meta_template_name"
                        class="w-full bg-surface-800 border border-surface-600 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                    <option value="">Selecione um template...</option>
                    @foreach($metaTemplates as $tpl)
                        <option value="{{ $tpl->name }}">{{ $tpl->name }} ({{ $tpl->language }}) — {{ \Illuminate\Support\Str::limit($tpl->body_text, 60) }}</option>
                    @endforeach
                </select>
                <p class="text-[10px] text-gray-500 mt-1">Templates aprovados pela Meta para mensagens fora da janela de 24h. Sincronize em Meta WhatsApp > Templates.</p>
            </div>
            @endif

            <div class="mb-4">
                <label class="block text-xs text-gray-400 mb-1">
                    Mensagem automática
                    @if(!$isMeta) <span class="text-red-400">*</span> @else <span class="text-gray-500">(usada dentro da janela de 24h)</span> @endif
                </label>
                <textarea wire:model="message_template" rows="8"
                          placeholder="Olá {nome}! 👋&#10;Percebemos que você solicitou uma cotação. Podemos ajudar?&#10;&#10;Entre em contato conosco!"
                          class="w-full bg-surface-800 border border-surface-600 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent resize-none font-mono"></textarea>
                @error('message_template') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        @else
        <div class="mb-4 p-3 bg-blue-500/5 border border-blue-500/20 rounded-lg">
            <p class="text-xs text-blue-400">A IA vai responder diretamente à dúvida do lead. Nenhuma mensagem fixa será enviada.</p>
        </div>
        @endif

        {{-- Toggles: Ativo + IA --}}
        <div class="flex flex-col gap-3 mb-5">
            <div class="flex items-center gap-3">
                <button type="button" wire:click="$toggle('is_active')"
                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors
                               {{ $is_active ? 'bg-accent' : 'bg-surface-600' }}">
                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                 {{ $is_active ? 'translate-x-4' : 'translate-x-1' }}"></span>
                </button>
                <span class="text-sm text-gray-300">{{ $is_active ? 'Automação ativa' : 'Automação pausada' }}</span>
            </div>

            <div class="flex items-start gap-3 p-3 bg-purple-500/5 border border-purple-500/20 rounded-lg">
                <button type="button" wire:click="$toggle('enable_ai_on_reply')"
                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors shrink-0 mt-0.5
                               {{ $enable_ai_on_reply ? 'bg-purple-500' : 'bg-surface-600' }}">
                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                 {{ $enable_ai_on_reply ? 'translate-x-4' : 'translate-x-1' }}"></span>
                </button>
                <div>
                    <p class="text-sm text-gray-200 font-medium">Ativar IA de Atendimento na resposta do Cliente</p>
                    <p class="text-xs text-gray-500 mt-0.5">Quando o cliente responder à mensagem automática, a IA assume o atendimento automaticamente.</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-3 bg-blue-500/5 border border-blue-500/20 rounded-lg">
                <button type="button" wire:click="$toggle('ai_first_response')"
                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors shrink-0 mt-0.5
                               {{ $ai_first_response ? 'bg-blue-500' : 'bg-surface-600' }}">
                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                 {{ $ai_first_response ? 'translate-x-4' : 'translate-x-1' }}"></span>
                </button>
                <div>
                    <p class="text-sm text-gray-200 font-medium">IA responde direto à dúvida do lead</p>
                    <p class="text-xs text-gray-500 mt-0.5">A IA responde automaticamente à mensagem/dúvida que veio do site, sem enviar mensagem fixa. A mensagem template abaixo não será usada.</p>
                </div>
            </div>
        </div>

        <div class="flex gap-2">
            <button wire:click="save"
                    class="px-4 py-2 bg-accent hover:bg-accent-dark text-white text-sm font-semibold rounded-lg transition-colors">
                {{ $editingId ? 'Salvar alterações' : 'Criar automação' }}
            </button>
            <button wire:click="$set('showForm', false)"
                    class="px-4 py-2 bg-surface-700 hover:bg-surface-600 text-gray-300 text-sm rounded-lg transition-colors">
                Cancelar
            </button>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- LISTA DE AUTOMAÇÕES                                    --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    @if($automations->isEmpty())
        <div class="flex flex-col items-center justify-center py-12 text-gray-600">
            <svg class="w-10 h-10 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <p class="text-sm">Nenhuma automação criada ainda.</p>
            <p class="text-xs mt-1">Clique em "Nova Automação" para começar.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($automations as $auto)
            <div class="border border-surface-600 rounded-xl p-4 bg-surface-800/50">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            {{-- Status indicator --}}
                            <span class="w-2 h-2 rounded-full shrink-0 {{ $auto->is_active ? 'bg-green-400' : 'bg-gray-500' }}"></span>
                            <span class="text-sm font-semibold text-white truncate">{{ $auto->name }}</span>
                            @if(!$auto->is_active)
                                <span class="text-[10px] px-1.5 py-0.5 bg-gray-500/20 text-gray-400 rounded">Pausada</span>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 mb-2">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Lead criado via API
                            </span>
                            @if($auto->pipeline)
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                                    </svg>
                                    Pipeline: <span class="text-accent">{{ $auto->pipeline->name }}</span>
                                </span>
                            @else
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                                    </svg>
                                    Qualquer pipeline
                                </span>
                            @endif
                            @if($auto->enable_ai_on_reply)
                                <span class="flex items-center gap-1 px-1.5 py-0.5 bg-purple-500/15 text-purple-400 border border-purple-500/25 rounded text-[10px] font-semibold">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                    IA na resposta
                                </span>
                            @endif
                        </div>

                        {{-- Preview da mensagem --}}
                        <div class="bg-surface-900 rounded-lg p-3 text-xs text-gray-400 font-mono whitespace-pre-wrap line-clamp-3 max-h-16 overflow-hidden">{{ $auto->message_template }}</div>
                    </div>

                    {{-- Ações --}}
                    <div class="flex items-center gap-1 shrink-0">
                        <button wire:click="toggleActive({{ $auto->id }})"
                                title="{{ $auto->is_active ? 'Pausar' : 'Ativar' }}"
                                class="p-2 rounded-lg text-gray-500 hover:text-yellow-400 hover:bg-yellow-400/10 transition-colors">
                            @if($auto->is_active)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                        </button>
                        <button wire:click="edit({{ $auto->id }})"
                                class="p-2 rounded-lg text-gray-500 hover:text-accent hover:bg-accent/10 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button wire:click="delete({{ $auto->id }})"
                                wire:confirm="Remover esta automação?"
                                class="p-2 rounded-lg text-gray-500 hover:text-red-400 hover:bg-red-400/10 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
