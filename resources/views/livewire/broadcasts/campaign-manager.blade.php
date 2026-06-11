<div>
    {{-- Header --}}
    <div style="height:60px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:space-between; padding:0 24px; flex-shrink:0; background:rgba(11,15,28,0.5); backdrop-filter:blur(6px);">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
            <h1 style="font-size:15px; font-weight:800; color:white; font-family:Syne,sans-serif; letter-spacing:-0.02em;">Disparos</h1>
            <span style="font-size:10px; color:rgba(255,255,255,0.3); margin-left:4px;">{{ $activeLeadCount }} leads ativos</span>
        </div>
        <div style="display:flex; gap:8px;">
            <button wire:click="openReport"
                    style="display:flex; align-items:center; gap:6px; padding:6px 14px; font-size:11px; font-weight:600; color:#60a5fa; background:rgba(96,165,250,0.08); border:1px solid rgba(96,165,250,0.2); border-radius:8px; cursor:pointer;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Relatório
            </button>
            <button wire:click="openCreate"
                    style="display:flex; align-items:center; gap:6px; padding:6px 14px; font-size:11px; font-weight:600; color:#b2ff00; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:8px; cursor:pointer;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nova Campanha
            </button>
        </div>
    </div>

    <div style="padding:20px 24px;">
        {{-- Tabela de campanhas --}}
        <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:12px; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.06);">
                        <th style="padding:10px 16px; text-align:left; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Campanha</th>
                        <th style="padding:10px 16px; text-align:center; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Status</th>
                        <th style="padding:10px 16px; text-align:center; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Enviados</th>
                        <th style="padding:10px 16px; text-align:center; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Falhas</th>
                        <th style="padding:10px 16px; text-align:left; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Criado em</th>
                        <th style="padding:10px 16px; text-align:right; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                        <td style="padding:10px 16px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                @if($campaign->image_path)
                                <img src="{{ \App\Services\MediaStorage::url($campaign->image_path) }}" alt=""
                                     style="width:40px; height:40px; border-radius:8px; object-fit:cover; border:1px solid rgba(255,255,255,0.08); flex-shrink:0;">
                                @endif
                                <div>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <p style="font-size:12px; font-weight:600; color:white;">{{ $campaign->name }}</p>
                                <span style="font-size:9px; font-weight:700; padding:1px 6px; border-radius:20px; background:{{ ($campaign->channel ?? 'whatsapp') === 'email' ? 'rgba(59,130,246,0.12)' : 'rgba(34,197,94,0.12)' }}; color:{{ ($campaign->channel ?? 'whatsapp') === 'email' ? '#60a5fa' : '#4ade80' }}; border:1px solid {{ ($campaign->channel ?? 'whatsapp') === 'email' ? 'rgba(59,130,246,0.2)' : 'rgba(34,197,94,0.2)' }};">
                                    {{ ($campaign->channel ?? 'whatsapp') === 'email' ? 'EMAIL' : 'WHATSAPP' }}
                                </span>
                            </div>
                            <p style="font-size:10px; color:rgba(255,255,255,0.3); margin-top:2px;">{{ Str::limit($campaign->message ?? $campaign->subject, 60) }}</p>
                                </div>
                            </div>
                        </td>
                        <td style="padding:10px 16px; text-align:center;">
                            @php
                                $statusColors = [
                                    'draft'     => ['bg' => 'rgba(107,114,128,0.12)', 'color' => '#9ca3af', 'border' => 'rgba(107,114,128,0.2)', 'label' => 'Rascunho'],
                                    'scheduled' => ['bg' => 'rgba(245,158,11,0.12)', 'color' => '#fbbf24', 'border' => 'rgba(245,158,11,0.2)', 'label' => 'Agendada'],
                                    'paused'    => ['bg' => 'rgba(245,158,11,0.12)', 'color' => '#f97316', 'border' => 'rgba(245,158,11,0.2)', 'label' => 'Pausada'],
                                    'sending'   => ['bg' => 'rgba(59,130,246,0.12)', 'color' => '#60a5fa', 'border' => 'rgba(59,130,246,0.2)', 'label' => 'Enviando'],
                                    'completed' => ['bg' => 'rgba(34,197,94,0.12)', 'color' => '#4ade80', 'border' => 'rgba(34,197,94,0.2)', 'label' => 'Concluída'],
                                    'failed'    => ['bg' => 'rgba(239,68,68,0.12)', 'color' => '#f87171', 'border' => 'rgba(239,68,68,0.2)', 'label' => 'Falhou'],
                                ];
                                $s = $statusColors[$campaign->status] ?? $statusColors['draft'];
                            @endphp
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:{{ $s['bg'] }}; color:{{ $s['color'] }}; border:1px solid {{ $s['border'] }};">{{ $s['label'] }}</span>
                            @if($campaign->scheduled_at && in_array($campaign->status, ['scheduled', 'draft']))
                            <p style="font-size:9px; color:rgba(245,158,11,0.7); margin-top:3px;">{{ \Carbon\Carbon::parse($campaign->scheduled_at)->format('d/m/Y H:i') }}</p>
                            @endif
                        </td>
                        <td style="padding:10px 16px; text-align:center; font-size:12px; color:#4ade80; font-weight:600;">{{ $campaign->sent_count ?? 0 }}</td>
                        <td style="padding:10px 16px; text-align:center; font-size:12px; color:#f87171; font-weight:600;">{{ $campaign->failed_count ?? 0 }}</td>
                        <td style="padding:10px 16px; font-size:11px; color:rgba(255,255,255,0.4);">{{ $campaign->created_at->format('d/m/Y H:i') }}</td>
                        <td style="padding:10px 16px; text-align:right;">
                            <div style="display:flex; justify-content:flex-end; gap:6px;">
                                @if(in_array($campaign->status, ['draft', 'scheduled', 'paused']))
                                <button wire:click="editCampaign({{ $campaign->id }})"
                                        style="padding:4px 10px; font-size:11px; color:#60a5fa; background:rgba(59,130,246,0.08); border:1px solid rgba(59,130,246,0.2); border-radius:6px; cursor:pointer;">
                                    Editar
                                </button>
                                @endif
                                @if(in_array($campaign->status, ['sending', 'scheduled']))
                                <button wire:click="pauseCampaign({{ $campaign->id }})" wire:confirm="Pausar campanha?"
                                        style="padding:4px 10px; font-size:11px; color:#fbbf24; background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.2); border-radius:6px; cursor:pointer;">
                                    Pausar
                                </button>
                                @endif
                                @if(in_array($campaign->status, ['draft', 'completed', 'paused']))
                                <button wire:click="send({{ $campaign->id }})" wire:confirm="Disparar mensagem para todos os leads ativos?"
                                        style="padding:4px 10px; font-size:11px; color:#b2ff00; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:6px; cursor:pointer;">
                                    {{ $campaign->status === 'completed' ? 'Re-disparar' : 'Disparar' }}
                                </button>
                                @endif
                                <button wire:click="previewCampaign({{ $campaign->id }})"
                                        style="padding:4px 10px; font-size:11px; color:#a78bfa; background:rgba(139,92,246,0.08); border:1px solid rgba(139,92,246,0.2); border-radius:6px; cursor:pointer;">
                                    Preview
                                </button>
                                <button wire:click="viewRuns({{ $campaign->id }})"
                                        style="padding:4px 10px; font-size:11px; color:rgba(255,255,255,0.4); background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:6px; cursor:pointer;">
                                    Histórico
                                </button>
                                <button wire:click="deleteCampaign({{ $campaign->id }})" wire:confirm="Excluir campanha?"
                                        style="padding:4px 10px; font-size:11px; color:#f87171; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); border-radius:6px; cursor:pointer;">
                                    Excluir
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:40px 16px; text-align:center; font-size:13px; color:rgba(255,255,255,0.3);">Nenhuma campanha criada.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:12px;">{{ $campaigns->links() }}</div>

        {{-- Preview da campanha --}}
        @if($previewingId)
        @php $previewCampaign = $campaigns->firstWhere('id', $previewingId) ?? \App\Models\BroadcastCampaign::find($previewingId); @endphp
        @if($previewCampaign)
        <div style="margin-top:16px; background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(139,92,246,0.2); border-radius:16px; padding:20px; position:relative;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <div style="width:2px; height:16px; background:#a78bfa; border-radius:2px;"></div>
                    <h3 style="font-size:12px; font-weight:700; color:white; text-transform:uppercase;">Preview: {{ $previewCampaign->name }}</h3>
                </div>
                <button wire:click="closePreview" style="font-size:18px; color:rgba(255,255,255,0.3); background:none; border:none; cursor:pointer; line-height:1;">&times;</button>
            </div>

            @if($previewCampaign->channel === 'email')
                {{-- Email preview (iframe para isolar estilos) --}}
                <iframe srcdoc='{!! str_replace("'", "&#39;", $previewCampaign->html_content) !!}'
                        style="width:100%; max-width:600px; height:500px; border:none; border-radius:8px; background:white; display:block; margin:0 auto;">
                </iframe>
            @else
                {{-- WhatsApp preview --}}
                <div style="max-width:400px; margin:0 auto; background:#0b141a; border-radius:12px; padding:16px;">
                    @if($previewCampaign->image_path)
                    <div style="margin-bottom:8px;">
                        <img src="{{ \App\Services\MediaStorage::url($previewCampaign->image_path) }}" alt=""
                             style="width:100%; border-radius:8px;">
                    </div>
                    @endif
                    <div style="background:#202c33; border-radius:8px; padding:10px 14px;">
                        <p style="font-size:13px; color:#e9edef; line-height:1.6; white-space:pre-wrap;">{{ $previewCampaign->message }}</p>
                        <p style="font-size:10px; color:rgba(255,255,255,0.3); text-align:right; margin-top:4px;">Chatbot • agora</p>
                    </div>
                </div>
            @endif
        </div>
        @endif
        @endif
    </div>

    {{-- Modal: Nova Campanha --}}
    @if($showForm)
    <div style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px);" wire:click.self="$set('showForm', false)">
        <div style="background:#0f1320; border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:24px; width:100%; max-width:620px; max-height:90vh; overflow-y:auto;">
            <h2 style="font-size:15px; font-weight:700; color:white; margin-bottom:16px; font-family:Syne,sans-serif;">Nova Campanha</h2>
            <div style="display:flex; flex-direction:column; gap:12px;">
                {{-- Canal --}}
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; margin-bottom:6px; display:block;">Canal de disparo</label>
                    <div style="display:flex; gap:8px;">
                        <button type="button" wire:click="$set('channel', 'whatsapp')"
                                style="flex:1; padding:10px; border-radius:10px; cursor:pointer; text-align:center; font-size:12px; font-weight:600; transition:all 0.15s;
                                       background:{{ $channel === 'whatsapp' ? 'rgba(34,197,94,0.12)' : 'rgba(255,255,255,0.03)' }};
                                       border:1px solid {{ $channel === 'whatsapp' ? 'rgba(34,197,94,0.4)' : 'rgba(255,255,255,0.08)' }};
                                       color:{{ $channel === 'whatsapp' ? '#4ade80' : 'rgba(255,255,255,0.4)' }};">
                            WhatsApp
                        </button>
                        <button type="button" wire:click="$set('channel', 'email')"
                                style="flex:1; padding:10px; border-radius:10px; cursor:pointer; text-align:center; font-size:12px; font-weight:600; transition:all 0.15s;
                                       background:{{ $channel === 'email' ? 'rgba(59,130,246,0.12)' : 'rgba(255,255,255,0.03)' }};
                                       border:1px solid {{ $channel === 'email' ? 'rgba(59,130,246,0.4)' : 'rgba(255,255,255,0.08)' }};
                                       color:{{ $channel === 'email' ? '#60a5fa' : 'rgba(255,255,255,0.4)' }};">
                            Email {{ !$sendgridConfigured ? '(configure SendGrid nas Config. Globais)' : '' }}
                        </button>
                    </div>
                </div>

                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Nome da campanha *</label>
                    <input wire:model="name" type="text" placeholder="Ex: Promoção de Natal"
                           style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none;">
                    @error('name') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                </div>

                @if($channel === 'whatsapp')
                {{-- WhatsApp: imagem + mensagem --}}
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Imagem <span style="color:rgba(255,255,255,0.2); font-weight:400;">(opcional — enviada com a mensagem como legenda)</span></label>
                    <input wire:model="campaignImage" type="file" accept="image/*"
                           style="width:100%; margin-top:4px; padding:8px; font-size:12px; color:rgba(255,255,255,0.5); background:rgba(255,255,255,0.04); border:1px dashed rgba(34,197,94,0.3); border-radius:8px; cursor:pointer;">
                    @if($campaignImage)
                    <div style="margin-top:6px;">
                        <img src="{{ $campaignImage->temporaryUrl() }}" alt="Preview" style="max-height:120px; border-radius:8px; border:1px solid rgba(255,255,255,0.1);">
                    </div>
                    @endif
                    @error('campaignImage') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                </div>
                @if($isMeta && $metaTemplates->isNotEmpty())
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Template Meta WhatsApp</label>
                    <select wire:model.live="meta_template_name"
                            style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none;">
                        <option value="">Nenhum (texto livre)</option>
                        @foreach($metaTemplates as $tpl)
                            <option value="{{ $tpl->name }}">{{ $tpl->name }} ({{ $tpl->language }})</option>
                        @endforeach
                    </select>
                    <p style="font-size:9px; color:rgba(255,255,255,0.15); margin-top:3px;">Obrigatório para mensagens fora da janela de 24h. Sincronize em Meta WhatsApp > Templates.</p>
                </div>
                @if($meta_template_name)
                    @php
                        $selectedTpl = $metaTemplates->firstWhere('name', $meta_template_name);
                        $tplParams = [];
                        $tplBody = '';
                        if ($selectedTpl) {
                            $comps = json_decode($selectedTpl->components ?? '[]', true);
                            foreach ($comps as $comp) {
                                if ($comp['type'] === 'BODY') {
                                    $tplBody = $comp['text'] ?? '';
                                    preg_match_all('/\{\{(\d+)\}\}/', $tplBody, $pMatches);
                                    $tplParams = $pMatches[1] ?? [];
                                }
                            }
                            $examples = [];
                            foreach ($comps as $comp) {
                                if ($comp['type'] === 'BODY' && !empty($comp['example']['body_text'][0])) {
                                    $examples = $comp['example']['body_text'][0];
                                }
                            }
                        }
                    @endphp
                    <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:8px; padding:10px; margin-top:6px;">
                        <p style="font-size:10px; font-weight:600; color:rgba(255,255,255,0.3); margin-bottom:6px;">PREVIEW DO TEMPLATE:</p>
                        <p style="font-size:12px; color:rgba(255,255,255,0.6); line-height:1.6; white-space:pre-line;">{{ $tplBody }}</p>
                    </div>
                    <div style="margin-top:8px;">
                        <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Parâmetros do template *</label>
                        <p style="font-size:9px; color:rgba(255,255,255,0.2); margin:2px 0 6px;">Preencha um valor por linha. Linha 1 = @{{1}}, Linha 2 = @{{2}}, etc. Use <strong style="color:rgba(255,255,255,0.4);">{nome}</strong> para o nome do contato.</p>
                        <textarea wire:model="message" rows="{{ count($tplParams) + 1 }}"
                                  placeholder="{{ implode("\n", array_map(fn($i) => 'Parâmetro {{' . ($i) . '}}' . (isset($examples[$i-1]) ? ' — ex: ' . $examples[$i-1] : ''), $tplParams)) }}"
                                  style="width:100%; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; resize:vertical; font-family:monospace;"></textarea>
                        @error('message') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                    </div>
                @endif
                @endif
                @if(!$meta_template_name)
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">{{ $campaignImage ? 'Legenda da imagem *' : 'Contexto para IA *' }}</label>
                    <textarea wire:model="message" rows="5" placeholder="Escreva o contexto da mensagem. A IA vai gerar variações únicas para cada destinatário, evitando bloqueio por repetição.&#10;&#10;Ex: Olá {nome}! Temos uma oferta especial de máquinas para panificação com 20% de desconto até sexta-feira..."
                              style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; resize:vertical;"></textarea>
                    @error('message') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                    <p style="font-size:10px; color:rgba(139,92,246,0.7); margin-top:4px;">A IA gera uma mensagem diferente para cada lead com base neste contexto. Use {nome} para personalizar.</p>
                </div>
                @endif
                @else
                {{-- Email: campos simples --}}
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Assunto do email *</label>
                    <input wire:model="subject" type="text" placeholder="Ex: Novidades especiais para você!"
                           style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none;">
                    @error('subject') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                </div>

                {{-- Cor do header --}}
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; margin-bottom:6px; display:block;">Cor do header</label>
                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                        @foreach(['#2563eb' => 'Azul', '#111827' => 'Escuro', '#dc2626' => 'Vermelho', '#16a34a' => 'Verde', '#9333ea' => 'Roxo', '#ea580c' => 'Laranja', '#0891b2' => 'Ciano', '#be185d' => 'Rosa'] as $hex => $colorName)
                        <button type="button" wire:click="$set('emailColor', '{{ $hex }}')"
                                style="width:28px; height:28px; border-radius:50%; background:{{ $hex }}; border:2px solid {{ $emailColor === $hex ? 'white' : 'transparent' }}; cursor:pointer; transition:all 0.15s;"
                                title="{{ $colorName }}"></button>
                        @endforeach
                    </div>
                </div>

                {{-- Logo --}}
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Logo da empresa <span style="color:rgba(255,255,255,0.2); font-weight:400;">(opcional — aparece no topo do email)</span></label>
                    <input wire:model="emailLogo" type="file" accept="image/*"
                           style="width:100%; margin-top:4px; padding:8px; font-size:12px; color:rgba(255,255,255,0.5); background:rgba(255,255,255,0.04); border:1px dashed rgba(59,130,246,0.3); border-radius:8px; cursor:pointer;">
                    @if($emailLogo)
                    <div style="margin-top:4px; padding:8px; background:rgba(255,255,255,0.04); border-radius:6px; text-align:center;">
                        <img src="{{ $emailLogo->temporaryUrl() }}" alt="Logo" style="max-height:50px;">
                    </div>
                    @elseif($existingLogoUrl)
                    <div style="margin-top:4px; padding:8px; background:rgba(255,255,255,0.04); border-radius:6px; text-align:center;">
                        <img src="{{ $existingLogoUrl }}" alt="Logo atual" style="max-height:50px;">
                        <p style="font-size:9px; color:rgba(255,255,255,0.2); margin-top:4px;">Logo atual (envie novo para substituir)</p>
                    </div>
                    @endif
                </div>

                {{-- Imagem principal --}}
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Imagem <span style="color:rgba(255,255,255,0.2); font-weight:400;">(opcional — aparece abaixo do header)</span></label>
                    <input wire:model="emailImage" type="file" accept="image/*"
                           style="width:100%; margin-top:4px; padding:8px; font-size:12px; color:rgba(255,255,255,0.5); background:rgba(255,255,255,0.04); border:1px dashed rgba(59,130,246,0.3); border-radius:8px; cursor:pointer;">
                    @if($emailImage)
                    <div style="margin-top:4px;">
                        <img src="{{ $emailImage->temporaryUrl() }}" alt="Preview" style="max-height:120px; border-radius:8px; border:1px solid rgba(255,255,255,0.1);">
                    </div>
                    @elseif($existingImageUrl && $channel === 'email')
                    <div style="margin-top:4px;">
                        <img src="{{ $existingImageUrl }}" alt="Imagem atual" style="max-height:120px; border-radius:8px; border:1px solid rgba(255,255,255,0.1);">
                        <p style="font-size:9px; color:rgba(255,255,255,0.2); margin-top:4px;">Imagem atual (envie nova para substituir)</p>
                    </div>
                    @endif
                </div>

                {{-- Mensagem --}}
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Mensagem * <span style="color:rgba(255,255,255,0.2); font-weight:400;">(use {nome} para personalizar)</span></label>
                    <textarea wire:model="message" rows="5" placeholder="Olá {nome}! Temos uma novidade especial para você..."
                              style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; resize:vertical;"></textarea>
                    @error('message') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                    <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:4px;">Variáveis: {nome}, {email}. Leads sem email serão ignorados ({{ $emailLeadCount }} com email).</p>
                </div>
                @endif
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Agendar disparo (opcional)</label>
                        <input wire:model="scheduled_at" type="datetime-local"
                               style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; color-scheme:dark;">
                        <p style="font-size:9px; color:rgba(255,255,255,0.15); margin-top:2px;">Deixe vazio para disparar imediatamente</p>
                    </div>
                    <div>
                        <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Intervalo entre envios (seg)</label>
                        <input wire:model="interval_seconds" type="number" min="3" max="120"
                               style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none;">
                    </div>
                    <div>
                        <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Destinatários</label>
                        <select wire:model.live="recipientMode"
                                style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white;">
                            <option value="all">Todos os leads ativos ({{ $activeLeadCount }})</option>
                            <option value="tag">Filtrar por tag</option>
                            <option value="manual">Selecionar manualmente</option>
                        </select>
                    </div>
                </div>
                @if($recipientMode === 'tag')
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Tag</label>
                    <select wire:model.live="filterTag"
                            style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white;">
                        <option value="">Selecione...</option>
                        @foreach($allTags as $tag)
                        <option value="{{ $tag }}">{{ $tag }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if($recipientMode === 'manual')
                <div style="margin-top:8px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                        <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Selecionar destinatários</label>
                        @if(count($manualRecipientIds) > 0)
                        <span style="font-size:11px; font-weight:700; color:#b2ff00;">{{ count($manualRecipientIds) }} selecionado(s)</span>
                        @endif
                    </div>
                    <input wire:model.live.debounce.300ms="contactSearch" type="text" placeholder="Buscar por nome ou telefone..."
                           style="width:100%; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; margin-bottom:6px;">
                    <div style="max-height:200px; overflow-y:auto; border:1px solid rgba(255,255,255,0.06); border-radius:8px; background:rgba(0,0,0,0.2);">
                        @foreach($allContacts as $contact)
                        @php $isSelected = in_array($contact->id, $manualRecipientIds); @endphp
                        <div wire:click="toggleRecipient({{ $contact->id }})"
                             style="display:flex; align-items:center; gap:10px; padding:8px 12px; cursor:pointer; border-bottom:1px solid rgba(255,255,255,0.04); transition:background 0.1s; {{ $isSelected ? 'background:rgba(178,255,0,0.08);' : '' }}"
                             onmouseover="this.style.background='{{ $isSelected ? 'rgba(178,255,0,0.12)' : 'rgba(255,255,255,0.04)' }}'"
                             onmouseout="this.style.background='{{ $isSelected ? 'rgba(178,255,0,0.08)' : 'transparent' }}'">
                            <div style="width:18px; height:18px; border-radius:4px; border:2px solid {{ $isSelected ? '#b2ff00' : 'rgba(255,255,255,0.2)' }}; display:flex; align-items:center; justify-content:center; flex-shrink:0; {{ $isSelected ? 'background:#b2ff00;' : '' }}">
                                @if($isSelected)
                                <svg width="10" height="10" fill="none" stroke="#111" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                                @endif
                            </div>
                            <div style="flex:1; min-width:0;">
                                <p style="font-size:12px; color:white; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $contact->name ?? 'Sem nome' }}</p>
                                <p style="font-size:10px; color:rgba(255,255,255,0.3);">{{ $contact->phone }}</p>
                            </div>
                        </div>
                        @endforeach
                        @if($allContacts->isEmpty())
                        <p style="padding:16px; text-align:center; font-size:11px; color:rgba(255,255,255,0.3);">Nenhum contato encontrado</p>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            <div style="display:flex; gap:10px; margin-top:18px;">
                <button wire:click="save" style="flex:1; padding:8px; font-size:12px; font-weight:700; color:#111; background:linear-gradient(135deg, #b2ff00, #8fcc00); border:none; border-radius:8px; cursor:pointer;">{{ $editingId ? 'Atualizar Campanha' : 'Criar Campanha' }}</button>
                <button wire:click="$set('showForm', false)" style="padding:8px 16px; font-size:12px; color:rgba(255,255,255,0.4); background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; cursor:pointer;">Cancelar</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal: Histórico de runs --}}
    @if($viewingCampaign)
    <div style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px);" wire:click.self="closeRuns">
        <div style="background:#0f1320; border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:24px; width:100%; max-width:600px; max-height:80vh; overflow-y:auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h2 style="font-size:15px; font-weight:700; color:white; font-family:Syne,sans-serif;">{{ $viewingCampaign->name }} — Histórico</h2>
                <button wire:click="closeRuns" style="color:rgba(255,255,255,0.3); background:none; border:none; cursor:pointer; font-size:18px;">&times;</button>
            </div>

            @if($runs->isEmpty())
            <p style="font-size:12px; color:rgba(255,255,255,0.3); text-align:center; padding:20px;">Nenhum disparo realizado.</p>
            @else
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach($runs as $run)
                <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:10px; padding:12px 16px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <span style="font-size:11px; color:rgba(255,255,255,0.5);">{{ $run->created_at->format('d/m/Y H:i') }}</span>
                            @php
                                $rc = match($run->status) {
                                    'sending'   => 'color:#60a5fa',
                                    'completed' => 'color:#4ade80',
                                    'failed'    => 'color:#f87171',
                                    default     => 'color:#9ca3af',
                                };
                            @endphp
                            <span style="font-size:10px; font-weight:700; margin-left:8px; {{ $rc }}">{{ strtoupper($run->status) }}</span>
                        </div>
                        <div style="display:flex; gap:12px; font-size:11px;">
                            <span style="color:#4ade80;">{{ $run->sent_count }} enviados</span>
                            <span style="color:#f87171;">{{ $run->failed_count }} falhas</span>
                            <span style="color:rgba(255,255,255,0.3);">/ {{ $run->total_recipients }}</span>
                        </div>
                    </div>
                    @if($run->completed_at)
                    <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:4px;">Concluído em {{ $run->completed_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Modal: Relatório de Desempenho --}}
    @if($showReport && $reportData)
    <div style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px);" wire:click.self="closeReport">
        <div style="background:#0f1320; border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:24px; width:100%; max-width:800px; max-height:85vh; overflow-y:auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <div>
                    <h2 style="font-size:16px; font-weight:800; color:white; font-family:Syne,sans-serif;">Relatório de Disparos</h2>
                    <p style="font-size:11px; color:rgba(255,255,255,0.3); margin-top:2px;">
                        {{ $reportCampaignId ? 'Campanha específica' : 'Visão geral de todas as campanhas' }}
                    </p>
                </div>
                <button wire:click="closeReport" style="color:rgba(255,255,255,0.3); background:none; border:none; cursor:pointer; font-size:20px;">&times;</button>
            </div>

            {{-- Cards de métricas --}}
            <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-bottom:12px;">
                <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:12px; padding:16px; text-align:center;">
                    <p style="font-size:10px; color:rgba(255,255,255,0.3); text-transform:uppercase; font-weight:700; letter-spacing:0.05em;">Destinatários</p>
                    <p style="font-size:24px; font-weight:800; color:white; margin-top:4px;">{{ number_format($reportData['totalRecipients'], 0, ',', '.') }}</p>
                </div>
                <div style="background:rgba(34,197,94,0.06); border:1px solid rgba(34,197,94,0.15); border-radius:12px; padding:16px; text-align:center;">
                    <p style="font-size:10px; color:rgba(34,197,94,0.7); text-transform:uppercase; font-weight:700; letter-spacing:0.05em;">Enviados</p>
                    <p style="font-size:24px; font-weight:800; color:#4ade80; margin-top:4px;">{{ number_format($reportData['totalSent'], 0, ',', '.') }}</p>
                </div>
                <div style="background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.15); border-radius:12px; padding:16px; text-align:center;">
                    <p style="font-size:10px; color:rgba(239,68,68,0.7); text-transform:uppercase; font-weight:700; letter-spacing:0.05em;">Falhas</p>
                    <p style="font-size:24px; font-weight:800; color:#f87171; margin-top:4px;">{{ number_format($reportData['totalFailed'], 0, ',', '.') }}</p>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-bottom:20px;">
                <div style="background:rgba(96,165,250,0.06); border:1px solid rgba(96,165,250,0.15); border-radius:12px; padding:16px; text-align:center;">
                    <p style="font-size:10px; color:rgba(96,165,250,0.7); text-transform:uppercase; font-weight:700; letter-spacing:0.05em;">Entregues</p>
                    <p style="font-size:24px; font-weight:800; color:#60a5fa; margin-top:4px;">{{ number_format($reportData['totalDelivered'], 0, ',', '.') }}</p>
                    <p style="font-size:10px; color:rgba(96,165,250,0.5); margin-top:2px;">{{ $reportData['totalSent'] > 0 ? round($reportData['totalDelivered'] / $reportData['totalSent'] * 100, 1) : 0 }}% dos enviados</p>
                </div>
                <div style="background:rgba(168,85,247,0.06); border:1px solid rgba(168,85,247,0.15); border-radius:12px; padding:16px; text-align:center;">
                    <p style="font-size:10px; color:rgba(168,85,247,0.7); text-transform:uppercase; font-weight:700; letter-spacing:0.05em;">Lidos / Abertos</p>
                    <p style="font-size:24px; font-weight:800; color:#a78bfa; margin-top:4px;">{{ number_format($reportData['totalRead'], 0, ',', '.') }}</p>
                    <p style="font-size:10px; color:rgba(168,85,247,0.5); margin-top:2px;">{{ $reportData['totalSent'] > 0 ? round($reportData['totalRead'] / $reportData['totalSent'] * 100, 1) : 0 }}% dos enviados</p>
                </div>
                <div style="background:rgba(251,191,36,0.06); border:1px solid rgba(251,191,36,0.15); border-radius:12px; padding:16px; text-align:center;">
                    <p style="font-size:10px; color:rgba(251,191,36,0.7); text-transform:uppercase; font-weight:700; letter-spacing:0.05em;">Sem leitura</p>
                    @php $unread = $reportData['totalDelivered'] - $reportData['totalRead']; @endphp
                    <p style="font-size:24px; font-weight:800; color:#fbbf24; margin-top:4px;">{{ number_format(max(0, $unread), 0, ',', '.') }}</p>
                    <p style="font-size:10px; color:rgba(251,191,36,0.5); margin-top:2px;">entregue mas não lido</p>
                </div>
            </div>

            {{-- Detalhes por campanha --}}
            <div style="margin-bottom:20px;">
                <h3 style="font-size:12px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; margin-bottom:10px;">Campanhas</h3>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    @foreach($reportData['campaignDetails'] as $cd)
                    <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:10px; padding:12px 16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="font-size:13px; font-weight:700; color:white;">{{ $cd['name'] }}</span>
                                <span style="font-size:9px; font-weight:700; padding:2px 6px; border-radius:4px; {{ $cd['channel'] === 'whatsapp' ? 'background:rgba(34,197,94,0.15); color:#4ade80;' : 'background:rgba(96,165,250,0.15); color:#60a5fa;' }}">{{ strtoupper($cd['channel']) }}</span>
                                @if($cd['filter_tag'])
                                <span style="font-size:9px; padding:2px 6px; border-radius:4px; background:rgba(168,85,247,0.15); color:#a78bfa;">TAG: {{ $cd['filter_tag'] }}</span>
                                @endif
                            </div>
                            @if(!$reportCampaignId)
                            <button wire:click="openReport({{ $cd['id'] }})" style="font-size:10px; color:#60a5fa; background:none; border:none; cursor:pointer; text-decoration:underline;">Detalhar</button>
                            @endif
                        </div>
                        <div style="display:flex; gap:12px; font-size:11px; flex-wrap:wrap;">
                            <span style="color:rgba(255,255,255,0.5);">{{ $cd['total'] }} dest.</span>
                            <span style="color:#4ade80;">{{ $cd['sent'] }} env.</span>
                            <span style="color:#60a5fa;">{{ $cd['delivered'] }} entreg. ({{ $cd['delivery_rate'] }}%)</span>
                            <span style="color:#a78bfa;">{{ $cd['read'] }} lidos ({{ $cd['read_rate'] }}%)</span>
                            <span style="color:#f87171;">{{ $cd['failed'] }} falhas</span>
                            @if($cd['duration'])
                            <span style="color:rgba(255,255,255,0.3);">{{ $cd['duration'] }}</span>
                            @endif
                        </div>
                        <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:4px;">
                            Criada em {{ $cd['created_at']->format('d/m/Y H:i') }}
                            @if($cd['completed_at']) — Concluída em {{ $cd['completed_at']->format('d/m/Y H:i') }} @endif
                        </p>
                    </div>
                    @endforeach

                    @if(collect($reportData['campaignDetails'])->isEmpty())
                    <p style="font-size:12px; color:rgba(255,255,255,0.3); text-align:center; padding:20px;">Nenhuma campanha com disparos realizados.</p>
                    @endif
                </div>
            </div>

            {{-- Erros mais comuns --}}
            @if($reportData['topErrors']->isNotEmpty())
            <div style="margin-bottom:20px;">
                <h3 style="font-size:12px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; margin-bottom:10px;">Erros mais frequentes</h3>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    @foreach($reportData['topErrors'] as $err)
                    <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(239,68,68,0.05); border:1px solid rgba(239,68,68,0.1); border-radius:8px; padding:8px 12px;">
                        <span style="font-size:11px; color:rgba(255,255,255,0.5); flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ \Illuminate\Support\Str::limit($err->error, 80) }}</span>
                        <span style="font-size:11px; font-weight:700; color:#f87171; flex-shrink:0; margin-left:12px;">{{ $err->total }}x</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Lista de falhas (campanha específica) --}}
            @if($reportCampaignId && $reportData['failedRecipients']->isNotEmpty())
            <div>
                <h3 style="font-size:12px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; margin-bottom:10px;">Destinatários com falha</h3>
                <div style="max-height:200px; overflow-y:auto; border:1px solid rgba(255,255,255,0.06); border-radius:10px;">
                    @foreach($reportData['failedRecipients'] as $fr)
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; border-bottom:1px solid rgba(255,255,255,0.04);">
                        <div>
                            <span style="font-size:12px; color:white;">{{ $fr->broadcastContact?->name ?? 'Sem nome' }}</span>
                            <span style="font-size:10px; color:rgba(255,255,255,0.3); margin-left:8px;">{{ $fr->phone }}</span>
                        </div>
                        <span style="font-size:10px; color:#f87171; max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $fr->error }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Voltar para visão geral --}}
            @if($reportCampaignId)
            <div style="margin-top:16px; text-align:center;">
                <button wire:click="openReport" style="font-size:11px; color:#60a5fa; background:none; border:none; cursor:pointer; text-decoration:underline;">← Ver todas as campanhas</button>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
