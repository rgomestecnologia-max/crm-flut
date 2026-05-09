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
        </div>

        {{-- Disparos Email --}}
        <div class="module" style="border-color: rgba(59,130,246,0.1);">
            <div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#3b82f680,transparent);"></div>
            <div class="module-header">
                <div class="module-title">
                    <div class="bar" style="background:#3b82f6;"></div>
                    <h2>Disparos de Email</h2>
                </div>
                <button class="toggle" :style="{ background: modules.email ? '#3b82f6' : 'rgba(255,255,255,0.1)' }" @click="modules.email = !modules.email; calc()">
                    <span :style="{ left: modules.email ? '25px' : '3px' }"></span>
                </button>
            </div>
            <p class="module-desc">Campanhas de email em massa com agendamento, templates e unsubscribe.</p>
            <template x-if="modules.email">
                <div class="field">
                    <label>Volume mensal de disparos</label>
                    <select x-model="email.plan" @change="calc()">
                        <option value="5k">Até 5.000 disparos/mês</option>
                        <option value="20k">Até 20.000 disparos/mês</option>
                        <option value="50k">Até 50.000 disparos/mês</option>
                    </select>
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
                <div class="field">
                    <label>Quantidade de fluxos/agentes de IA</label>
                    <input type="number" min="1" max="10" x-model.number="ia.flows" @input="calc()">
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
            <p class="module-desc">Conexão com sistemas externos (site, ERP, CRM externo, API personalizada).</p>
            <template x-if="modules.integrations">
                <div class="field">
                    <label>Quantidade de integrações</label>
                    <input type="number" min="1" max="10" x-model.number="integrations.count" @input="calc()">
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
                            <span>Disparos Email (<span x-text="email.plan"></span>)</span>
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

        <a href="/onboarding" class="cta">Solicitar implementação</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js" defer></script>
<script>
function pricingSimulator() {
    const C = @json($config);
    return {
        modules: { multi: true, crm: false, email: false, ia: false, integrations: false },
        multi: { users: 3, instances: 1 },
        email: { plan: '5k' },
        ia: { flows: 1 },
        integrations: { count: 1 },
        total: { monthly: 0, setup: 0 },
        detail: {},

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
                const prices = { '5k': parseFloat(C.email_5k_price), '20k': parseFloat(C.email_20k_price), '50k': parseFloat(C.email_50k_price) };
                const m = prices[this.email.plan] || prices['5k'];
                const s = parseFloat(C.email_setup);
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
        }
    };
}
</script>
</body>
</html>
