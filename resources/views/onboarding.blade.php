<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Onboarding — CRM Flut</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:#080C16; color:white; min-height:100vh; }
        .container { max-width:720px; margin:0 auto; padding:40px 20px 60px; }
        .logo { text-align:center; margin-bottom:32px; }
        .logo img { height:36px; }
        h1 { font-family:'Syne',sans-serif; font-size:24px; font-weight:800; letter-spacing:-0.02em; text-align:center; margin-bottom:8px; }
        .subtitle { text-align:center; font-size:13px; color:rgba(255,255,255,0.35); margin-bottom:40px; }
        .section { background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:24px; margin-bottom:20px; position:relative; overflow:hidden; }
        .section::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; border-radius:16px 16px 0 0; }
        .section-green::before { background:linear-gradient(90deg, #b2ff0080, transparent); }
        .section-blue::before { background:linear-gradient(90deg, #3b82f680, transparent); }
        .section-purple::before { background:linear-gradient(90deg, #8b5cf680, transparent); }
        .section-amber::before { background:linear-gradient(90deg, #f59e0b80, transparent); }
        .section-pink::before { background:linear-gradient(90deg, #ec489980, transparent); }
        .section-cyan::before { background:linear-gradient(90deg, #06b6d480, transparent); }
        .section-title { display:flex; align-items:center; gap:8px; margin-bottom:16px; }
        .section-title .bar { width:3px; height:18px; border-radius:2px; }
        .section-title h2 { font-family:'Syne',sans-serif; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; }
        .section-desc { font-size:11px; color:rgba(255,255,255,0.3); margin-bottom:16px; }
        .field { margin-bottom:14px; }
        .field label { display:block; font-size:10px; font-weight:700; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px; }
        .field input, .field select, .field textarea { width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:10px 14px; font-size:13px; color:white; outline:none; font-family:inherit; transition:all 0.2s; }
        .field input:focus, .field select:focus, .field textarea:focus { border-color:rgba(178,255,0,0.5); box-shadow:0 0 0 3px rgba(178,255,0,0.07); }
        .field input::placeholder, .field textarea::placeholder { color:rgba(255,255,255,0.15); }
        .field textarea { resize:vertical; min-height:80px; line-height:1.6; }
        .field input[type=file] { padding:8px; font-size:12px; cursor:pointer; }
        .field input[type=color] { height:42px; padding:4px; cursor:pointer; }
        .field-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .field-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
        .radio-group { display:flex; gap:10px; flex-wrap:wrap; }
        .radio-group label { display:flex; align-items:center; gap:6px; font-size:12px; color:rgba(255,255,255,0.6); cursor:pointer; padding:6px 12px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:8px; transition:all 0.15s; }
        .radio-group label:hover { background:rgba(255,255,255,0.06); }
        .radio-group input { accent-color:#b2ff00; }
        .btn { display:block; width:100%; padding:14px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-family:'Syne',sans-serif; font-size:14px; font-weight:700; letter-spacing:0.04em; text-transform:uppercase; border:none; border-radius:12px; cursor:pointer; transition:all 0.2s; box-shadow:0 4px 20px rgba(178,255,0,0.3); }
        .btn:hover { transform:translateY(-1px); box-shadow:0 8px 30px rgba(178,255,0,0.4); }
        .success { text-align:center; padding:80px 20px; }
        .success .icon { font-size:64px; margin-bottom:20px; }
        .success h2 { font-family:'Syne',sans-serif; font-size:22px; margin-bottom:12px; }
        .success p { color:rgba(255,255,255,0.4); font-size:14px; }
        .required { color:#f87171; }
        @media (max-width:640px) { .field-row, .field-row-3 { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">
        <img src="/images/logo-flut.webp" alt="CRM Flut">
    </div>

    @if(isset($submitted) && $submitted)
        <div class="success">
            <div class="icon">🚀</div>
            <h2>Recebemos suas informações!</h2>
            <p>Obrigado, <strong>{{ $companyName }}</strong>. Nossa equipe vai analisar e entrar em contato para iniciar a implementação do seu CRM.</p>
        </div>
    @else
        <h1>Implementação do CRM</h1>
        <p class="subtitle">Preencha as informações abaixo para configurarmos seu sistema de atendimento</p>

        <form method="POST" action="{{ url('/onboarding') }}" enctype="multipart/form-data">
            @csrf

            {{-- Dados da Empresa --}}
            <div class="section section-green">
                <div class="section-title">
                    <div class="bar" style="background:#b2ff00;"></div>
                    <h2>Dados da Empresa</h2>
                </div>
                <div class="field">
                    <label>Nome da empresa <span class="required">*</span></label>
                    <input type="text" name="company_name" required placeholder="Ex: Machinery Prime">
                </div>
                <div class="field-row">
                    <div class="field">
                        <label>CNPJ</label>
                        <input type="text" name="cnpj" placeholder="00.000.000/0000-00">
                    </div>
                    <div class="field">
                        <label>Segmento</label>
                        <input type="text" name="segment" placeholder="Ex: Máquinas industriais">
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label>Site</label>
                        <input type="text" name="website" placeholder="https://www.exemplo.com.br">
                    </div>
                    <div class="field">
                        <label>Redes sociais</label>
                        <input type="text" name="social_media" placeholder="@instagram, facebook.com/...">
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label>Logo da empresa (upload)</label>
                        <input type="file" name="logo" accept="image/*">
                    </div>
                    <div class="field">
                        <label>Cor principal da marca</label>
                        <input type="color" name="brand_color" value="#b2ff00">
                    </div>
                </div>
            </div>

            {{-- WhatsApp --}}
            <div class="section section-blue">
                <div class="section-title">
                    <div class="bar" style="background:#3b82f6;"></div>
                    <h2>WhatsApp</h2>
                </div>
                <div class="field-row-3">
                    <div class="field">
                        <label>Número do WhatsApp</label>
                        <input type="text" name="whatsapp_number" placeholder="(11) 99999-9999">
                    </div>
                    <div class="field">
                        <label>Já usa WhatsApp Business?</label>
                        <div class="radio-group" style="margin-top:4px;">
                            <label><input type="radio" name="has_whatsapp_business" value="sim"> Sim</label>
                            <label><input type="radio" name="has_whatsapp_business" value="nao"> Não</label>
                        </div>
                    </div>
                    <div class="field">
                        <label>Quantos atendentes?</label>
                        <input type="number" name="agents_count" min="1" placeholder="Ex: 3">
                    </div>
                </div>
            </div>

            {{-- Departamentos --}}
            <div class="section section-purple">
                <div class="section-title">
                    <div class="bar" style="background:#8b5cf6;"></div>
                    <h2>Departamentos / Setores</h2>
                </div>
                <p class="section-desc">Quais setores da empresa vão atender pelo WhatsApp?</p>
                <div class="field">
                    <label>Departamentos</label>
                    <textarea name="departments" rows="3" placeholder="Ex: Comercial, Suporte Técnico, Financeiro, Pós-venda"></textarea>
                </div>
                <div class="field">
                    <label>Responsáveis por cada setor (nome e email)</label>
                    <textarea name="department_leads" rows="4" placeholder="Comercial: João Silva - joao@empresa.com&#10;Suporte: Maria Santos - maria@empresa.com"></textarea>
                </div>
            </div>

            {{-- CRM Pipeline --}}
            <div class="section section-amber">
                <div class="section-title">
                    <div class="bar" style="background:#f59e0b;"></div>
                    <h2>CRM — Pipeline de Vendas</h2>
                </div>
                <p class="section-desc">Como funciona o processo de vendas da sua empresa?</p>
                <div class="field">
                    <label>Etapas do funil de vendas</label>
                    <textarea name="sales_pipeline" rows="3" placeholder="Ex: Novo Lead → Primeiro Contato → Negociação → Proposta → Fechado Ganho / Fechado Perdido"></textarea>
                </div>
                <div class="field">
                    <label>Campos personalizados que precisa no CRM</label>
                    <textarea name="custom_fields" rows="3" placeholder="Ex: Valor do orçamento, Modelo do equipamento, Data de entrega, Forma de pagamento"></textarea>
                </div>
            </div>

            {{-- IA de Atendimento --}}
            <div class="section section-pink">
                <div class="section-title">
                    <div class="bar" style="background:#ec4899;"></div>
                    <h2>IA de Atendimento</h2>
                </div>
                <p class="section-desc">Informações para treinar a inteligência artificial que vai atender seus clientes.</p>
                <div class="field">
                    <label>Descrição da empresa (o que faz, produtos, serviços)</label>
                    <textarea name="company_description" rows="4" placeholder="Descreva sua empresa, o que vende, principais produtos/serviços, diferenciais..."></textarea>
                </div>
                <div class="field">
                    <label>Tom de voz desejado</label>
                    <div class="radio-group">
                        <label><input type="checkbox" name="voice_tone" value="Profissional"> Profissional</label>
                        <label><input type="checkbox" name="voice_tone" value="Amigável"> Amigável</label>
                        <label><input type="checkbox" name="voice_tone" value="Técnico"> Técnico</label>
                        <label><input type="checkbox" name="voice_tone" value="Descontraído"> Descontraído</label>
                        <label><input type="checkbox" name="voice_tone" value="Formal"> Formal</label>
                        <label><input type="checkbox" name="voice_tone" value="Consultivo"> Consultivo</label>
                    </div>
                </div>
                <div class="field">
                    <label>Horário de atendimento</label>
                    <input type="text" name="business_hours" placeholder="Ex: Seg a Sex, 08h às 18h">
                </div>
                <div class="field">
                    <label>A IA deve se apresentar como atendente virtual?</label>
                    <div class="radio-group">
                        <label><input type="radio" name="ai_presents_as_virtual" value="sim"> Sim, se apresenta como atendente virtual</label>
                        <label><input type="radio" name="ai_presents_as_virtual" value="nao"> Não, se apresenta como pessoa real</label>
                    </div>
                </div>
                <div class="field">
                    <label>A IA tem um nome específico de atendente?</label>
                    <div class="field-row">
                        <div>
                            <div class="radio-group">
                                <label><input type="radio" name="ai_has_name" value="sim" onclick="document.getElementById('ai_name_field').style.display='block'"> Sim</label>
                                <label><input type="radio" name="ai_has_name" value="nao" onclick="document.getElementById('ai_name_field').style.display='none'"> Não</label>
                            </div>
                        </div>
                        <div id="ai_name_field" style="display:none;">
                            <input type="text" name="ai_name" placeholder="Ex: Ana, Julia, Assistente Comercial">
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label>Perguntas frequentes dos clientes (FAQ)</label>
                    <textarea name="faq" rows="4" placeholder="Pergunta: Qual o horário de funcionamento?&#10;Resposta: Funcionamos de segunda a sexta, das 8h às 18h.&#10;&#10;Pergunta: Vocês fazem entrega?&#10;Resposta: Sim, para toda a região."></textarea>
                </div>
                <div class="field">
                    <label>Checklist de Atendimento</label>
                    <textarea name="checklist" rows="4" placeholder="Informações que a IA deve coletar durante o atendimento:&#10;- Nome completo&#10;- Cidade/Estado&#10;- Produto de interesse&#10;- Orçamento disponível"></textarea>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label>Tem catálogo de produtos?</label>
                        <div class="radio-group" style="margin-top:4px;">
                            <label><input type="radio" name="has_catalog" value="sim"> Sim</label>
                            <label><input type="radio" name="has_catalog" value="nao"> Não</label>
                        </div>
                    </div>
                    <div class="field">
                        <label>Upload de catálogos/documentos</label>
                        <input type="file" name="catalog_files[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                    </div>
                </div>
                <div class="field">
                    <label>Link de repositório online (Google Drive, Dropbox, etc.)</label>
                    <input type="text" name="catalog_link" placeholder="Ex: https://drive.google.com/drive/folders/...">
                    <p style="font-size:10px; color:rgba(255,255,255,0.15); margin-top:4px;">Compartilhe o link com permissão de visualização</p>
                </div>
            </div>

            {{-- Automação --}}
            <div class="section section-cyan">
                <div class="section-title">
                    <div class="bar" style="background:#06b6d4;"></div>
                    <h2>Automação</h2>
                </div>
                <div class="field">
                    <label>Tem site que envia leads? (integração API)</label>
                    <div class="radio-group">
                        <label><input type="radio" name="has_site_leads" value="sim"> Sim</label>
                        <label><input type="radio" name="has_site_leads" value="nao"> Não</label>
                        <label><input type="radio" name="has_site_leads" value="nao_sei"> Não sei</label>
                    </div>
                </div>
                <div class="field">
                    <label>Mensagem automática para novos contatos (referência)</label>
                    <textarea name="auto_message" rows="3" placeholder="Ex: Olá! Obrigado por entrar em contato com a Machinery Prime. Em que podemos ajudá-lo?"></textarea>
                </div>
                <div class="field">
                    <label>Deseja follow-up automático se o cliente não responder?</label>
                    <div class="radio-group">
                        <label><input type="radio" name="want_followup" value="sim"> Sim</label>
                        <label><input type="radio" name="want_followup" value="nao"> Não</label>
                    </div>
                </div>
            </div>

            {{-- Contato --}}
            <div class="section section-green">
                <div class="section-title">
                    <div class="bar" style="background:#b2ff00;"></div>
                    <h2>Contato para Implementação</h2>
                </div>
                <div class="field-row-3">
                    <div class="field">
                        <label>Nome do responsável</label>
                        <input type="text" name="contact_name" placeholder="Seu nome">
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="contact_email" placeholder="email@empresa.com">
                    </div>
                    <div class="field">
                        <label>Telefone</label>
                        <input type="text" name="contact_phone" placeholder="(11) 99999-9999">
                    </div>
                </div>
                <div class="field">
                    <label>Observações adicionais</label>
                    <textarea name="notes" rows="3" placeholder="Algo mais que gostaria de nos informar?"></textarea>
                </div>
            </div>

            <button type="submit" class="btn">Enviar informações</button>
        </form>
    @endif
</div>
</body>
</html>
