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
        @foreach([''=>'Todos','APPROVED'=>'Aprovados','PENDING'=>'Pendentes','REJECTED'=>'Rejeitados'] as $key => $label)
        @php
            $colors = ['' => '#b2ff00', 'APPROVED' => '#4ade80', 'PENDING' => '#fbbf24', 'REJECTED' => '#f87171'];
            $c = $colors[$key];
            $active = $statusFilter === $key;
            $countKey = match($key) { '' => 'all', 'APPROVED' => 'approved', 'PENDING' => 'pending', 'REJECTED' => 'rejected' };
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
            <h3 style="font-size:14px; font-weight:700; color:white;">Categoria do template</h3>
            <div style="display:flex; gap:8px;">
                <button wire:click="cancelCreate" style="padding:6px 14px; font-size:11px; color:rgba(255,255,255,0.4); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:8px; cursor:pointer;">Cancelar</button>
                <button wire:click="nextStep" style="padding:6px 16px; font-size:11px; font-weight:700; background:linear-gradient(135deg,#3b82f6,#2563eb); color:white; border:none; border-radius:8px; cursor:pointer;">Próximo</button>
            </div>
        </div>

        <div style="display:flex; gap:8px; margin-bottom:16px;">
            <button wire:click="$set('category', 'MARKETING')"
                    style="padding:8px 20px; font-size:12px; font-weight:600; border-radius:8px; cursor:pointer; border:1px solid {{ $category === 'MARKETING' ? 'rgba(59,130,246,0.5)' : 'rgba(255,255,255,0.1)' }}; background:{{ $category === 'MARKETING' ? 'rgba(59,130,246,0.1)' : 'transparent' }}; color:{{ $category === 'MARKETING' ? '#60a5fa' : 'rgba(255,255,255,0.4)' }};">
                Marketing
            </button>
            <button wire:click="$set('category', 'UTILITY')"
                    style="padding:8px 20px; font-size:12px; font-weight:600; border-radius:8px; cursor:pointer; border:1px solid {{ $category === 'UTILITY' ? 'rgba(34,197,94,0.5)' : 'rgba(255,255,255,0.1)' }}; background:{{ $category === 'UTILITY' ? 'rgba(34,197,94,0.1)' : 'transparent' }}; color:{{ $category === 'UTILITY' ? '#4ade80' : 'rgba(255,255,255,0.4)' }};">
                Utilidade
            </button>
        </div>
        <p style="font-size:11px; color:rgba(255,255,255,0.3); line-height:1.6;">
            @if($category === 'MARKETING')
                Para mensagens promocionais, ofertas especiais, vendas ou anúncios de novos produtos.
            @else
                Para mensagens que mantêm clientes informados, como rastreamento de pedidos, lembretes, confirmações ou atualizações.
            @endif
        </p>

        @else
        {{-- Step 2: Editor --}}
        <div style="display:flex; gap:24px; flex-wrap:wrap;">
            {{-- Formulário (esquerda) --}}
            <div style="flex:1; min-width:320px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                    <div>
                        <span style="font-size:10px; font-weight:700; padding:3px 10px; border-radius:20px; background:{{ $category === 'MARKETING' ? 'rgba(59,130,246,0.15)' : 'rgba(34,197,94,0.15)' }}; color:{{ $category === 'MARKETING' ? '#60a5fa' : '#4ade80' }};">{{ $category === 'MARKETING' ? 'Marketing' : 'Utilidade' }}</span>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button wire:click="cancelCreate" style="padding:6px 14px; font-size:11px; color:rgba(255,255,255,0.4); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:8px; cursor:pointer;">Cancelar</button>
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
                <div style="display:flex; gap:12px; margin-bottom:14px;">
                    <div style="flex:1;">
                        <label style="display:block; font-size:10px; font-weight:600; color:rgba(255,255,255,0.4); margin-bottom:4px;">Idioma</label>
                        <select wire:model="language" style="width:100%; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none;">
                            <option value="pt_BR">Português (BR)</option>
                            <option value="en_US">English (US)</option>
                            <option value="es">Español</option>
                        </select>
                    </div>
                </div>

                {{-- Cabeçalho --}}
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:10px; font-weight:600; color:rgba(255,255,255,0.4); margin-bottom:4px;">Cabeçalho (opcional)</label>
                    <select wire:model.live="headerType" style="width:100%; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; margin-bottom:6px;">
                        <option value="none">Sem cabeçalho</option>
                        <option value="text">Texto</option>
                        <option value="image">Imagem</option>
                        <option value="video">Vídeo</option>
                        <option value="document">Documento</option>
                    </select>
                    @if($headerType === 'text')
                    <input wire:model="headerText" type="text" placeholder="Texto do cabeçalho" style="width:100%; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none;">
                    @endif
                </div>

                {{-- Corpo --}}
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:10px; font-weight:600; color:rgba(255,255,255,0.4); margin-bottom:4px;">Corpo do texto * <span style="float:right; color:rgba(255,255,255,0.2);">{{ strlen($bodyText) }}/1024</span></label>
                    <textarea wire:model="bodyText" rows="6" placeholder="Digite o texto da mensagem... Use {{1}}, {{2}} para variáveis."
                              style="width:100%; padding:10px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; resize:vertical; line-height:1.5;"></textarea>
                    <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:4px;">Use @{{1}}, @{{2}} para variáveis. Ex: "Olá @{{1}}, sua proposta está pronta!"</p>
                    @error('bodyText') <p style="font-size:10px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                {{-- Rodapé --}}
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:10px; font-weight:600; color:rgba(255,255,255,0.4); margin-bottom:4px;">Rodapé (opcional) <span style="float:right; color:rgba(255,255,255,0.2);">{{ strlen($footerText) }}/60</span></label>
                    <input wire:model="footerText" type="text" placeholder="Mensagem no rodapé" maxlength="60"
                           style="width:100%; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none;">
                </div>

                {{-- Botões --}}
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:10px; font-weight:600; color:rgba(255,255,255,0.4); margin-bottom:8px;">Botões (opcional, máx. 3)</label>

                    @foreach($buttons as $i => $btn)
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; padding:8px 10px; background:rgba(255,255,255,0.03); border-radius:6px; border:1px solid rgba(255,255,255,0.06);">
                        <span style="font-size:10px; color:rgba(255,255,255,0.3); flex-shrink:0;">{{ $btn['type'] === 'quick_reply' ? '↩️' : ($btn['type'] === 'url' ? '🔗' : '📞') }}</span>
                        <span style="font-size:11px; color:rgba(255,255,255,0.7); flex:1;">{{ $btn['text'] }}</span>
                        @if($btn['value'])<span style="font-size:10px; color:rgba(255,255,255,0.3);">{{ $btn['value'] }}</span>@endif
                        <button wire:click="removeButton({{ $i }})" style="background:none; border:none; color:#f87171; cursor:pointer; font-size:14px;">&times;</button>
                    </div>
                    @endforeach

                    @if(count($buttons) < 3)
                    <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:6px;">
                        <select wire:model="newButtonType" style="padding:6px 10px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:6px; color:white; outline:none;">
                            <option value="">Tipo...</option>
                            <option value="quick_reply">Resposta rápida</option>
                            <option value="url">Link (URL)</option>
                            <option value="phone">Telefone</option>
                        </select>
                        <input wire:model="newButtonText" type="text" placeholder="Texto do botão" style="flex:1; padding:6px 10px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:6px; color:white; outline:none;">
                        @if($newButtonType === 'url' || $newButtonType === 'phone')
                        <input wire:model="newButtonValue" type="text" placeholder="{{ $newButtonType === 'url' ? 'https://...' : '+5511...' }}" style="flex:1; padding:6px 10px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:6px; color:white; outline:none;">
                        @endif
                        <button wire:click="addButton" style="padding:6px 12px; font-size:11px; background:rgba(178,255,0,0.1); border:1px solid rgba(178,255,0,0.2); color:#b2ff00; border-radius:6px; cursor:pointer;">+</button>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Preview (direita) --}}
            <div style="width:280px; flex-shrink:0;">
                <p style="font-size:10px; font-weight:600; color:rgba(255,255,255,0.3); margin-bottom:8px; text-align:center;">Preview</p>
                <div style="background:#e5ddd5; border-radius:16px; padding:16px 12px; min-height:400px; position:relative;">
                    {{-- Status bar --}}
                    <div style="text-align:center; margin-bottom:12px;">
                        <span style="font-size:9px; color:rgba(0,0,0,0.3);">WhatsApp</span>
                    </div>

                    {{-- Message bubble --}}
                    @if($bodyText || $headerText)
                    <div style="background:white; border-radius:8px; padding:8px 10px; box-shadow:0 1px 2px rgba(0,0,0,0.1); max-width:240px;">
                        @if($headerType === 'text' && $headerText)
                        <p style="font-size:12px; font-weight:700; color:#111; margin-bottom:4px;">{{ $headerText }}</p>
                        @elseif(in_array($headerType, ['image','video']))
                        <div style="background:linear-gradient(135deg,#c4b5fd,#a78bfa); border-radius:6px; height:120px; margin-bottom:6px; display:flex; align-items:center; justify-content:center;">
                            <span style="font-size:24px;">{{ $headerType === 'image' ? '🖼️' : '🎬' }}</span>
                        </div>
                        @elseif($headerType === 'document')
                        <div style="background:#f3f4f6; border-radius:6px; padding:10px; margin-bottom:6px; display:flex; align-items:center; gap:8px;">
                            <span style="font-size:18px;">📄</span>
                            <span style="font-size:10px; color:#666;">Documento</span>
                        </div>
                        @endif

                        <p style="font-size:11px; color:#333; line-height:1.5; white-space:pre-wrap;">{{ $bodyText ?: 'Corpo do texto...' }}</p>

                        @if($footerText)
                        <p style="font-size:9px; color:#999; margin-top:6px;">{{ $footerText }}</p>
                        @endif

                        @if(!empty($buttons))
                        <div style="border-top:1px solid #eee; margin-top:8px; padding-top:6px;">
                            @foreach($buttons as $btn)
                            <div style="text-align:center; padding:6px; font-size:11px; color:#0088cc; cursor:pointer; {{ !$loop->last ? 'border-bottom:1px solid #f0f0f0;' : '' }}">
                                {{ $btn['type'] === 'url' ? '🔗 ' : ($btn['type'] === 'phone' ? '📞 ' : '') }}{{ $btn['text'] }}
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @else
                    <div style="display:flex; align-items:center; justify-content:center; height:200px; color:rgba(0,0,0,0.2); font-size:12px;">
                        Preencha o corpo do texto
                    </div>
                    @endif
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
                    <th style="text-align:left; padding:10px 8px; color:rgba(255,255,255,0.3); font-size:10px; font-weight:600; text-transform:uppercase;">Texto</th>
                    <th style="padding:10px 8px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($templates as $t)
                @php
                    $statusColors = ['APPROVED' => '#4ade80', 'PENDING' => '#fbbf24', 'IN_REVIEW' => '#fbbf24', 'REJECTED' => '#f87171', 'DISABLED' => '#6b7280'];
                    $sc = $statusColors[$t['status']] ?? '#6b7280';
                    $statusLabels = ['APPROVED' => 'Aprovado', 'PENDING' => 'Pendente', 'IN_REVIEW' => 'Em análise', 'REJECTED' => 'Rejeitado', 'DISABLED' => 'Desativado'];
                    $sl = $statusLabels[$t['status']] ?? $t['status'];
                    $bodyPreview = '';
                    foreach ($t['components'] ?? [] as $comp) {
                        if (($comp['type'] ?? '') === 'BODY') { $bodyPreview = $comp['text'] ?? ''; break; }
                    }
                @endphp
                <tr style="border-bottom:1px solid rgba(255,255,255,0.03); transition:background 0.1s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:10px 12px; color:white; font-weight:600; font-family:monospace; font-size:11px;">{{ $t['name'] }}</td>
                    <td style="padding:10px 8px;">
                        <span style="font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; background:{{ $sc }}1a; color:{{ $sc }}; border:1px solid {{ $sc }}33;">{{ $sl }}</span>
                    </td>
                    <td style="padding:10px 8px; color:rgba(255,255,255,0.4); font-size:11px;">{{ $t['category'] ?? '—' }}</td>
                    <td style="padding:10px 8px; color:rgba(255,255,255,0.4); font-size:11px;">{{ $t['language'] ?? 'pt_BR' }}</td>
                    <td style="padding:10px 8px; color:rgba(255,255,255,0.3); font-size:11px; max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ \Illuminate\Support\Str::limit($bodyPreview, 80) }}</td>
                    <td style="padding:10px 8px; text-align:right;">
                        <button wire:click="deleteTemplate({{ $t['id'] }})" wire:confirm="Excluir este template?"
                                style="padding:4px 8px; font-size:10px; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.15); color:#f87171; border-radius:6px; cursor:pointer;">
                            Excluir
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
