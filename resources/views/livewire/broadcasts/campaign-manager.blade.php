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
        <button wire:click="openCreate"
                style="display:flex; align-items:center; gap:6px; padding:6px 14px; font-size:11px; font-weight:600; color:#b2ff00; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:8px; cursor:pointer;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nova Campanha
        </button>
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
                            <div style="display:flex; align-items:center; gap:6px;">
                                <p style="font-size:12px; font-weight:600; color:white;">{{ $campaign->name }}</p>
                                <span style="font-size:9px; font-weight:700; padding:1px 6px; border-radius:20px; background:{{ ($campaign->channel ?? 'whatsapp') === 'email' ? 'rgba(59,130,246,0.12)' : 'rgba(34,197,94,0.12)' }}; color:{{ ($campaign->channel ?? 'whatsapp') === 'email' ? '#60a5fa' : '#4ade80' }}; border:1px solid {{ ($campaign->channel ?? 'whatsapp') === 'email' ? 'rgba(59,130,246,0.2)' : 'rgba(34,197,94,0.2)' }};">
                                    {{ ($campaign->channel ?? 'whatsapp') === 'email' ? 'EMAIL' : 'WHATSAPP' }}
                                </span>
                            </div>
                            <p style="font-size:10px; color:rgba(255,255,255,0.3); margin-top:2px;">{{ Str::limit($campaign->message ?? $campaign->subject, 60) }}</p>
                        </td>
                        <td style="padding:10px 16px; text-align:center;">
                            @php
                                $statusColors = [
                                    'draft'     => ['bg' => 'rgba(107,114,128,0.12)', 'color' => '#9ca3af', 'border' => 'rgba(107,114,128,0.2)', 'label' => 'Rascunho'],
                                    'sending'   => ['bg' => 'rgba(59,130,246,0.12)', 'color' => '#60a5fa', 'border' => 'rgba(59,130,246,0.2)', 'label' => 'Enviando'],
                                    'completed' => ['bg' => 'rgba(34,197,94,0.12)', 'color' => '#4ade80', 'border' => 'rgba(34,197,94,0.2)', 'label' => 'Concluída'],
                                    'failed'    => ['bg' => 'rgba(239,68,68,0.12)', 'color' => '#f87171', 'border' => 'rgba(239,68,68,0.2)', 'label' => 'Falhou'],
                                ];
                                $s = $statusColors[$campaign->status] ?? $statusColors['draft'];
                            @endphp
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:{{ $s['bg'] }}; color:{{ $s['color'] }}; border:1px solid {{ $s['border'] }};">{{ $s['label'] }}</span>
                        </td>
                        <td style="padding:10px 16px; text-align:center; font-size:12px; color:#4ade80; font-weight:600;">{{ $campaign->sent_count ?? 0 }}</td>
                        <td style="padding:10px 16px; text-align:center; font-size:12px; color:#f87171; font-weight:600;">{{ $campaign->failed_count ?? 0 }}</td>
                        <td style="padding:10px 16px; font-size:11px; color:rgba(255,255,255,0.4);">{{ $campaign->created_at->format('d/m/Y H:i') }}</td>
                        <td style="padding:10px 16px; text-align:right;">
                            <div style="display:flex; justify-content:flex-end; gap:6px;">
                                @if($campaign->status === 'draft' || $campaign->status === 'completed')
                                <button wire:click="send({{ $campaign->id }})" wire:confirm="Disparar mensagem para todos os leads ativos?"
                                        style="padding:4px 10px; font-size:11px; color:#b2ff00; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:6px; cursor:pointer;">
                                    {{ $campaign->status === 'completed' ? 'Re-disparar' : 'Disparar' }}
                                </button>
                                @endif
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
    </div>

    {{-- Modal: Nova Campanha --}}
    @if($showForm)
    <div style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px);" wire:click.self="$set('showForm', false)">
        <div style="background:#0f1320; border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:24px; width:100%; max-width:520px;">
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
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">{{ $campaignImage ? 'Legenda da imagem *' : 'Mensagem *' }} <span style="color:rgba(255,255,255,0.2); font-weight:400;">(use {nome} para o nome do lead)</span></label>
                    <textarea wire:model="message" rows="5" placeholder="Olá {nome}! Temos uma oferta especial..."
                              style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; resize:vertical;"></textarea>
                    @error('message') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                </div>
                @else
                {{-- Email: assunto + HTML --}}
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Assunto do email *</label>
                    <input wire:model="subject" type="text" placeholder="Ex: Novidades especiais para você, {nome}!"
                           style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none;">
                    @error('subject') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                </div>
                {{-- Templates pré-prontos --}}
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; margin-bottom:6px; display:block;">Template (opcional — clique para usar)</label>
                    <div style="display:flex; gap:6px; flex-wrap:wrap;" x-data>
                        @php
                        $templates = [
                            'Promoção' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;"><div style="background:#2563eb;padding:30px;text-align:center;"><h1 style="color:#ffffff;margin:0;font-size:24px;">🎉 Oferta Especial!</h1></div><div style="padding:30px;"><p style="font-size:16px;color:#333;">Olá <strong>{nome}</strong>!</p><p style="font-size:14px;color:#666;line-height:1.6;">Temos uma promoção exclusiva esperando por você. Não perca essa oportunidade!</p><div style="text-align:center;margin:25px 0;"><a href="#" style="background:#2563eb;color:#ffffff;padding:12px 30px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px;">Ver Promoção</a></div><p style="font-size:12px;color:#999;text-align:center;">Se não deseja mais receber, ignore este email.</p></div></div>',
                            'Newsletter' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;"><div style="background:#111827;padding:25px;text-align:center;"><h1 style="color:#b2ff00;margin:0;font-size:22px;">📰 Novidades da Semana</h1></div><div style="padding:30px;"><p style="font-size:16px;color:#333;">Olá <strong>{nome}</strong>!</p><p style="font-size:14px;color:#666;line-height:1.6;">Confira as principais novidades desta semana:</p><ul style="font-size:14px;color:#666;line-height:2;"><li>Novidade 1 — Descrição breve</li><li>Novidade 2 — Descrição breve</li><li>Novidade 3 — Descrição breve</li></ul><hr style="border:none;border-top:1px solid #eee;margin:20px 0;"><div style="text-align:center;"><p style="font-size:12px;color:#999;">Siga nossas redes sociais</p><p style="font-size:20px;">📱 📸 💼</p></div></div></div>',
                            'Boas-vindas' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;"><div style="background:linear-gradient(135deg,#8b5cf6,#6366f1);padding:40px;text-align:center;"><h1 style="color:#ffffff;margin:0;font-size:28px;">Bem-vindo(a)! 👋</h1><p style="color:rgba(255,255,255,0.8);margin-top:10px;font-size:14px;">Estamos felizes em ter você conosco</p></div><div style="padding:30px;"><p style="font-size:16px;color:#333;">Olá <strong>{nome}</strong>!</p><p style="font-size:14px;color:#666;line-height:1.6;">Obrigado por se cadastrar. A partir de agora você receberá nossas melhores ofertas e novidades diretamente no seu email.</p><div style="text-align:center;margin:25px 0;"><a href="#" style="background:#8b5cf6;color:#ffffff;padding:12px 30px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px;">Conhecer Nossos Produtos</a></div><div style="text-align:center;margin-top:20px;"><p style="font-size:12px;color:#999;">Nos siga nas redes sociais</p><p><a href="#" style="color:#6366f1;text-decoration:none;margin:0 8px;">Facebook</a> <a href="#" style="color:#6366f1;text-decoration:none;margin:0 8px;">Instagram</a> <a href="#" style="color:#6366f1;text-decoration:none;margin:0 8px;">LinkedIn</a></p></div></div></div>',
                        ];
                        @endphp
                        @foreach($templates as $tplName => $tplHtml)
                        <button type="button"
                                @click="$wire.set('htmlContent', @js($tplHtml))"
                                style="padding:6px 14px; font-size:11px; font-weight:600; border-radius:8px; cursor:pointer; transition:all 0.15s; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); color:rgba(255,255,255,0.5);"
                                onmouseover="this.style.background='rgba(59,130,246,0.1)'; this.style.borderColor='rgba(59,130,246,0.3)'; this.style.color='#60a5fa'"
                                onmouseout="this.style.background='rgba(255,255,255,0.04)'; this.style.borderColor='rgba(255,255,255,0.08)'; this.style.color='rgba(255,255,255,0.5)'">
                            {{ $tplName }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Editor HTML --}}
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Conteúdo do email *</label>

                    {{-- Toolbar --}}
                    <div style="display:flex; gap:4px; flex-wrap:wrap; margin-top:4px; margin-bottom:4px;" x-data>
                        <button type="button" @click="$wire.set('htmlContent', ($wire.htmlContent||'') + '<h1 style=\'color:#333;font-family:Arial,sans-serif;\'>Título</h1>')"
                                style="padding:4px 8px; font-size:10px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:4px; color:rgba(255,255,255,0.5); cursor:pointer;" title="Título">H1</button>
                        <button type="button" @click="$wire.set('htmlContent', ($wire.htmlContent||'') + '<h2 style=\'color:#333;font-family:Arial,sans-serif;\'>Subtítulo</h2>')"
                                style="padding:4px 8px; font-size:10px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:4px; color:rgba(255,255,255,0.5); cursor:pointer;" title="Subtítulo">H2</button>
                        <button type="button" @click="$wire.set('htmlContent', ($wire.htmlContent||'') + '<p style=\'font-size:14px;color:#666;line-height:1.6;font-family:Arial,sans-serif;\'>Seu texto aqui...</p>')"
                                style="padding:4px 8px; font-size:10px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:4px; color:rgba(255,255,255,0.5); cursor:pointer;" title="Parágrafo">¶ Texto</button>
                        <button type="button" @click="$wire.set('htmlContent', ($wire.htmlContent||'') + '<div style=\'text-align:center;margin:20px 0;\'><a href=\'#\' style=\'background:#2563eb;color:#ffffff;padding:12px 30px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px;\'>Clique Aqui</a></div>')"
                                style="padding:4px 8px; font-size:10px; background:rgba(59,130,246,0.1); border:1px solid rgba(59,130,246,0.2); border-radius:4px; color:#60a5fa; cursor:pointer;" title="Botão CTA">🔘 Botão</button>
                        <button type="button" @click="$wire.set('htmlContent', ($wire.htmlContent||'') + '<div style=\'text-align:center;margin:20px 0;\'><img src=\'URL_DA_IMAGEM\' alt=\'Imagem\' style=\'max-width:100%;border-radius:8px;\'/></div>')"
                                style="padding:4px 8px; font-size:10px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:4px; color:rgba(255,255,255,0.5); cursor:pointer;" title="Imagem">🖼 Imagem</button>
                        <button type="button" @click="$wire.set('htmlContent', ($wire.htmlContent||'') + '<hr style=\'border:none;border-top:1px solid #eee;margin:20px 0;\'/>')"
                                style="padding:4px 8px; font-size:10px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:4px; color:rgba(255,255,255,0.5); cursor:pointer;" title="Separador">— Linha</button>
                        <button type="button" @click="$wire.set('htmlContent', ($wire.htmlContent||'') + '<div style=\'text-align:center;margin:20px 0;\'><p style=\'font-size:12px;color:#999;\'>Siga nossas redes</p><p><a href=\'#\' style=\'color:#1877f2;text-decoration:none;margin:0 8px;font-size:20px;\'>📘</a><a href=\'#\' style=\'color:#e4405f;text-decoration:none;margin:0 8px;font-size:20px;\'>📸</a><a href=\'#\' style=\'color:#0a66c2;text-decoration:none;margin:0 8px;font-size:20px;\'>💼</a><a href=\'#\' style=\'color:#25d366;text-decoration:none;margin:0 8px;font-size:20px;\'>📱</a></p></div>')"
                                style="padding:4px 8px; font-size:10px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:4px; color:rgba(255,255,255,0.5); cursor:pointer;" title="Redes Sociais">🌐 Redes</button>
                        <button type="button" @click="$wire.set('htmlContent', ($wire.htmlContent||'') + '<p style=\'font-size:12px;color:#999;text-align:center;\'>© 2026 Sua Empresa. Todos os direitos reservados.<br>Para cancelar o recebimento, <a href=\'#\' style=\'color:#999;\'>clique aqui</a>.</p>')"
                                style="padding:4px 8px; font-size:10px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:4px; color:rgba(255,255,255,0.5); cursor:pointer;" title="Rodapé">📄 Rodapé</button>
                    </div>

                    <textarea wire:model="htmlContent" rows="12" placeholder="Cole ou construa o HTML do email aqui..."
                              style="width:100%; padding:8px 12px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; resize:vertical; font-family:monospace; line-height:1.5;"></textarea>
                    @error('htmlContent') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                    <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:4px;">Variáveis: {nome}, {email}. Leads sem email serão ignorados ({{ $emailLeadCount }} com email).</p>
                </div>

                {{-- Preview --}}
                @if($htmlContent)
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; margin-bottom:6px; display:block;">Preview do email</label>
                    <div style="background:white; border-radius:8px; padding:16px; max-height:300px; overflow-y:auto;">
                        {!! $htmlContent !!}
                    </div>
                </div>
                @endif
                @endif
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
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
                        </select>
                    </div>
                </div>
                @if($recipientMode === 'tag')
                <div>
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase;">Tag</label>
                    <select wire:model="filterTag"
                            style="width:100%; margin-top:4px; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white;">
                        <option value="">Selecione...</option>
                        @foreach($allTags as $tag)
                        <option value="{{ $tag }}">{{ $tag }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            <div style="display:flex; gap:10px; margin-top:18px;">
                <button wire:click="save" style="flex:1; padding:8px; font-size:12px; font-weight:700; color:#111; background:linear-gradient(135deg, #b2ff00, #8fcc00); border:none; border-radius:8px; cursor:pointer;">Criar Campanha</button>
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
</div>
