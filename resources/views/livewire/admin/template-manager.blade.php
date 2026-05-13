<div>
    {{-- Header --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif;">Modelos de WhatsApp</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:2px;">Crie e gerencie templates para mensagens ativas via Meta API</p>
        </div>
        <div style="display:flex; gap:8px;">
            <button wire:click="syncTemplates" wire:loading.attr="disabled"
                    style="padding:8px 14px; font-size:11px; font-weight:600; background:rgba(59,130,246,0.1); border:1px solid rgba(59,130,246,0.2); color:#60a5fa; border-radius:8px; cursor:pointer;">
                <span wire:loading.remove wire:target="syncTemplates">Sincronizar Meta</span>
                <span wire:loading wire:target="syncTemplates">Sincronizando...</span>
            </button>
            <button wire:click="openCreate"
                    style="padding:8px 16px; font-size:11px; font-weight:700; background:linear-gradient(135deg,#b2ff00,#8fcc00); color:#111; border:none; border-radius:8px; cursor:pointer;">
                + Novo Template
            </button>
        </div>
    </div>

    {{-- Filtros --}}
    <div style="display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; align-items:center;">
        @foreach([''=>'Todos','APPROVED'=>'Aprovados','PENDING'=>'Pendentes','REJECTED'=>'Rejeitados','DRAFT'=>'Rascunhos'] as $key => $label)
        @php
            $colors = ['' => '#b2ff00', 'APPROVED' => '#4ade80', 'PENDING' => '#fbbf24', 'REJECTED' => '#f87171', 'DRAFT' => '#94a3b8'];
            $c = $colors[$key];
            $active = $statusFilter === $key;
            $countKey = match($key) { '' => 'all', 'APPROVED' => 'approved', 'PENDING' => 'pending', 'REJECTED' => 'rejected', 'DRAFT' => 'draft' };
        @endphp
        <button wire:click="setFilter('{{ $key }}')"
                style="padding:5px 14px; font-size:11px; font-weight:600; border-radius:20px; cursor:pointer; border:1px solid {{ $active ? $c.'66' : 'rgba(255,255,255,0.1)' }}; background:{{ $active ? $c.'1a' : 'rgba(255,255,255,0.03)' }}; color:{{ $active ? $c : 'rgba(255,255,255,0.4)' }};">
            {{ $label }} <span style="opacity:0.6; margin-left:3px;">{{ $counts[$countKey] }}</span>
        </button>
        @endforeach

        <input wire:model.live.debounce.300ms="searchQuery" type="text" placeholder="Buscar template..."
               style="margin-left:auto; padding:6px 12px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; width:200px;">
    </div>

    {{-- FORM: Criar Template --}}
    @if($showForm)
    <div style="background:linear-gradient(145deg, rgba(17,24,39,0.95), rgba(11,15,28,0.98)); border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:24px; margin-bottom:20px;">

        @if($step === 'category')
        {{-- Step 1: Categoria --}}
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
            <h3 style="font-size:14px; font-weight:700; color:white;">Categoria do modelo do WhatsApp</h3>
            <div style="display:flex; gap:8px;">
                <button wire:click="cancelCreate" style="padding:6px 14px; font-size:11px; color:rgba(255,255,255,0.4); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:8px; cursor:pointer;">Cancelar</button>
                <button wire:click="nextStep" style="padding:6px 16px; font-size:11px; font-weight:700; background:linear-gradient(135deg,#3b82f6,#2563eb); color:white; border:none; border-radius:8px; cursor:pointer;">Próximo</button>
            </div>
        </div>

        <div style="display:flex; gap:8px; margin-bottom:16px;">
            <button wire:click="$set('category', 'MARKETING')"
                    style="padding:10px 24px; font-size:12px; font-weight:600; border-radius:8px; cursor:pointer; border:2px solid {{ $category === 'MARKETING' ? '#3b82f6' : 'rgba(255,255,255,0.1)' }}; background:{{ $category === 'MARKETING' ? 'rgba(59,130,246,0.1)' : 'transparent' }}; color:{{ $category === 'MARKETING' ? '#60a5fa' : 'rgba(255,255,255,0.4)' }};">
                Marketing
            </button>
            <button wire:click="$set('category', 'UTILITY')"
                    style="padding:10px 24px; font-size:12px; font-weight:600; border-radius:8px; cursor:pointer; border:2px solid {{ $category === 'UTILITY' ? '#22c55e' : 'rgba(255,255,255,0.1)' }}; background:{{ $category === 'UTILITY' ? 'rgba(34,197,94,0.1)' : 'transparent' }}; color:{{ $category === 'UTILITY' ? '#4ade80' : 'rgba(255,255,255,0.4)' }};">
                Utilidade
            </button>
        </div>
        <p style="font-size:12px; color:rgba(255,255,255,0.35); line-height:1.6;">
            @if($category === 'MARKETING')
                Para mensagens promocionais, como ofertas especiais, vendas ou anúncios de novos produtos.
            @else
                Para mensagens que mantêm os clientes informados, como rastreamento de pedidos, lembretes de reservas, confirmações ou atualizações de pagamento.
            @endif
        </p>

        @else
        {{-- Step 2: Editor --}}
        <div style="display:flex; gap:24px; flex-wrap:wrap;">
            {{-- Formulário (esquerda) --}}
            <div style="flex:1; min-width:340px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:10px; font-weight:700; padding:3px 10px; border-radius:20px; background:{{ $category === 'MARKETING' ? 'rgba(59,130,246,0.15)' : 'rgba(34,197,94,0.15)' }}; color:{{ $category === 'MARKETING' ? '#60a5fa' : '#4ade80' }};">Categoria de {{ strtolower($category === 'MARKETING' ? 'marketing' : 'utilidade') }}</span>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button wire:click="cancelCreate" style="padding:6px 14px; font-size:11px; color:rgba(255,255,255,0.4); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:8px; cursor:pointer;">Cancelar</button>
                        <button wire:click="saveDraft" wire:loading.attr="disabled"
                                style="padding:6px 16px; font-size:11px; font-weight:600; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.15); color:rgba(255,255,255,0.7); border-radius:8px; cursor:pointer;">
                            Salvar rascunho
                        </button>
                        <button wire:click="submitTemplate" wire:loading.attr="disabled"
                                style="padding:6px 16px; font-size:11px; font-weight:700; background:linear-gradient(135deg,#22c55e,#16a34a); color:white; border:none; border-radius:8px; cursor:pointer;">
                            <span wire:loading.remove wire:target="submitTemplate">Enviar para análise</span>
                            <span wire:loading wire:target="submitTemplate">Enviando...</span>
                        </button>
                    </div>
                </div>

                {{-- Nome --}}
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:10px; font-weight:600; color:rgba(255,255,255,0.4); margin-bottom:4px;">Nome do template *</label>
                    <input wire:model="templateName" type="text" placeholder="ex: promocao_verao (apenas letras minúsculas, números e _)"
                           style="width:100%; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; font-family:monospace;">
                    @error('templateName') <p style="font-size:10px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                {{-- Idioma --}}
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:10px; font-weight:600; color:rgba(255,255,255,0.4); margin-bottom:4px;">Idioma</label>
                    <select wire:model="language" style="width:100%; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none;">
                        <option value="pt_BR">Português (Português BR)</option>
                        <option value="en_US">English (US)</option>
                        <option value="es">Español</option>
                    </select>
                </div>

                {{-- Cabeçalho --}}
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:10px; font-weight:600; color:rgba(255,255,255,0.4); margin-bottom:4px;">Cabeçalho (Opcional)</label>
                    <select wire:model.live="headerType" style="width:100%; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; margin-bottom:6px;">
                        <option value="none">Sem cabeçalho</option>
                        <option value="text">Texto</option>
                        <option value="image">Imagem</option>
                    </select>
                    @if($headerType === 'text')
                    <input wire:model="headerText" type="text" placeholder="Texto do cabeçalho" style="width:100%; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none;">
                    @elseif($headerType === 'image')
                    <div style="margin-top:6px;">
                        <label style="display:flex; align-items:center; gap:10px; padding:16px; background:rgba(255,255,255,0.02); border:2px dashed rgba(255,255,255,0.1); border-radius:10px; cursor:pointer; transition:all 0.15s;"
                               onmouseover="this.style.borderColor='rgba(178,255,0,0.3)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'">
                            @if($headerImage)
                            <img src="{{ $headerImage->temporaryUrl() }}" style="width:60px; height:60px; border-radius:8px; object-fit:cover;">
                            <span style="font-size:11px; color:rgba(255,255,255,0.5);">Imagem selecionada — clique para trocar</span>
                            @else
                            <div style="width:48px; height:48px; border-radius:8px; background:rgba(178,255,0,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <svg width="20" height="20" fill="none" stroke="#b2ff00" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p style="font-size:11px; color:rgba(255,255,255,0.6);">Clique para enviar imagem</p>
                                <p style="font-size:10px; color:rgba(255,255,255,0.2);">JPG, PNG ou WebP — máx 5MB</p>
                            </div>
                            @endif
                            <input type="file" wire:model="headerImage" accept="image/jpeg,image/png,image/webp" class="hidden" style="display:none;">
                        </label>
                    </div>
                    @endif
                </div>

                {{-- Corpo --}}
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:10px; font-weight:600; color:rgba(255,255,255,0.4); margin-bottom:4px;">Corpo do texto * <span style="float:right; color:rgba(255,255,255,0.2);">{{ strlen($bodyText) }}/1024</span></label>
                    <textarea wire:model.live.debounce.300ms="bodyText" rows="6" placeholder="Digite o texto da mensagem aqui..."
                              style="width:100%; padding:10px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; resize:vertical; line-height:1.5;"></textarea>
                    <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:4px;">Use @{{1}}, @{{2}} para variáveis. Ex: "Olá @{{1}}, sua proposta está pronta!"</p>
                    @error('bodyText') <p style="font-size:10px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                {{-- Rodapé --}}
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:10px; font-weight:600; color:rgba(255,255,255,0.4); margin-bottom:4px;">Rodapé (Opcional) <span style="float:right; color:rgba(255,255,255,0.2);">{{ strlen($footerText) }}/60</span></label>
                    <input wire:model.live.debounce.300ms="footerText" type="text" placeholder="Mensagem no rodapé" maxlength="60"
                           style="width:100%; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none;">
                </div>

                {{-- Botões --}}
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:10px; font-weight:600; color:rgba(255,255,255,0.4); margin-bottom:8px;">Botões (Opcional)</label>

                    @foreach($buttons as $i => $btn)
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; padding:8px 10px; background:rgba(255,255,255,0.03); border-radius:6px; border:1px solid rgba(255,255,255,0.06);">
                        <span style="font-size:10px; color:rgba(255,255,255,0.3); flex-shrink:0;">{{ $btn['type'] === 'quick_reply' ? '↩️' : ($btn['type'] === 'url' ? '🔗' : '📞') }}</span>
                        <span style="font-size:11px; color:rgba(255,255,255,0.7); flex:1;">{{ $btn['text'] }}</span>
                        @if($btn['value'])<span style="font-size:10px; color:rgba(255,255,255,0.3);">{{ \Illuminate\Support\Str::limit($btn['value'], 25) }}</span>@endif
                        <button wire:click="removeButton({{ $i }})" style="background:none; border:none; color:#f87171; cursor:pointer; font-size:14px;">&times;</button>
                    </div>
                    @endforeach

                    @if(count($buttons) < 3)
                    <div style="display:flex; gap:6px; margin-top:6px;">
                        <button wire:click="$set('newButtonType', 'quick_reply')" style="padding:6px 12px; font-size:10px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); color:rgba(255,255,255,0.5); border-radius:6px; cursor:pointer;">+ Resposta rápida</button>
                        <button wire:click="$set('newButtonType', 'url')" style="padding:6px 12px; font-size:10px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); color:rgba(255,255,255,0.5); border-radius:6px; cursor:pointer;">+ Chamada para ação</button>
                    </div>
                    @if($newButtonType)
                    <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:8px; padding:10px; background:rgba(255,255,255,0.02); border-radius:8px; border:1px solid rgba(255,255,255,0.06);">
                        @if($newButtonType === 'url')
                        <select wire:model="newButtonType" style="padding:6px 8px; font-size:10px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:6px; color:white; outline:none;">
                            <option value="url">Link (URL)</option>
                            <option value="phone">Telefone</option>
                        </select>
                        @endif
                        <input wire:model="newButtonText" type="text" placeholder="Texto do botão" style="flex:1; min-width:120px; padding:6px 10px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:6px; color:white; outline:none;">
                        @if($newButtonType === 'url' || $newButtonType === 'phone')
                        <input wire:model="newButtonValue" type="text" placeholder="{{ $newButtonType === 'url' ? 'https://...' : '+5511...' }}" style="flex:1; min-width:120px; padding:6px 10px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:6px; color:white; outline:none;">
                        @endif
                        <button wire:click="addButton" style="padding:6px 14px; font-size:11px; font-weight:600; background:rgba(178,255,0,0.1); border:1px solid rgba(178,255,0,0.2); color:#b2ff00; border-radius:6px; cursor:pointer;">Adicionar</button>
                        <button wire:click="$set('newButtonType', '')" style="padding:6px 8px; font-size:11px; background:none; border:none; color:rgba(255,255,255,0.3); cursor:pointer;">✕</button>
                    </div>
                    @endif
                    @endif
                </div>
            </div>

            {{-- Preview iPhone (direita) --}}
            <div style="width:300px; flex-shrink:0;">
                <p style="font-size:10px; font-weight:600; color:rgba(255,255,255,0.3); margin-bottom:8px; text-align:center;">Preview</p>
                {{-- iPhone frame --}}
                <div style="background:#f0f0f0; border-radius:36px; padding:12px; box-shadow:0 8px 40px rgba(0,0,0,0.4), inset 0 0 0 2px rgba(255,255,255,0.1);">
                    {{-- Notch --}}
                    <div style="background:#000; border-radius:24px; overflow:hidden;">
                        {{-- Status bar --}}
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 20px 4px; background:#075e54;">
                            <span style="font-size:10px; font-weight:600; color:white;">19:12</span>
                            <div style="width:80px; height:20px; background:#000; border-radius:0 0 12px 12px;"></div>
                            <div style="display:flex; gap:3px;">
                                <span style="font-size:9px; color:white;">📶</span>
                                <span style="font-size:9px; color:white;">🔋</span>
                            </div>
                        </div>
                        {{-- WhatsApp header --}}
                        <div style="display:flex; align-items:center; gap:8px; padding:6px 12px 8px; background:#075e54;">
                            <svg width="16" height="16" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                            <div style="width:28px; height:28px; border-radius:50%; background:rgba(255,255,255,0.2);"></div>
                            <span style="font-size:12px; font-weight:600; color:white; flex:1;">Contato</span>
                            <svg width="14" height="14" fill="none" stroke="white" stroke-width="1.5" viewBox="0 0 24 24"><path d="M15 10l-4 4l-4-4"/></svg>
                            <svg width="14" height="14" fill="none" stroke="white" stroke-width="1.5" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>

                        {{-- Chat area with wallpaper --}}
                        <div style="min-height:380px; background:#e5ddd5 url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22><text x=%2220%22 y=%2240%22 font-size=%2216%22 fill=%22%23d4ccc4%22 opacity=%220.5%22>💬</text><text x=%22100%22 y=%22100%22 font-size=%2216%22 fill=%22%23d4ccc4%22 opacity=%220.5%22>📱</text><text x=%2260%22 y=%22160%22 font-size=%2216%22 fill=%22%23d4ccc4%22 opacity=%220.5%22>✉️</text></svg>') repeat; padding:16px 10px; display:flex; flex-direction:column; justify-content:flex-end;">

                            @if($bodyText || $headerText || $headerType === 'image')
                            {{-- Message bubble --}}
                            <div style="background:white; border-radius:8px 8px 8px 0; padding:0; box-shadow:0 1px 2px rgba(0,0,0,0.1); max-width:250px; overflow:hidden;">
                                @if($headerType === 'image')
                                <div style="background:linear-gradient(135deg,#a78bfa,#7c3aed); height:130px; display:flex; align-items:center; justify-content:center;">
                                    @if($headerImage)
                                    <img src="{{ $headerImage->temporaryUrl() }}" style="width:100%; height:130px; object-fit:cover;">
                                    @else
                                    <span style="font-size:32px;">🖼️</span>
                                    @endif
                                </div>
                                @endif

                                <div style="padding:6px 8px;">
                                    @if($headerType === 'text' && $headerText)
                                    <p style="font-size:12px; font-weight:700; color:#111; margin-bottom:3px;">{{ $headerText }}</p>
                                    @endif

                                    <p style="font-size:11px; color:#333; line-height:1.5; white-space:pre-wrap;">{{ $bodyText ?: 'Corpo do texto...' }}</p>

                                    @if($footerText)
                                    <p style="font-size:9px; color:#999; margin-top:4px;">{{ $footerText }}</p>
                                    @endif

                                    <p style="font-size:8px; color:#bbb; text-align:right; margin-top:2px;">19:12 ✓✓</p>
                                </div>

                                @if(!empty($buttons))
                                @foreach($buttons as $btn)
                                <div style="text-align:center; padding:7px; font-size:11px; color:#0088cc; border-top:1px solid #f0f0f0; font-weight:500;">
                                    @if($btn['type'] === 'url')🔗 @elseif($btn['type'] === 'phone')📞 @endif{{ $btn['text'] }}
                                </div>
                                @endforeach
                                @endif
                            </div>
                            @endif
                        </div>

                        {{-- Input bar --}}
                        <div style="display:flex; align-items:center; gap:6px; padding:6px 8px; background:#f0f0f0;">
                            <span style="font-size:16px;">+</span>
                            <div style="flex:1; background:white; border-radius:20px; padding:6px 12px; font-size:10px; color:#999;">Mensagem</div>
                            <span style="font-size:14px;">📷</span>
                            <span style="font-size:14px;">🎤</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- LISTA DE TEMPLATES --}}
    @if(empty($templates))
    <div style="text-align:center; padding:60px 20px; color:rgba(255,255,255,0.3);">
        <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 12px; opacity:0.3;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p style="font-size:13px;">{{ $searchQuery ? 'Nenhum template encontrado' : 'Nenhum template cadastrado' }}</p>
        <p style="font-size:11px; color:rgba(255,255,255,0.15); margin-top:4px;">Clique em "Sincronizar Meta" ou "Novo Template" para começar</p>
    </div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:12px;">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.06);">
                    <th style="text-align:left; padding:10px 12px; color:rgba(255,255,255,0.3); font-size:10px; font-weight:600; text-transform:uppercase;">Nome</th>
                    <th style="text-align:left; padding:10px 8px; color:rgba(255,255,255,0.3); font-size:10px; font-weight:600; text-transform:uppercase;">Status</th>
                    <th style="text-align:left; padding:10px 8px; color:rgba(255,255,255,0.3); font-size:10px; font-weight:600; text-transform:uppercase;">Categoria</th>
                    <th style="text-align:left; padding:10px 8px; color:rgba(255,255,255,0.3); font-size:10px; font-weight:600; text-transform:uppercase;">Idioma</th>
                    <th style="text-align:left; padding:10px 8px; color:rgba(255,255,255,0.3); font-size:10px; font-weight:600; text-transform:uppercase;">Texto de Resposta</th>
                    <th style="padding:10px 8px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($templates as $t)
                @php
                    $statusColors = ['APPROVED' => '#4ade80', 'PENDING' => '#fbbf24', 'IN_REVIEW' => '#fbbf24', 'REJECTED' => '#f87171', 'DISABLED' => '#6b7280', 'DRAFT' => '#94a3b8'];
                    $sc = $statusColors[$t['status']] ?? '#6b7280';
                    $statusLabels = ['APPROVED' => 'Aprovado', 'PENDING' => 'Pendente', 'IN_REVIEW' => 'Em análise', 'REJECTED' => 'Rejeitado', 'DISABLED' => 'Desativado', 'DRAFT' => 'Rascunho'];
                    $sl = $statusLabels[$t['status']] ?? $t['status'];
                    $bodyPreview = '';
                    foreach ($t['components'] ?? [] as $comp) {
                        if (($comp['type'] ?? '') === 'BODY') { $bodyPreview = $comp['text'] ?? ''; break; }
                    }
                @endphp
                <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                    <td style="padding:10px 12px; color:white; font-weight:600; font-family:monospace; font-size:11px;">{{ $t['name'] }}</td>
                    <td style="padding:10px 8px;">
                        <span style="font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; background:{{ $sc }}1a; color:{{ $sc }}; border:1px solid {{ $sc }}33;">{{ $sl }}</span>
                    </td>
                    <td style="padding:10px 8px; color:rgba(255,255,255,0.4); font-size:11px;">{{ $t['category'] ?? '—' }}</td>
                    <td style="padding:10px 8px; color:rgba(255,255,255,0.4); font-size:11px;">{{ $t['language'] ?? 'pt_BR' }}</td>
                    <td style="padding:10px 8px; color:rgba(255,255,255,0.3); font-size:11px; max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ \Illuminate\Support\Str::limit($bodyPreview, 80) }}</td>
                    <td style="padding:10px 8px; text-align:right; white-space:nowrap;">
                        <button wire:click="viewTemplate({{ $t['id'] }})"
                                style="padding:4px 8px; font-size:10px; background:rgba(59,130,246,0.08); border:1px solid rgba(59,130,246,0.15); color:#60a5fa; border-radius:6px; cursor:pointer; margin-right:4px;">
                            Ver
                        </button>
                        <button wire:click="deleteTemplate({{ $t['id'] }})" wire:confirm="Excluir este template?"
                                style="padding:4px 8px; font-size:10px; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.15); color:#f87171; border-radius:6px; cursor:pointer;">
                            Excluir
                        </button>
                    </td>
                </tr>
                {{-- Painel de visualização expandido --}}
                @if($viewingId === $t['id'])
                @php
                    $vHeader = null; $vBody = ''; $vFooter = ''; $vButtons = [];
                    foreach ($t['components'] ?? [] as $comp) {
                        match($comp['type'] ?? '') {
                            'HEADER' => $vHeader = $comp,
                            'BODY' => $vBody = $comp['text'] ?? '',
                            'FOOTER' => $vFooter = $comp['text'] ?? '',
                            'BUTTONS' => $vButtons = $comp['buttons'] ?? [],
                            default => null,
                        };
                    }
                @endphp
                <tr><td colspan="6" style="padding:0;">
                    <div style="padding:16px 20px; background:rgba(255,255,255,0.015); border-bottom:1px solid rgba(255,255,255,0.06);">
                        <div style="display:flex; gap:24px; flex-wrap:wrap;">
                            {{-- Detalhes --}}
                            <div style="flex:1; min-width:250px;">
                                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                                    <h4 style="font-size:13px; font-weight:700; color:white;">{{ $t['name'] }}</h4>
                                    <button wire:click="closeView" style="background:none; border:none; color:rgba(255,255,255,0.3); cursor:pointer; font-size:16px;">&times;</button>
                                </div>

                                <div style="display:grid; grid-template-columns:auto 1fr; gap:6px 16px; font-size:11px;">
                                    <span style="color:rgba(255,255,255,0.3);">Status:</span>
                                    <span style="color:{{ $sc }}; font-weight:600;">{{ $sl }}</span>
                                    <span style="color:rgba(255,255,255,0.3);">Categoria:</span>
                                    <span style="color:rgba(255,255,255,0.6);">{{ $t['category'] ?? '—' }}</span>
                                    <span style="color:rgba(255,255,255,0.3);">Idioma:</span>
                                    <span style="color:rgba(255,255,255,0.6);">{{ $t['language'] ?? 'pt_BR' }}</span>
                                    @if($t['template_id'])
                                    <span style="color:rgba(255,255,255,0.3);">Template ID:</span>
                                    <span style="color:rgba(255,255,255,0.4); font-family:monospace; font-size:10px;">{{ $t['template_id'] }}</span>
                                    @endif
                                </div>

                                @if($vHeader)
                                <div style="margin-top:12px;">
                                    <p style="font-size:10px; font-weight:600; color:rgba(255,255,255,0.3); margin-bottom:4px;">CABEÇALHO ({{ $vHeader['format'] ?? 'TEXT' }})</p>
                                    <p style="font-size:12px; color:rgba(255,255,255,0.6);">{{ $vHeader['text'] ?? ($vHeader['format'] ?? '—') }}</p>
                                </div>
                                @endif

                                <div style="margin-top:12px;">
                                    <p style="font-size:10px; font-weight:600; color:rgba(255,255,255,0.3); margin-bottom:4px;">CORPO</p>
                                    <p style="font-size:12px; color:rgba(255,255,255,0.7); white-space:pre-wrap; line-height:1.6;">{{ $vBody }}</p>
                                </div>

                                @if($vFooter)
                                <div style="margin-top:10px;">
                                    <p style="font-size:10px; font-weight:600; color:rgba(255,255,255,0.3); margin-bottom:4px;">RODAPÉ</p>
                                    <p style="font-size:11px; color:rgba(255,255,255,0.4);">{{ $vFooter }}</p>
                                </div>
                                @endif

                                @if(!empty($vButtons))
                                <div style="margin-top:10px;">
                                    <p style="font-size:10px; font-weight:600; color:rgba(255,255,255,0.3); margin-bottom:6px;">BOTÕES</p>
                                    @foreach($vButtons as $vb)
                                    <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                                        <span style="font-size:10px; color:rgba(255,255,255,0.3);">{{ ($vb['type'] ?? '') === 'QUICK_REPLY' ? '↩️' : (($vb['type'] ?? '') === 'URL' ? '🔗' : '📞') }}</span>
                                        <span style="font-size:11px; color:rgba(255,255,255,0.6);">{{ $vb['text'] ?? '' }}</span>
                                        @if(isset($vb['url']))<span style="font-size:10px; color:rgba(255,255,255,0.3);">{{ $vb['url'] }}</span>@endif
                                        @if(isset($vb['phone_number']))<span style="font-size:10px; color:rgba(255,255,255,0.3);">{{ $vb['phone_number'] }}</span>@endif
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>

                            {{-- Preview iPhone --}}
                            <div style="width:260px; flex-shrink:0;">
                                <div style="background:#e8e8e8; border-radius:30px; padding:10px;">
                                    <div style="background:#000; border-radius:20px; overflow:hidden;">
                                        <div style="display:flex; justify-content:space-between; padding:6px 16px 3px; background:#075e54;">
                                            <span style="font-size:9px; color:white;">19:12</span>
                                            <div style="width:60px; height:16px; background:#000; border-radius:0 0 10px 10px;"></div>
                                            <span style="font-size:8px; color:white;">📶🔋</span>
                                        </div>
                                        <div style="display:flex; align-items:center; gap:6px; padding:4px 10px 6px; background:#075e54;">
                                            <span style="color:white; font-size:12px;">‹</span>
                                            <div style="width:22px; height:22px; border-radius:50%; background:rgba(255,255,255,0.2);"></div>
                                            <span style="font-size:10px; color:white; flex:1;">Contato</span>
                                        </div>
                                        <div style="min-height:280px; background:#e5ddd5; padding:12px 8px; display:flex; flex-direction:column; justify-content:flex-end;">
                                            <div style="background:white; border-radius:6px 6px 6px 0; overflow:hidden; max-width:220px;">
                                                @if($vHeader && in_array($vHeader['format'] ?? '', ['IMAGE','VIDEO']))
                                                <div style="background:linear-gradient(135deg,#a78bfa,#7c3aed); height:100px; display:flex; align-items:center; justify-content:center;">
                                                    <span style="font-size:24px;">🖼️</span>
                                                </div>
                                                @endif
                                                <div style="padding:5px 7px;">
                                                    @if($vHeader && ($vHeader['format'] ?? '') === 'TEXT')
                                                    <p style="font-size:10px; font-weight:700; color:#111; margin-bottom:2px;">{{ $vHeader['text'] }}</p>
                                                    @endif
                                                    <p style="font-size:10px; color:#333; line-height:1.4; white-space:pre-wrap;">{{ $vBody }}</p>
                                                    @if($vFooter)
                                                    <p style="font-size:8px; color:#999; margin-top:3px;">{{ $vFooter }}</p>
                                                    @endif
                                                    <p style="font-size:7px; color:#bbb; text-align:right; margin-top:1px;">19:12 ✓✓</p>
                                                </div>
                                                @foreach($vButtons as $vb)
                                                <div style="text-align:center; padding:5px; font-size:10px; color:#0088cc; border-top:1px solid #f0f0f0;">
                                                    {{ $vb['text'] ?? '' }}
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div style="display:flex; align-items:center; gap:4px; padding:5px 6px; background:#f0f0f0;">
                                            <span style="font-size:12px;">+</span>
                                            <div style="flex:1; background:white; border-radius:16px; padding:4px 10px; font-size:9px; color:#999;">Mensagem</div>
                                            <span style="font-size:12px;">📷</span>
                                            <span style="font-size:12px;">🎤</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </td></tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
