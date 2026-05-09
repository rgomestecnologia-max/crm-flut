<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Simulador de Preços — CRM Flut</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:#080C16; color:white; min-height:100vh; }
        .container { max-width:800px; margin:0 auto; padding:40px 20px 60px; }
        .logo { text-align:center; margin-bottom:24px; }
        .logo img { height:36px; }
        h1 { font-family:'Syne',sans-serif; font-size:24px; font-weight:800; letter-spacing:-0.02em; text-align:center; margin-bottom:6px; }
        .subtitle { text-align:center; font-size:13px; color:rgba(255,255,255,0.35); margin-bottom:36px; }
        .module { background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:24px; margin-bottom:16px; position:relative; overflow:hidden; }
        .module::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; border-radius:16px 16px 0 0; }
        .module-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
        .module-title { display:flex; align-items:center; gap:10px; }
        .module-title .bar { width:3px; height:20px; border-radius:2px; }
        .module-title h2 { font-family:'Syne',sans-serif; font-size:14px; font-weight:700; }
        .module-desc { font-size:11px; color:rgba(255,255,255,0.3); margin-bottom:14px; }
        .toggle { position:relative; display:inline-flex; width:48px; height:26px; border-radius:20px; border:none; cursor:pointer; transition:background 0.2s; flex-shrink:0; }
        .toggle span { position:absolute; top:3px; width:20px; height:20px; border-radius:50%; background:white; box-shadow:0 1px 4px rgba(0,0,0,0.3); transition:left 0.2s; }
        .field { margin-bottom:12px; }
        .field label { display:block; font-size:10px; font-weight:700; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px; }
        .field select, .field input { width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:10px 14px; font-size:13px; color:white; outline:none; font-family:inherit; }
        .field select option { background:#1a1a2e; }
        .field-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .price-tag { font-size:12px; font-weight:600; color:rgba(255,255,255,0.4); padding:4px 10px; background:rgba(255,255,255,0.04); border-radius:20px; }
        .result { background:linear-gradient(145deg, rgba(17,24,39,0.95), rgba(11,15,28,0.98)); border:2px solid rgba(178,255,0,0.3); border-radius:20px; padding:32px; margin-top:24px; text-align:center; }
        .result h3 { font-family:'Syne',sans-serif; font-size:13px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:20px; }
        .result-row { display:flex; justify-content:center; gap:40px; margin-bottom:24px; flex-wrap:wrap; }
        .result-item { text-align:center; }
        .result-item .label { font-size:11px; color:rgba(255,255,255,0.3); margin-bottom:6px; }
        .result-item .value { font-family:'Syne',sans-serif; font-size:32px; font-weight:800; letter-spacing:-0.02em; }
        .result-item .value.green { color:#b2ff00; }
        .result-item .value.blue { color:#3b82f6; }
        .breakdown { margin-top:16px; padding-top:16px; border-top:1px solid rgba(255,255,255,0.06); }
        .breakdown-item { display:flex; justify-content:space-between; padding:4px 0; font-size:12px; color:rgba(255,255,255,0.35); }
        .breakdown-item .val { color:rgba(255,255,255,0.6); font-weight:600; }
        .cta { display:block; width:100%; max-width:400px; margin:24px auto 0; padding:14px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-family:'Syne',sans-serif; font-size:14px; font-weight:700; letter-spacing:0.04em; text-transform:uppercase; text-decoration:none; border:none; border-radius:12px; cursor:pointer; text-align:center; box-shadow:0 4px 20px rgba(178,255,0,0.3); transition:all 0.2s; }
        .cta:hover { transform:translateY(-1px); box-shadow:0 8px 30px rgba(178,255,0,0.4); }
        @media (max-width:640px) { .field-row { grid-template-columns:1fr; } .result-row { gap:20px; } .result-item .value { font-size:24px; } }
    </style>
</head>
<body>
<div class="container">
    <div class="logo"><img src="/images/logo-flut.webp" alt="CRM Flut"></div>
    <h1>Simulador de Investimento</h1>
    <p class="subtitle">Monte sua solução ideal e veja o investimento em tempo real</p>

    <div x-data="pricingSimulator()" x-init="calc()">

        {{-- Multi-atendimento --}}
        <div class="module" style="border-color: rgba(178,255,0,0.1);">
            <div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#b2ff0080,transparent);"></div>
            <div class="module-header">
                <div class="module-title">
                    <div class="bar" style="background:#b2ff00;"></div>
                    <h2>Multi-atendimento WhatsApp</h2>
                </div>
                <button class="toggle" :style="{ background: modules.multi ? '#b2ff00' : 'rgba(255,255,255,0.1)' }" @click="modules.multi = !modules.multi; calc()">
                    <span :style="{ left: modules.multi ? '25px' : '3px' }"></span>
                </button>
            </div>
            <p class="module-desc">Atendimento via WhatsApp com múltiplos agentes, departamentos e chatbot.</p>
            <template x-if="modules.multi">
                <div>
                    <div class="field-row">
                        <div class="field">
                            <label>Quantidade de atendentes</label>
                            <input type="number" min="1" max="50" x-model.number="multi.users" @input="calc()">
                        </div>
                        <div class="field">
                            <label>Números de WhatsApp conectados</label>
                            <select x-model.number="multi.instances" @change="calc()">
                                <option value="1">1 número</option>
                                <option value="2">2 números</option>
                                <option value="3">3 números</option>
                                <option value="4">4 números</option>
                                <option value="5">5 números</option>
                            </select>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- CRM --}}
        <div class="module" style="border-color: rgba(139,92,246,0.1);">
            <div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#8b5cf680,transparent);"></div>
            <div class="module-header">
                <div class="module-title">
                    <div class="bar" style="background:#8b5cf6;"></div>
                    <h2>CRM — Pipeline de Vendas</h2>
                </div>
                <button class="toggle" :style="{ background: modules.crm ? '#8b5cf6' : 'rgba(255,255,255,0.1)' }" @click="modules.crm = !modules.crm; calc()">
                    <span :style="{ left: modules.crm ? '25px' : '3px' }"></span>
                </button>
            </div>
            <p class="module-desc">Kanban de vendas com pipeline, etapas, campos personalizados e exportação.</p>
            <template x-if="modules.crm">
                <div>
                    <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-bottom:8px;">Exemplos de pipelines que podemos criar para você:</p>
                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(139,92,246,0.08); border:1px solid rgba(139,92,246,0.15); color:#a78bfa;">Pipeline Comercial</span>
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(139,92,246,0.08); border:1px solid rgba(139,92,246,0.15); color:#a78bfa;">Pipeline SDR</span>
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(139,92,246,0.08); border:1px solid rgba(139,92,246,0.15); color:#a78bfa;">Pipeline Pós-venda</span>
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(139,92,246,0.08); border:1px solid rgba(139,92,246,0.15); color:#a78bfa;">Pipeline Financeiro</span>
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(139,92,246,0.08); border:1px solid rgba(139,92,246,0.15); color:#a78bfa;">Pipeline Suporte</span>
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(139,92,246,0.08); border:1px solid rgba(139,92,246,0.15); color:#a78bfa;">Pipeline Marketing</span>
                    </div>
                </div>
            </template>
        </div>

        {{-- Disparos --}}
        <div class="module" style="border-color: rgba(59,130,246,0.1);">
            <div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#3b82f680,transparent);"></div>
            <div class="module-header">
                <div class="module-title">
                    <div class="bar" style="background:#3b82f6;"></div>
                    <h2>Disparos em Massa</h2>
                </div>
                <button class="toggle" :style="{ background: modules.email ? '#3b82f6' : 'rgba(255,255,255,0.1)' }" @click="modules.email = !modules.email; calc()">
                    <span :style="{ left: modules.email ? '25px' : '3px' }"></span>
                </button>
            </div>
            <p class="module-desc">Campanhas de email e WhatsApp em massa com agendamento, templates e relatórios.</p>
            <template x-if="modules.email">
                <div>
                    <div class="field">
                        <label>Disparo por Email — Volume mensal</label>
                        <select x-model="email.plan" @change="calc()">
                            <option value="none">Não preciso de email</option>
                            <option value="5k">Até 5.000 disparos/mês</option>
                            <option value="20k">Até 20.000 disparos/mês</option>
                            <option value="50k">Até 50.000 disparos/mês</option>
                        </select>
                    </div>
                    <div class="field" style="margin-top:12px;">
                        <label>Disparo por WhatsApp</label>
                        <div style="display:flex; align-items:center; gap:10px; margin-top:6px;">
                            <button class="toggle" style="flex-shrink:0;" :style="{ background: email.whatsapp ? '#22c55e' : 'rgba(255,255,255,0.1)' }" @click="email.whatsapp = !email.whatsapp; calc()">
                                <span :style="{ left: email.whatsapp ? '25px' : '3px' }"></span>
                            </button>
                            <span style="font-size:12px; color:rgba(255,255,255,0.5);" x-text="email.whatsapp ? 'Incluir disparo por WhatsApp (+R$ 200/mês)' : 'Sem disparo por WhatsApp'"></span>
                        </div>
                        <template x-if="email.whatsapp">
                            <div style="margin-top:10px; padding:10px 14px; background:rgba(245,158,11,0.06); border:1px solid rgba(245,158,11,0.15); border-radius:10px;">
                                <p style="font-size:11px; color:rgba(245,158,11,0.8); line-height:1.6;">
                                    <strong>Recomendação:</strong> Para disparos em massa via WhatsApp, recomendamos utilizar a <strong>API oficial do WhatsApp (Meta)</strong> para evitar bloqueio do número. A API oficial cobra entre <strong>R$ 0,15 a R$ 0,30 por mensagem</strong> iniciada com o cliente (cobrado diretamente pela Meta).
                                </p>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- IA --}}
        <div class="module" style="border-color: rgba(236,72,153,0.1);">
            <div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#ec489980,transparent);"></div>
            <div class="module-header">
                <div class="module-title">
                    <div class="bar" style="background:#ec4899;"></div>
                    <h2>IA de Atendimento</h2>
                </div>
                <button class="toggle" :style="{ background: modules.ia ? '#ec4899' : 'rgba(255,255,255,0.1)' }" @click="modules.ia = !modules.ia; calc()">
                    <span :style="{ left: modules.ia ? '25px' : '3px' }"></span>
                </button>
            </div>
            <p class="module-desc">Inteligência artificial que atende seus clientes 24h com base de conhecimento.</p>
            <template x-if="modules.ia">
                <div>
                    <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-bottom:8px;">Exemplos de fluxos/agentes de IA:</p>
                    <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px;">
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(236,72,153,0.08); border:1px solid rgba(236,72,153,0.15); color:#f472b6;">SDR</span>
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(236,72,153,0.08); border:1px solid rgba(236,72,153,0.15); color:#f472b6;">SAC</span>
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(236,72,153,0.08); border:1px solid rgba(236,72,153,0.15); color:#f472b6;">Agendamento</span>
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(236,72,153,0.08); border:1px solid rgba(236,72,153,0.15); color:#f472b6;">Cobranças</span>
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(236,72,153,0.08); border:1px solid rgba(236,72,153,0.15); color:#f472b6;">Pós-Venda</span>
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(236,72,153,0.08); border:1px solid rgba(236,72,153,0.15); color:#f472b6;">Reativação</span>
                    </div>
                    <div class="field">
                        <label>Quantidade de fluxos/agentes de IA</label>
                        <input type="number" min="1" max="10" x-model.number="ia.flows" @input="calc()">
                    </div>
                </div>
            </template>
        </div>

        {{-- Integrações --}}
        <div class="module" style="border-color: rgba(6,182,212,0.1);">
            <div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#06b6d480,transparent);"></div>
            <div class="module-header">
                <div class="module-title">
                    <div class="bar" style="background:#06b6d4;"></div>
                    <h2>Integrações Externas</h2>
                </div>
                <button class="toggle" :style="{ background: modules.integrations ? '#06b6d4' : 'rgba(255,255,255,0.1)' }" @click="modules.integrations = !modules.integrations; calc()">
                    <span :style="{ left: modules.integrations ? '25px' : '3px' }"></span>
                </button>
            </div>
            <p class="module-desc">Conexão com sistemas externos para envio e recebimento de dados automaticamente.</p>
            <template x-if="modules.integrations">
                <div>
                    <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-bottom:10px;">Exemplos de integrações:</p>
                    <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px;">
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(6,182,212,0.08); border:1px solid rgba(6,182,212,0.15); color:#22d3ee;">Site</span>
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(6,182,212,0.08); border:1px solid rgba(6,182,212,0.15); color:#22d3ee;">Loja Virtual</span>
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(6,182,212,0.08); border:1px solid rgba(6,182,212,0.15); color:#22d3ee;">Sistema Financeiro</span>
                        <span style="font-size:10px; padding:4px 10px; border-radius:20px; background:rgba(6,182,212,0.08); border:1px solid rgba(6,182,212,0.15); color:#22d3ee;">ERP</span>
                    </div>
                    <div class="field">
                        <label>Quantidade de integrações</label>
                        <input type="number" min="1" max="10" x-model.number="integrations.count" @input="calc()">
                    </div>
                </div>
            </template>
        </div>

        {{-- Resultado --}}
        <div class="result">
            <h3>Seu investimento</h3>
            <div class="result-row">
                <div class="result-item">
                    <p class="label">Implantação (único)</p>
                    <p class="value blue">R$ <span x-text="fmt(total.setup)"></span></p>
                </div>
                <div class="result-item">
                    <p class="label">Mensalidade</p>
                    <p class="value green">R$ <span x-text="fmt(total.monthly)"></span></p>
                </div>
            </div>

            <div class="breakdown">
                <template x-if="modules.multi">
                    <div>
                        <div class="breakdown-item">
                            <span>Multi-atendimento (<span x-text="multi.users"></span> usuários, <span x-text="multi.instances"></span> número<span x-show="multi.instances > 1">s</span>)</span>
                            <span class="val">R$ <span x-text="fmt(detail.multi_monthly)"></span>/mês</span>
                        </div>
                        <div class="breakdown-item">
                            <span>↳ Implantação</span>
                            <span class="val">R$ <span x-text="fmt(detail.multi_setup)"></span></span>
                        </div>
                    </div>
                </template>
                <template x-if="modules.crm">
                    <div>
                        <div class="breakdown-item">
                            <span>CRM</span>
                            <span class="val">R$ <span x-text="fmt(detail.crm_monthly)"></span>/mês</span>
                        </div>
                        <div class="breakdown-item">
                            <span>↳ Implantação</span>
                            <span class="val">R$ <span x-text="fmt(detail.crm_setup)"></span></span>
                        </div>
                    </div>
                </template>
                <template x-if="modules.email">
                    <div>
                        <div class="breakdown-item">
                            <span>Disparos (<span x-show="email.plan !== 'none'">Email <span x-text="email.plan"></span></span><span x-show="email.plan !== 'none' && email.whatsapp"> + </span><span x-show="email.whatsapp">WhatsApp</span>)</span>
                            <span class="val">R$ <span x-text="fmt(detail.email_monthly)"></span>/mês</span>
                        </div>
                        <div class="breakdown-item">
                            <span>↳ Implantação</span>
                            <span class="val">R$ <span x-text="fmt(detail.email_setup)"></span></span>
                        </div>
                    </div>
                </template>
                <template x-if="modules.ia">
                    <div>
                        <div class="breakdown-item">
                            <span>IA de Atendimento (<span x-text="ia.flows"></span> fluxo<span x-show="ia.flows > 1">s</span>)</span>
                            <span class="val">R$ <span x-text="fmt(detail.ia_monthly)"></span>/mês</span>
                        </div>
                        <div class="breakdown-item">
                            <span>↳ Implantação</span>
                            <span class="val">R$ <span x-text="fmt(detail.ia_setup)"></span></span>
                        </div>
                    </div>
                </template>
                <template x-if="modules.integrations">
                    <div>
                        <div class="breakdown-item">
                            <span>Integrações (<span x-text="integrations.count"></span>)</span>
                            <span class="val">R$ <span x-text="fmt(detail.int_monthly)"></span>/mês</span>
                        </div>
                        <div class="breakdown-item">
                            <span>↳ Implantação</span>
                            <span class="val">R$ <span x-text="fmt(detail.int_setup)"></span></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div style="margin-top:20px; padding:16px 20px; background:linear-gradient(135deg, rgba(245,158,11,0.08), rgba(245,158,11,0.03)); border:2px solid rgba(245,158,11,0.25); border-radius:14px; text-align:center;">
            <div style="display:flex; align-items:center; justify-content:center; gap:10px; margin-bottom:6px;">
                <svg width="20" height="20" fill="none" stroke="#fbbf24" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p style="font-size:14px; font-weight:700; color:#fbbf24; font-family:'Syne',sans-serif;">Prazo de implantação: até 10 dias úteis</p>
            </div>
            <p style="font-size:12px; color:rgba(255,255,255,0.35);">Após a aprovação, nossa equipe inicia a configuração completa do seu CRM.</p>
        </div>

        {{-- Nome do cliente (obrigatório) --}}
        <div style="margin-top:20px;">
            <div class="field">
                <label>Nome do cliente / empresa <span style="color:#ef4444;">*</span></label>
                <input type="text" x-model="clientName" placeholder="Digite o nome do cliente ou empresa"
                       style="width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:12px 16px; font-size:14px; color:white; outline:none; font-family:inherit;"
                       :style="nameError ? 'border-color:rgba(239,68,68,0.5)' : ''"
                       @input="nameError = false">
                <p x-show="nameError" x-cloak style="font-size:11px; color:#ef4444; margin-top:6px;">Preencha o nome do cliente para salvar a proposta</p>
            </div>
        </div>

        {{-- Botões --}}
        <div style="display:flex; gap:12px; margin-top:16px;">
            <a href="/onboarding" class="cta" style="flex:1;">Solicitar implementação</a>

            <template x-if="!savedId">
                <button @click="salvarProposta()" class="cta" style="flex:1; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111;" :disabled="saving">
                    <template x-if="saving">
                        <span>Salvando...</span>
                    </template>
                    <template x-if="!saving">
                        <span>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline; vertical-align:middle; margin-right:6px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Salvar Proposta
                        </span>
                    </template>
                </button>
            </template>

            <template x-if="savedId">
                <button @click="gerarPDF()" class="cta" style="flex:1; background:linear-gradient(135deg, #3b82f6, #2563eb); box-shadow:0 4px 20px rgba(59,130,246,0.3);">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline; vertical-align:middle; margin-right:6px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Gerar PDF da proposta
                </button>
            </template>
        </div>

        {{-- Confirmação de salvamento --}}
        <template x-if="savedId">
            <div style="margin-top:12px; padding:12px 16px; background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.2); border-radius:10px; display:flex; align-items:center; gap:10px;">
                <svg width="18" height="18" fill="none" stroke="#22c55e" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <p style="font-size:12px; color:#22c55e;">Proposta salva com sucesso! Agora você pode gerar o PDF.</p>
            </div>
        </template>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script>
function pricingSimulator() {
    const C = @json($config);
    return {
        modules: { multi: true, crm: false, email: false, ia: false, integrations: false },
        multi: { users: 1, instances: 1 },
        email: { plan: '5k', whatsapp: false },
        ia: { flows: 1 },
        integrations: { count: 1 },
        total: { monthly: 0, setup: 0 },
        detail: {},
        clientName: '',
        savedId: null,
        saving: false,
        nameError: false,

        fmt(v) { return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

        calc() {
            let monthly = 0, setup = 0;
            this.detail = {};

            if (this.modules.multi) {
                let m = parseFloat(C.multi_base_price);
                const baseUsers = parseInt(C.multi_base_users);
                const extra = Math.max(0, this.multi.users - baseUsers) * parseFloat(C.multi_extra_user);
                m += extra;
                const extraInst = Math.max(0, this.multi.instances - 1) * parseFloat(C.multi_extra_instance);
                m += extraInst;
                const s = parseFloat(C.multi_setup);
                this.detail.multi_monthly = m;
                this.detail.multi_setup = s;
                monthly += m; setup += s;
            }
            if (this.modules.crm) {
                const m = parseFloat(C.crm_price);
                const s = parseFloat(C.crm_setup);
                this.detail.crm_monthly = m;
                this.detail.crm_setup = s;
                monthly += m; setup += s;
            }
            if (this.modules.email) {
                const prices = { 'none': 0, '5k': parseFloat(C.email_5k_price), '20k': parseFloat(C.email_20k_price), '50k': parseFloat(C.email_50k_price) };
                let m = prices[this.email.plan] ?? 0;
                let s = this.email.plan !== 'none' ? parseFloat(C.email_setup) : 0;
                // WhatsApp broadcast
                if (this.email.whatsapp) {
                    m += 200;
                    if (s === 0) s = parseFloat(C.email_setup); // setup se só tem WhatsApp
                }
                this.detail.email_monthly = m;
                this.detail.email_setup = s;
                monthly += m; setup += s;
            }
            if (this.modules.ia) {
                const m = this.ia.flows * parseFloat(C.ia_flow_price);
                const s = this.ia.flows * parseFloat(C.ia_flow_setup);
                this.detail.ia_monthly = m;
                this.detail.ia_setup = s;
                monthly += m; setup += s;
            }
            if (this.modules.integrations) {
                const m = this.integrations.count * parseFloat(C.integration_monthly);
                const s = this.integrations.count * parseFloat(C.integration_setup);
                this.detail.int_monthly = m;
                this.detail.int_setup = s;
                monthly += m; setup += s;
            }

            this.total.monthly = monthly;
            this.total.setup = setup;
        },

        async salvarProposta() {
            if (!this.clientName.trim()) {
                this.nameError = true;
                return;
            }
            this.saving = true;
            try {
                const res = await fetch('/pricing/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({
                        client_name: this.clientName.trim(),
                        modules: this.modules,
                        config: {
                            multi_users: this.multi.users,
                            multi_instances: this.multi.instances,
                            email_plan: this.email.plan,
                            email_whatsapp: this.email.whatsapp,
                            ia_flows: this.ia.flows,
                            integrations_count: this.integrations.count,
                        },
                        details: this.detail,
                        total_monthly: this.total.monthly,
                        total_setup: this.total.setup,
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.savedId = data.id;
                } else {
                    alert('Erro ao salvar proposta. Tente novamente.');
                }
            } catch (e) {
                alert('Erro de conexão. Tente novamente.');
            }
            this.saving = false;
        },

        gerarPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait' });
            const pw = doc.internal.pageSize.getWidth();
            const today = new Date();
            const dataStr = today.toLocaleDateString('pt-BR');
            const validade = new Date(today.getTime() + 30*24*60*60*1000).toLocaleDateString('pt-BR');
            const fmt = (v) => v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // === HEADER (fundo escuro) ===
            doc.setFillColor(15, 23, 42);
            doc.roundedRect(15, 12, pw - 30, 38, 4, 4, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(20);
            doc.setFont('helvetica', 'bold');
            doc.text('Proposta Comercial', pw / 2, 26, { align: 'center' });
            doc.setFontSize(12);
            doc.setTextColor(178, 255, 0);
            doc.text(this.clientName, pw / 2, 35, { align: 'center' });
            doc.setFontSize(9);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(180, 180, 200);
            doc.text('CRM Flut  |  Gerada em ' + dataStr, pw / 2, 44, { align: 'center' });

            // === MÓDULOS SELECIONADOS (tabela) ===
            let items = [];
            if (this.modules.multi) {
                items.push([
                    `Multi-atendimento (${this.multi.users} usuário${this.multi.users>1?'s':''}, ${this.multi.instances} número${this.multi.instances>1?'s':''})`,
                    'R$ ' + fmt(this.detail.multi_monthly) + '/mês',
                    'R$ ' + fmt(this.detail.multi_setup)
                ]);
            }
            if (this.modules.crm) {
                items.push([
                    'CRM — Pipeline de Vendas',
                    'R$ ' + fmt(this.detail.crm_monthly) + '/mês',
                    'R$ ' + fmt(this.detail.crm_setup)
                ]);
            }
            if (this.modules.email) {
                let desc = 'Disparos em Massa';
                if (this.email.plan !== 'none') desc += ` (Email ${this.email.plan})`;
                if (this.email.whatsapp) desc += (this.email.plan !== 'none' ? ' + ' : ' (') + 'WhatsApp' + (this.email.plan === 'none' ? ')' : '');
                items.push([
                    desc,
                    'R$ ' + fmt(this.detail.email_monthly) + '/mês',
                    'R$ ' + fmt(this.detail.email_setup)
                ]);
            }
            if (this.modules.ia) {
                items.push([
                    `IA de Atendimento (${this.ia.flows} fluxo${this.ia.flows>1?'s':''})`,
                    'R$ ' + fmt(this.detail.ia_monthly) + '/mês',
                    'R$ ' + fmt(this.detail.ia_setup)
                ]);
            }
            if (this.modules.integrations) {
                items.push([
                    `Integrações Externas (${this.integrations.count})`,
                    'R$ ' + fmt(this.detail.int_monthly) + '/mês',
                    'R$ ' + fmt(this.detail.int_setup)
                ]);
            }

            // Linha de total
            items.push([
                'TOTAL',
                'R$ ' + fmt(this.total.monthly) + '/mês',
                'R$ ' + fmt(this.total.setup)
            ]);

            doc.autoTable({
                startY: 58,
                margin: { left: 15, right: 15 },
                head: [['Módulo', 'Mensalidade', 'Implantação']],
                body: items,
                headStyles: {
                    fillColor: [241, 245, 249],
                    textColor: [80, 80, 80],
                    fontStyle: 'bold',
                    fontSize: 9,
                    halign: 'left'
                },
                columnStyles: {
                    0: { cellWidth: 'auto' },
                    1: { halign: 'right', cellWidth: 42 },
                    2: { halign: 'right', cellWidth: 42 }
                },
                bodyStyles: {
                    fontSize: 10,
                    textColor: [51, 51, 51],
                    cellPadding: 5
                },
                alternateRowStyles: {
                    fillColor: [250, 250, 252]
                },
                didParseCell: function(data) {
                    if (data.section === 'body' && data.row.index === items.length - 1) {
                        data.cell.styles.fillColor = [240, 253, 244];
                        data.cell.styles.fontStyle = 'bold';
                        data.cell.styles.fontSize = 11;
                        if (data.column.index === 1) {
                            data.cell.styles.textColor = [34, 197, 94];
                        } else if (data.column.index === 2) {
                            data.cell.styles.textColor = [59, 130, 246];
                        } else {
                            data.cell.styles.textColor = [17, 17, 17];
                        }
                    }
                }
            });

            let y = doc.lastAutoTable.finalY + 12;

            // === PRAZO DE IMPLANTAÇÃO ===
            doc.setFillColor(255, 251, 235);
            doc.setDrawColor(253, 230, 138);
            doc.roundedRect(15, y, pw - 30, 22, 3, 3, 'FD');
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(146, 64, 14);
            doc.text('Prazo de implantação: até 10 dias úteis após aprovação.', 20, y + 9);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            doc.text('Validade da proposta: ' + dataStr + ' até ' + validade + ' (30 dias corridos).', 20, y + 17);

            y += 32;

            // === FOOTER ===
            doc.setDrawColor(230, 230, 230);
            doc.line(15, y, pw - 15, y);
            doc.setFontSize(9);
            doc.setTextColor(153, 153, 153);
            doc.text('CRM Flut — crm.flut.com.br', pw / 2, y + 8, { align: 'center' });
            doc.setFontSize(8);
            doc.setTextColor(200, 200, 200);
            doc.text('Documento gerado automaticamente pelo simulador de investimento.', pw / 2, y + 14, { align: 'center' });

            // === SALVAR ===
            const filename = 'proposta-' + this.clientName.trim().toLowerCase().replace(/\s+/g, '-') + '-' + today.toISOString().slice(0,10) + '.pdf';
            doc.save(filename);
        }
    };
}
</script>
</body>
</html>
