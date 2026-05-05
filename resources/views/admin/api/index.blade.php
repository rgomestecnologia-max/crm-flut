<x-layouts.app>
    <x-slot:title>Automação — {{ config('app.name') }}</x-slot:title>

    <div style="height:60px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; padding:0 24px; gap:16px; flex-shrink:0; background:rgba(11,15,28,0.5); backdrop-filter:blur(6px);">
        <svg class="w-5 h-5 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        <h1 style="font-size:15px; font-weight:800; color:white; font-family:Syne,sans-serif; letter-spacing:-0.02em;">Automação</h1>
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-6">
        <div class="max-w-3xl mx-auto space-y-6">

            {{-- ══════════════════════════════════════════ --}}
            {{-- AUTOMAÇÕES DE MENSAGEM                    --}}
            {{-- ══════════════════════════════════════════ --}}
            <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6">
                <livewire:admin.automation-manager />
            </div>

            {{-- ══════════════════════════════════════════ --}}
            {{-- API DE INTEGRAÇÃO (mantida)               --}}
            {{-- ══════════════════════════════════════════ --}}
            <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6">
                <div class="flex items-center gap-2 mb-5">
                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    <h2 class="text-base font-semibold text-white">API de Integração</h2>
                </div>
                <livewire:admin.api-token-manager />
            </div>

            @if(app(\App\Services\CurrentCompany::class)->id() === 2)
            {{-- ══════════════════════════════════════════ --}}
            {{-- REGRAS DE NEGÓCIO — PARE SEGURO            --}}
            {{-- ══════════════════════════════════════════ --}}
            <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                    <svg width="18" height="18" fill="none" stroke="#fbbf24" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                    <h2 style="font-size:15px; font-weight:700; color:white; font-family:'Syne',sans-serif;">Regras de Negócio Ativas</h2>
                </div>
                <p style="font-size:11px; color:rgba(255,255,255,0.3); margin-bottom:16px;">Regras customizadas para a Pare Seguro Estacionamento.</p>

                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Lead do site via API + delay 5 min + Saudação via IA</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            Lead chega via <strong style="color:rgba(255,255,255,0.6);">POST /api/leads</strong>, card criado em <strong style="color:rgba(255,255,255,0.6);">Vendas → Novo</strong>.
                            Aguarda <strong style="color:rgba(255,255,255,0.6);">5 minutos</strong> antes de enviar. A mensagem é gerada pela <strong style="color:rgba(255,255,255,0.6);">IA com variações</strong> (nunca repete a mesma).
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Follow-up (lembrete) após 2 horas</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            Se o cliente <strong style="color:rgba(255,255,255,0.6);">não responder</strong> a primeira mensagem em 2 horas, envia lembrete automático via IA.
                            Cancela se respondeu, se reservou, ou se a conversa foi encerrada.
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">IA atende apenas clientes do site</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            A IA só responde clientes que vieram pela <strong style="color:rgba(255,255,255,0.6);">automação do site</strong>.
                            Clientes diretos vão para <strong style="color:rgba(255,255,255,0.6);">Aguardando</strong> (atendimento humano).
                            IA <strong style="color:rgba(255,255,255,0.6);">não responde em grupos</strong>.
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Mover Novo → Em negociação ao responder</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            Quando o cliente responde e o card está em <strong style="color:rgba(255,255,255,0.6);">Novo</strong>,
                            move para <strong style="color:rgba(255,255,255,0.6);">Em negociação</strong>.
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Reserva via site → move card + confirma + encerra IA</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            Quando reserva chega via API (tipo_vaga diferente de simulação): move card para <strong style="color:rgba(255,255,255,0.6);">Reservado</strong>,
                            remove duplicatas do dia, envia <strong style="color:rgba(255,255,255,0.6);">confirmação via IA</strong> e <strong style="color:rgba(255,255,255,0.6);">encerra a conversa</strong>.
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Anti-duplicata por dia</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            <strong style="color:rgba(255,255,255,0.6);">Mesmo dia:</strong> simulação repetida atualiza card existente. Reserva move o card mais recente.
                            <strong style="color:rgba(255,255,255,0.6);">Dias diferentes:</strong> cria cards novos (jornadas independentes).
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Handoff automático + Lead salvo</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            IA transfere para humano quando não sabe. Todo lead via API é salvo em <strong style="color:rgba(255,255,255,0.6);">Leads</strong> com tag <strong style="color:rgba(255,255,255,0.6);">site</strong>.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            {{-- ══════════════════════════════════════════ --}}
            {{-- ROTEAMENTO POR DDD (exibido se houver regras) --}}
            {{-- ══════════════════════════════════════════ --}}
            @if(\App\Models\DddRoutingRule::where('company_id', app(\App\Services\CurrentCompany::class)->id())->exists())
            <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6">
                <livewire:admin.ddd-routing-manager />
            </div>
            @endif

            @if(app(\App\Services\CurrentCompany::class)->id() === 9)
            {{-- ══════════════════════════════════════════ --}}
            {{-- REGRAS DE NEGÓCIO — ALQUIMA               --}}
            {{-- ══════════════════════════════════════════ --}}
            <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                    <svg width="18" height="18" fill="none" stroke="#fbbf24" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                    <h2 style="font-size:15px; font-weight:700; color:white; font-family:'Syne',sans-serif;">Regras de Negócio Ativas</h2>
                </div>
                <p style="font-size:11px; color:rgba(255,255,255,0.3); margin-bottom:16px;">Regras customizadas para a Alquima Alumínios.</p>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">IA responde direto à dúvida do lead</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            Quando o lead chega com uma <strong style="color:rgba(255,255,255,0.6);">mensagem/dúvida do site</strong>, envia saudação e a IA responde automaticamente.
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Mover Novo → Em negociação ao responder</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            Quando o cliente responde e o card está na etapa <strong style="color:rgba(255,255,255,0.6);">Novo</strong>, move automaticamente para <strong style="color:rgba(255,255,255,0.6);">Em negociação</strong>.
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Lead salvo automaticamente no menu Leads</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            Todo lead via API é salvo no menu <strong style="color:rgba(255,255,255,0.6);">Leads</strong> com tag <strong style="color:rgba(255,255,255,0.6);">site</strong>.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            @if(app(\App\Services\CurrentCompany::class)->id() === 3)
            {{-- ══════════════════════════════════════════ --}}
            {{-- REGRAS DE NEGÓCIO — ORANGEXPRESS           --}}
            {{-- ══════════════════════════════════════════ --}}
            <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                    <svg width="18" height="18" fill="none" stroke="#fbbf24" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                    <h2 style="font-size:15px; font-weight:700; color:white; font-family:'Syne',sans-serif;">Regras de Negócio Ativas</h2>
                </div>
                <p style="font-size:11px; color:rgba(255,255,255,0.3); margin-bottom:16px;">Regras customizadas aplicadas no sistema para a Orangexpress.</p>

                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Roteamento automático por DDD</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            Quando um lead chega do site, o sistema identifica o <strong style="color:rgba(255,255,255,0.6);">DDD do telefone</strong> e atribui automaticamente ao agente responsável pela região.
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">IA responde direto à dúvida do lead</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            Quando o lead chega com uma <strong style="color:rgba(255,255,255,0.6);">mensagem/dúvida do site</strong>, a IA já envia a saudação e responde à dúvida automaticamente, sem mensagem fixa intermediária. A conversa já fica com IA ativa.
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Mover Novo → Em negociação ao responder</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            Quando o cliente responde e o card está na etapa <strong style="color:rgba(255,255,255,0.6);">Novo</strong>, move automaticamente para <strong style="color:rgba(255,255,255,0.6);">Em negociação</strong>.
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Lead salvo automaticamente no menu Leads</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            Todo lead que entra via API é salvo no menu <strong style="color:rgba(255,255,255,0.6);">Leads</strong> com tag <strong style="color:rgba(255,255,255,0.6);">site</strong> para futuros disparos.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            @if(app(\App\Services\CurrentCompany::class)->id() === 5)
            {{-- ══════════════════════════════════════════ --}}
            {{-- REGRAS DE NEGÓCIO — STUDIO ANA CARDOSO    --}}
            {{-- ══════════════════════════════════════════ --}}
            <div class="bg-surface-800 border border-surface-700 rounded-2xl p-6">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                    <svg width="18" height="18" fill="none" stroke="#fbbf24" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                    <h2 style="font-size:15px; font-weight:700; color:white; font-family:'Syne',sans-serif;">Regras de Negócio Ativas</h2>
                </div>
                <p style="font-size:11px; color:rgba(255,255,255,0.3); margin-bottom:16px;">Regras customizadas para o Studio Ana Cardoso.</p>

                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Agendamento via API externa</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            O sistema externo envia os dados via API (<strong style="color:rgba(255,255,255,0.6);">POST /api/leads</strong>).
                            O agendamento cria um card no <strong style="color:rgba(255,255,255,0.6);">Pipeline Agendamento → Etapa Novo</strong>
                            com campos: Cliente, Profissional, Serviços, Total, Data/Hora.
                            Suporta <strong style="color:rgba(255,255,255,0.6);">agendamento_id</strong> para atualizar cards existentes.
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Confirmação 24h antes + Saudação via IA</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            O sistema calcula <strong style="color:rgba(255,255,255,0.6);">24 horas antes</strong> do horário agendado e dispara mensagem via WhatsApp.
                            A mensagem é gerada pela <strong style="color:rgba(255,255,255,0.6);">IA com variações</strong> usando o texto configurado como referência (nunca repete a mesma mensagem).
                            Se o agendamento é em menos de 24h, envia imediatamente.
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Agendamento retroativo não envia mensagem</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            Se o agendamento tem <strong style="color:rgba(255,255,255,0.6);">data/hora no passado</strong>, o card é criado normalmente mas <strong style="color:rgba(255,255,255,0.6);">nenhuma mensagem</strong> é enviada.
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Resposta SIM/NÃO move etapa automaticamente</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            ✅ <strong style="color:#4ade80;">SIM</strong> → move para <strong style="color:rgba(255,255,255,0.6);">Confirmados</strong><br>
                            <span style="color:rgba(255,255,255,0.25); font-size:10px;">Detecta: texto (sim, pode, confirmo, beleza, ok, combinado...) + reações (👍❤️✅🙏💚😍🥰💪)</span><br>
                            <span style="color:rgba(255,255,255,0.25); font-size:10px;">Resposta gerada pela IA com variações do contexto configurado</span><br><br>
                            ❌ <strong style="color:#f87171;">NÃO</strong> → move para <strong style="color:rgba(255,255,255,0.6);">Remarcar</strong><br>
                            <span style="color:rgba(255,255,255,0.25); font-size:10px;">Detecta: texto (não, remarcar, cancelar, desmarcar...) + reações (👎❌😢😞)</span><br>
                            <span style="color:rgba(255,255,255,0.25); font-size:10px;">Resposta gerada pela IA com variações do contexto configurado</span>
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Atualização de agendamento via API</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            Se o sistema externo envia o mesmo <strong style="color:rgba(255,255,255,0.6);">agendamento_id</strong>, o card é atualizado.
                            Se a mensagem de confirmação <strong style="color:rgba(255,255,255,0.6);">já foi enviada</strong>, não reenvia.
                            Se <strong style="color:rgba(255,255,255,0.6);">ainda não foi enviada</strong>, dispara normalmente com os dados atualizados.
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Múltiplos cards por cliente</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            A mesma cliente pode ter <strong style="color:rgba(255,255,255,0.6);">vários cards</strong> (agendamentos diferentes).
                            Cada agendamento tem seu próprio card e disparo independente.
                        </p>
                    </div>
                    <div style="background:rgba(178,255,0,0.04); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:14px 16px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:rgba(34,197,94,0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.2);">ATIVA</span>
                            <p style="font-size:13px; font-weight:600; color:white;">Conversão de timezone UTC → BRT</p>
                        </div>
                        <p style="font-size:11px; color:rgba(255,255,255,0.35); line-height:1.6;">
                            Datas recebidas em <strong style="color:rgba(255,255,255,0.6);">UTC (Z)</strong> são convertidas automaticamente para <strong style="color:rgba(255,255,255,0.6);">horário de Brasília</strong>.
                        </p>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
</x-layouts.app>
