<div>
    {{-- Tabs --}}
    <div style="display:flex; gap:8px; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:12px;">
        @foreach(['widgets' => 'Widgets', 'editor' => 'Editor de Fluxo', 'leads' => 'Leads Capturados'] as $k => $l)
        <button wire:click="$set('tab', '{{ $k }}')"
                style="padding:6px 16px; font-size:12px; font-weight:{{ $tab === $k ? '700' : '400' }}; border-radius:8px; cursor:pointer; border:1px solid {{ $tab === $k ? 'rgba(178,255,0,0.3)' : 'rgba(255,255,255,0.08)' }}; background:{{ $tab === $k ? 'rgba(178,255,0,0.1)' : 'transparent' }}; color:{{ $tab === $k ? '#b2ff00' : 'rgba(255,255,255,0.4)' }};">
            {{ $l }}
        </button>
        @endforeach
    </div>

    {{-- ═══ TAB: WIDGETS ═══ --}}
    @if($tab === 'widgets')
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
        <h3 style="font-size:14px; font-weight:700; color:white;">Seus Widgets</h3>
        <button wire:click="$set('showWidgetForm', true)" style="padding:6px 14px; font-size:11px; font-weight:600; color:#111; background:#b2ff00; border:none; border-radius:8px; cursor:pointer;">+ Novo Widget</button>
    </div>

    @if($showWidgetForm)
    <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:16px; margin-bottom:16px;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div>
                <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Nome interno</label>
                <input wire:model="widgetName" type="text" placeholder="Ex: Site principal" style="width:100%; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none; box-sizing:border-box;">
            </div>
            <div>
                <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Título do chat</label>
                <input wire:model="widgetTitle" type="text" style="width:100%; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none; box-sizing:border-box;">
            </div>
            <div>
                <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Subtítulo</label>
                <input wire:model="widgetSubtitle" type="text" placeholder="Online agora" style="width:100%; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none; box-sizing:border-box;">
            </div>
            <div>
                <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Cor principal</label>
                <div style="display:flex; gap:8px; align-items:center;">
                    <input wire:model="widgetColor" type="color" style="width:36px; height:30px; border:none; cursor:pointer; background:transparent;">
                    <input wire:model="widgetColor" type="text" style="flex:1; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none;">
                </div>
            </div>
            <div>
                <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Avatar do atendente</label>
                <div style="display:flex; align-items:center; gap:10px;">
                    @if($widgetAvatarUrl)
                    <img src="{{ $widgetAvatarUrl }}" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid rgba(178,255,0,0.3);">
                    <button wire:click="$set('widgetAvatarUrl', '')" style="font-size:10px; color:#f87171; background:none; border:none; cursor:pointer;">Remover</button>
                    @else
                    <div style="width:40px; height:40px; border-radius:50%; background:#25D366; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <svg width="20" height="20" fill="white" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                    </div>
                    @endif
                    <label style="padding:6px 12px; font-size:10px; font-weight:600; color:#b2ff00; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:7px; cursor:pointer;">
                        📷 Enviar foto
                        <input type="file" wire:model="avatarUpload" accept="image/*" style="display:none;">
                    </label>
                    <span wire:loading wire:target="avatarUpload" style="font-size:10px; color:rgba(255,255,255,0.4);">Enviando...</span>
                </div>
            </div>
            <div>
                <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">WhatsApp</label>
                <input wire:model="widgetWhatsapp" type="text" placeholder="5511999999999" style="width:100%; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none; box-sizing:border-box;">
            </div>
            <div>
                <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Posição</label>
                <select wire:model="widgetPosition" style="width:100%; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none;">
                    <option value="bottom-right" style="background:#1a1f2e;">Inferior Direito</option>
                    <option value="bottom-left" style="background:#1a1f2e;">Inferior Esquerdo</option>
                </select>
            </div>
        </div>
        <div style="margin-top:12px;">
            <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Mensagem pré-preenchida do WhatsApp</label>
            <input wire:model="widgetWhatsappMsg" type="text" placeholder="Olá! Vim pelo site e gostaria de..." style="width:100%; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none; box-sizing:border-box;">
        </div>
        <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:14px;">
            <button wire:click="$set('showWidgetForm', false)" style="padding:6px 14px; font-size:11px; color:rgba(255,255,255,0.4); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:7px; cursor:pointer;">Cancelar</button>
            <button wire:click="saveWidget" style="padding:6px 16px; font-size:11px; font-weight:700; color:#111; background:#b2ff00; border:none; border-radius:7px; cursor:pointer;">Salvar</button>
        </div>
    </div>
    @endif

    @foreach($widgets as $w)
    <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:12px; padding:14px 18px; margin-bottom:8px; display:flex; align-items:center; gap:14px;">
        <div style="width:12px; height:12px; border-radius:50%; background:{{ $w->is_active ? '#4ade80' : '#ef4444' }}; flex-shrink:0;" title="{{ $w->is_active ? 'Ativo' : 'Inativo' }}"></div>
        <div style="flex:1; min-width:0;">
            <p style="font-size:13px; font-weight:700; color:white;">{{ $w->name }}</p>
            <p style="font-size:10px; color:rgba(255,255,255,0.3); margin-top:2px;">{{ $w->leads_count }} leads · ID: {{ $w->public_id }}</p>
        </div>
        <div style="display:flex; gap:6px; flex-shrink:0;">
            <button wire:click="openFlowEditor({{ $w->id }})" style="padding:4px 10px; font-size:10px; font-weight:600; color:#a78bfa; background:rgba(167,139,250,0.1); border:1px solid rgba(167,139,250,0.2); border-radius:6px; cursor:pointer;">Fluxo</button>
            <button wire:click="editWidget({{ $w->id }})" style="padding:4px 10px; font-size:10px; color:#60a5fa; background:rgba(96,165,250,0.1); border:1px solid rgba(96,165,250,0.2); border-radius:6px; cursor:pointer;">Editar</button>
            <button wire:click="toggleWidget({{ $w->id }})" style="padding:4px 10px; font-size:10px; color:{{ $w->is_active ? '#f59e0b' : '#4ade80' }}; background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:6px; cursor:pointer;">{{ $w->is_active ? 'Desativar' : 'Ativar' }}</button>
            <button wire:click="deleteWidget({{ $w->id }})" wire:confirm="Excluir widget e todos os dados?" style="padding:4px 10px; font-size:10px; color:#f87171; background:transparent; border:1px solid rgba(239,68,68,0.2); border-radius:6px; cursor:pointer;">✕</button>
        </div>
    </div>
    @endforeach

    {{-- Script de instalação --}}
    @if($widgets->isNotEmpty())
    <div style="margin-top:20px; padding:14px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:10px;">
        <p style="font-size:11px; font-weight:700; color:rgba(255,255,255,0.5); margin-bottom:8px;">📋 Script de instalação (copie e cole antes do &lt;/body&gt;)</p>
        @foreach($widgets as $w)
        <div style="margin-bottom:8px;">
            <p style="font-size:10px; color:rgba(255,255,255,0.3); margin-bottom:4px;">{{ $w->name }}:</p>
            <div x-data style="display:flex; gap:6px;">
                <code style="flex:1; font-size:10px; padding:8px; background:rgba(0,0,0,0.3); border-radius:6px; color:#b2ff00; overflow-x:auto; white-space:nowrap;">&lt;script src="{{ url('/js/flut-chat.js') }}?id={{ $w->public_id }}"&gt;&lt;/script&gt;</code>
                <button @click="navigator.clipboard.writeText('<script src=\'{{ url('/js/flut-chat.js') }}?id={{ $w->public_id }}\'><\/script>'); $dispatch('toast', {type:'success', message:'Copiado!'})"
                        style="padding:4px 10px; font-size:10px; color:#b2ff00; background:rgba(178,255,0,0.1); border:1px solid rgba(178,255,0,0.2); border-radius:6px; cursor:pointer; flex-shrink:0;">Copiar</button>
            </div>
        </div>
        @endforeach
    </div>
    @endif
    @endif

    {{-- ═══ TAB: EDITOR DE FLUXO ═══ --}}
    @if($tab === 'editor')
    @if(!$editingFlowId)
        <p style="color:rgba(255,255,255,0.3); font-size:13px; text-align:center; padding:40px;">Selecione um widget na aba Widgets e clique em "Fluxo" para editar.</p>
    @else
        @php $currentWidget = $widgets->firstWhere('id', $selectedWidgetId); @endphp
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
            <h3 style="font-size:14px; font-weight:700; color:white;">Fluxo: {{ $currentWidget?->name }}</h3>
            <button wire:click="$set('showStepForm', true)" style="padding:6px 14px; font-size:11px; font-weight:600; color:#111; background:#a78bfa; border:none; border-radius:8px; cursor:pointer;">+ Novo Step</button>
        </div>

        @if($showStepForm)
        <div style="background:rgba(167,139,250,0.05); border:1px solid rgba(167,139,250,0.2); border-radius:12px; padding:16px; margin-bottom:16px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                    <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Tipo</label>
                    <select wire:model.live="stepType" style="width:100%; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none;">
                        <option value="message" style="background:#1a1f2e;">💬 Mensagem</option>
                        <option value="input" style="background:#1a1f2e;">✏️ Input (texto livre)</option>
                        <option value="options" style="background:#1a1f2e;">🔘 Opções (botões)</option>
                        <option value="action" style="background:#1a1f2e;">⚡ Ação final</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Mensagem / Pergunta</label>
                    <input wire:model="stepContent" type="text" placeholder="O que o bot vai dizer..." style="width:100%; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none; box-sizing:border-box;">
                </div>
            </div>

            @if($stepType === 'input')
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:10px;">
                <div>
                    <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Chave do campo (ex: nome, email, telefone)</label>
                    <input wire:model="stepInputKey" type="text" placeholder="nome" style="width:100%; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none; box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Placeholder</label>
                    <input wire:model="stepInputPlaceholder" type="text" placeholder="Seu nome..." style="width:100%; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none; box-sizing:border-box;">
                </div>
            </div>
            @endif

            @if($stepType === 'options')
            <div style="margin-top:10px;">
                <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:6px;">Opções (cada uma pode levar a um step diferente)</label>
                @foreach($stepOptions as $i => $opt)
                <div style="display:flex; gap:6px; margin-bottom:6px; align-items:center;">
                    <input wire:model="stepOptions.{{ $i }}.label" type="text" placeholder="Texto do botão" style="flex:2; padding:6px 10px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:white; outline:none;">
                    <select wire:model="stepOptions.{{ $i }}.next_step_id" style="flex:1; padding:6px 10px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:white; outline:none;">
                        <option value="" style="background:#1a1f2e;">→ Próximo step</option>
                        @foreach($allSteps as $s)
                        <option value="{{ $s->id }}" style="background:#1a1f2e;">#{{ $s->sort_order }} {{ Str::limit($s->content, 30) }}</option>
                        @endforeach
                    </select>
                    <button wire:click="removeOption({{ $i }})" style="color:#f87171; background:none; border:none; cursor:pointer;">✕</button>
                </div>
                @endforeach
                <button wire:click="addOption" style="font-size:10px; color:rgba(255,255,255,0.3); background:none; border:1px dashed rgba(255,255,255,0.1); border-radius:6px; padding:4px 10px; cursor:pointer;">+ Opção</button>
            </div>
            @endif

            @if($stepType === 'action')
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:10px;">
                <div>
                    <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Tipo de ação</label>
                    <select wire:model="stepActionType" style="width:100%; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none;">
                        <option value="whatsapp" style="background:#1a1f2e;">📱 WhatsApp</option>
                        <option value="lead" style="background:#1a1f2e;">📋 Salvar Lead no CRM</option>
                        <option value="ia" style="background:#1a1f2e;">🤖 Chat com IA</option>
                        <option value="redirect" style="background:#1a1f2e;">🔗 Redirecionar URL</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Valor (URL, número, etc)</label>
                    <input wire:model="stepActionValue" type="text" placeholder="https://..." style="width:100%; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none; box-sizing:border-box;">
                </div>
            </div>
            @endif

            @if(in_array($stepType, ['message', 'input']))
            <div style="margin-top:10px;">
                <label style="font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;">Próximo step</label>
                <select wire:model="stepNextId" style="width:100%; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none;">
                    <option value="" style="background:#1a1f2e;">Nenhum (fim)</option>
                    @foreach($allSteps as $s)
                    <option value="{{ $s->id }}" style="background:#1a1f2e;">#{{ $s->sort_order }} {{ Str::limit($s->content, 40) }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:14px;">
                <button wire:click="$set('showStepForm', false)" style="padding:6px 14px; font-size:11px; color:rgba(255,255,255,0.4); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:7px; cursor:pointer;">Cancelar</button>
                <button wire:click="saveStep" style="padding:6px 16px; font-size:11px; font-weight:700; color:#111; background:#a78bfa; border:none; border-radius:7px; cursor:pointer;">Salvar Step</button>
            </div>
        </div>
        @endif

        {{-- Lista de steps --}}
        @foreach($flowSteps as $step)
        <div style="display:flex; align-items:flex-start; gap:12px; padding:12px 14px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:10px; margin-bottom:6px;">
            <div style="width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:12px; font-weight:800; color:#111; background:{{ match($step->type) { 'message' => '#60a5fa', 'input' => '#4ade80', 'options' => '#f59e0b', 'action' => '#ef4444', default => '#888' } }};">
                {{ $step->sort_order }}
            </div>
            <div style="flex:1; min-width:0;">
                <div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
                    <span style="font-size:9px; padding:2px 6px; border-radius:4px; font-weight:700; text-transform:uppercase; background:rgba(255,255,255,0.06); color:rgba(255,255,255,0.4);">{{ $step->type }}</span>
                    @if($step->input_key)<span style="font-size:9px; color:#4ade80;">→ {{ $step->input_key }}</span>@endif
                    @if($step->action_type)<span style="font-size:9px; color:#ef4444;">⚡ {{ $step->action_type }}</span>@endif
                </div>
                <p style="font-size:12px; color:rgba(255,255,255,0.7);">{{ $step->content ?: '(sem texto)' }}</p>
                @if($step->type === 'options' && $step->options)
                <div style="display:flex; gap:4px; margin-top:4px; flex-wrap:wrap;">
                    @foreach($step->options as $opt)
                    <span style="font-size:9px; padding:2px 8px; border-radius:10px; background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.2); color:#fbbf24;">{{ $opt['label'] }}</span>
                    @endforeach
                </div>
                @endif
            </div>
            <div style="display:flex; gap:4px; flex-shrink:0;">
                <button wire:click="moveStepUp({{ $step->id }})" title="Mover para cima" style="padding:3px 6px; font-size:10px; color:rgba(255,255,255,0.3); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:5px; cursor:pointer;">↑</button>
                <button wire:click="moveStepDown({{ $step->id }})" title="Mover para baixo" style="padding:3px 6px; font-size:10px; color:rgba(255,255,255,0.3); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:5px; cursor:pointer;">↓</button>
                <button wire:click="editStep({{ $step->id }})" style="padding:3px 8px; font-size:10px; color:#60a5fa; background:transparent; border:1px solid rgba(96,165,250,0.2); border-radius:5px; cursor:pointer;">✏️</button>
                <button wire:click="deleteStep({{ $step->id }})" wire:confirm="Excluir step?" style="padding:3px 8px; font-size:10px; color:#f87171; background:transparent; border:1px solid rgba(239,68,68,0.2); border-radius:5px; cursor:pointer;">✕</button>
            </div>
        </div>
        @endforeach

        @if($flowSteps->isEmpty())
        <p style="color:rgba(255,255,255,0.2); font-size:12px; text-align:center; padding:20px;">Nenhum step. Clique em "+ Novo Step" para começar.</p>
        @endif
    @endif
    @endif

    {{-- ═══ TAB: LEADS ═══ --}}
    @if($tab === 'leads')
    <h3 style="font-size:14px; font-weight:700; color:white; margin-bottom:16px;">Leads Capturados</h3>
    @forelse($recentLeads as $lead)
    <div style="padding:12px 14px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:10px; margin-bottom:6px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
            <span style="font-size:11px; font-weight:600; color:white;">{{ $lead->widget?->name ?? 'Widget removido' }}</span>
            <span style="font-size:10px; color:rgba(255,255,255,0.3);">{{ $lead->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            @foreach($lead->data ?? [] as $key => $val)
            <span style="font-size:11px; color:rgba(255,255,255,0.6);"><strong style="color:rgba(255,255,255,0.3);">{{ $key }}:</strong> {{ $val }}</span>
            @endforeach
        </div>
        @if($lead->action_taken)
        <span style="font-size:9px; margin-top:4px; display:inline-block; padding:2px 6px; border-radius:4px; background:rgba(178,255,0,0.08); color:#b2ff00;">{{ $lead->action_taken }}</span>
        @endif
    </div>
    @empty
    <p style="color:rgba(255,255,255,0.2); font-size:12px; text-align:center; padding:30px;">Nenhum lead capturado ainda.</p>
    @endforelse
    @endif
</div>
