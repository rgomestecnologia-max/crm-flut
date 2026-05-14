<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy / Politica de Privacidade — CRM Flut</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:#080C16; color:rgba(255,255,255,0.8); min-height:100vh; }
        .container { max-width:720px; margin:0 auto; padding:40px 20px 60px; }
        .logo { text-align:center; margin-bottom:32px; }
        .logo img { height:36px; }
        h1 { font-family:'Syne',sans-serif; font-size:24px; font-weight:800; color:white; margin-bottom:4px; }
        .subtitle { font-size:16px; color:rgba(255,255,255,0.4); margin-bottom:8px; font-family:'Syne',sans-serif; }
        .updated { font-size:12px; color:rgba(255,255,255,0.3); margin-bottom:32px; }
        h2 { font-family:'Syne',sans-serif; font-size:16px; font-weight:700; color:#b2ff00; margin:28px 0 10px; }
        p, li { font-size:14px; line-height:1.8; color:rgba(255,255,255,0.65); margin-bottom:10px; }
        ul { padding-left:20px; margin-bottom:14px; }
        a { color:#b2ff00; }
        .lang-toggle { text-align:center; margin-bottom:24px; }
        .lang-toggle a { display:inline-block; padding:6px 16px; border:1px solid rgba(255,255,255,0.1); border-radius:8px; font-size:12px; color:rgba(255,255,255,0.5); text-decoration:none; margin:0 4px; }
        .lang-toggle a.active { border-color:rgba(178,255,0,0.3); color:#b2ff00; }
        .section-en { display:none; }
        .legal-entity { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:10px; padding:16px; margin:16px 0; font-size:13px; }
        .footer { margin-top:40px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.06); font-size:12px; color:rgba(255,255,255,0.25); text-align:center; }
    </style>
    <script>
        function switchLang(lang) {
            document.querySelectorAll('.section-pt, .section-en').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.section-' + lang).forEach(el => el.style.display = 'block');
            document.querySelectorAll('.lang-toggle a').forEach(a => a.classList.remove('active'));
            document.querySelector('.lang-toggle a[data-lang="' + lang + '"]').classList.add('active');
        }
    </script>
</head>
<body>
<div class="container">
    <div class="logo"><img src="/images/logo-flut.webp" alt="CRM Flut"></div>

    <div class="lang-toggle">
        <a href="#" data-lang="pt" class="active" onclick="switchLang('pt'); return false;">Portugues</a>
        <a href="#" data-lang="en" onclick="switchLang('en'); return false;">English</a>
    </div>

    {{-- ==================== PORTUGUES ==================== --}}
    <div class="section-pt">
        <h1>Politica de Privacidade</h1>
        <p class="updated">Ultima atualizacao: 14 de maio de 2026</p>

        <div class="legal-entity">
            <strong>Razao Social:</strong> ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA<br>
            <strong>CNPJ:</strong> 46.724.626/0001-41<br>
            <strong>Nome Fantasia:</strong> Flut / CRM Flut<br>
            <strong>Site:</strong> crm.flut.com.br
        </div>

        <p>A <strong>ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA</strong> (CNPJ 46.724.626/0001-41), operando sob o nome fantasia <strong>Flut</strong> ("nos", "nosso"), desenvolve e opera a plataforma <strong>CRM Flut</strong> (crm.flut.com.br). Esta politica descreve como coletamos, usamos, armazenamos e protegemos suas informacoes pessoais.</p>

        <h2>1. Uso da WhatsApp Business Platform</h2>
        <p>O CRM Flut utiliza a <strong>WhatsApp Business Platform (Cloud API)</strong> fornecida pela <strong>Meta Platforms, Inc.</strong> para processar mensagens entre empresas e seus clientes. Ao utilizar nossos servicos, os dados de mensagens sao processados em conformidade com as <a href="https://www.whatsapp.com/legal/business-policy" target="_blank">Politicas Comerciais do WhatsApp</a> e a <a href="https://www.facebook.com/privacy/policy/" target="_blank">Politica de Privacidade da Meta</a>.</p>

        <h2>2. Informacoes que coletamos</h2>
        <ul>
            <li><strong>Dados de cadastro:</strong> nome, e-mail, telefone e empresa, fornecidos durante o registro ou onboarding.</li>
            <li><strong>Dados de uso:</strong> interacoes com a plataforma, logs de acesso, enderecos IP e tipo de navegador.</li>
            <li><strong>Mensagens do WhatsApp:</strong> conteudo de conversas processadas pela plataforma via WhatsApp Business API para fins de atendimento ao cliente.</li>
            <li><strong>Dados de integracao:</strong> informacoes recebidas via Meta WhatsApp Cloud API conforme configurado por cada empresa cliente.</li>
        </ul>

        <h2>3. Como usamos suas informacoes</h2>
        <ul>
            <li>Fornecer, operar e manter a plataforma CRM Flut.</li>
            <li>Processar e gerenciar atendimentos via WhatsApp Business API.</li>
            <li>Enviar notificacoes relacionadas ao servico (push, e-mail).</li>
            <li>Melhorar a experiencia do usuario e a performance da plataforma.</li>
            <li>Cumprir obrigacoes legais e regulatorias.</li>
        </ul>

        <h2>4. Compartilhamento de dados</h2>
        <p>Nao vendemos, alugamos ou compartilhamos seus dados pessoais com terceiros para fins de marketing. Podemos compartilhar dados com:</p>
        <ul>
            <li><strong>Meta Platforms, Inc.:</strong> dados de mensagens processados via WhatsApp Business Platform, conforme necessario para o funcionamento do servico.</li>
            <li><strong>Provedores de infraestrutura:</strong> hospedagem e armazenamento, exclusivamente para operacao da plataforma.</li>
            <li><strong>Obrigacoes legais:</strong> quando exigido por lei, ordem judicial ou autoridade competente.</li>
        </ul>

        <h2>5. Armazenamento e seguranca</h2>
        <p>Seus dados sao armazenados em servidores seguros com criptografia em transito (TLS/SSL). Implementamos medidas tecnicas e organizacionais para proteger contra acesso nao autorizado, perda ou destruicao de dados.</p>

        <h2>6. Retencao de dados</h2>
        <p>Mantemos seus dados pelo tempo necessario para fornecer o servico ou conforme exigido por lei. Voce pode solicitar a exclusao dos seus dados a qualquer momento (veja secao 8).</p>

        <h2>7. Seus direitos (LGPD)</h2>
        <p>Conforme a Lei Geral de Protecao de Dados (Lei 13.709/2018), voce tem direito a:</p>
        <ul>
            <li>Acessar seus dados pessoais.</li>
            <li>Corrigir dados incompletos ou desatualizados.</li>
            <li>Solicitar a exclusao dos seus dados.</li>
            <li>Revogar consentimento a qualquer momento.</li>
            <li>Solicitar portabilidade dos dados.</li>
            <li>Obter informacoes sobre o compartilhamento dos seus dados.</li>
        </ul>

        <h2>8. Exclusao de dados</h2>
        <p>Para solicitar a exclusao dos seus dados, acesse <a href="/data-deletion">crm.flut.com.br/data-deletion</a> ou envie um e-mail para <strong>privacidade@flut.com.br</strong>. Solicitacoes serao processadas em ate 15 dias uteis.</p>

        <h2>9. Cookies</h2>
        <p>Utilizamos cookies essenciais para funcionamento da plataforma (sessao, autenticacao). Nao utilizamos cookies de rastreamento de terceiros.</p>

        <h2>10. Alteracoes nesta politica</h2>
        <p>Podemos atualizar esta politica periodicamente. Alteracoes significativas serao comunicadas por e-mail ou aviso na plataforma.</p>

        <h2>11. Contato</h2>
        <ul>
            <li><strong>E-mail:</strong> privacidade@flut.com.br</li>
            <li><strong>Site:</strong> <a href="https://crm.flut.com.br">crm.flut.com.br</a></li>
        </ul>
    </div>

    {{-- ==================== ENGLISH ==================== --}}
    <div class="section-en">
        <h1>Privacy Policy</h1>
        <p class="updated">Last updated: May 14, 2026</p>

        <div class="legal-entity">
            <strong>Legal Entity:</strong> ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA<br>
            <strong>Tax ID (CNPJ):</strong> 46.724.626/0001-41<br>
            <strong>Trade Name:</strong> Flut / CRM Flut<br>
            <strong>Website:</strong> crm.flut.com.br
        </div>

        <p><strong>ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA</strong> (CNPJ 46.724.626/0001-41), operating under the trade name <strong>Flut</strong> ("we", "our", "us"), develops and operates the <strong>CRM Flut</strong> platform (crm.flut.com.br). This policy describes how we collect, use, store, and protect your personal information.</p>

        <h2>1. Use of WhatsApp Business Platform</h2>
        <p>CRM Flut uses the <strong>WhatsApp Business Platform (Cloud API)</strong> provided by <strong>Meta Platforms, Inc.</strong> to process messages between businesses and their customers. When using our services, message data is processed in accordance with <a href="https://www.whatsapp.com/legal/business-policy" target="_blank">WhatsApp Business Policy</a> and <a href="https://www.facebook.com/privacy/policy/" target="_blank">Meta's Privacy Policy</a>.</p>

        <h2>2. Information we collect</h2>
        <ul>
            <li><strong>Registration data:</strong> name, email, phone number, and company, provided during registration or onboarding.</li>
            <li><strong>Usage data:</strong> platform interactions, access logs, IP addresses, and browser type.</li>
            <li><strong>WhatsApp messages:</strong> message content processed through the platform via WhatsApp Business API for customer service purposes.</li>
            <li><strong>Integration data:</strong> information received via Meta WhatsApp Cloud API as configured by each client company.</li>
        </ul>

        <h2>3. How we use your information</h2>
        <ul>
            <li>Provide, operate, and maintain the CRM Flut platform.</li>
            <li>Process and manage customer service interactions via WhatsApp Business API.</li>
            <li>Send service-related notifications (push, email).</li>
            <li>Improve user experience and platform performance.</li>
            <li>Comply with legal and regulatory obligations.</li>
        </ul>

        <h2>4. Data sharing</h2>
        <p>We do not sell, rent, or share your personal data with third parties for marketing purposes. We may share data with:</p>
        <ul>
            <li><strong>Meta Platforms, Inc.:</strong> message data processed via WhatsApp Business Platform, as necessary for service operation.</li>
            <li><strong>Infrastructure providers:</strong> hosting and storage services, exclusively for platform operation.</li>
            <li><strong>Legal obligations:</strong> when required by law, court order, or competent authority.</li>
        </ul>

        <h2>5. Storage and security</h2>
        <p>Your data is stored on secure servers with encryption in transit (TLS/SSL). We implement technical and organizational measures to protect against unauthorized access, loss, or destruction of data.</p>

        <h2>6. Data retention</h2>
        <p>We retain your data for as long as necessary to provide the service or as required by law. You may request deletion of your data at any time (see section 8).</p>

        <h2>7. Your rights (LGPD)</h2>
        <p>Under Brazil's General Data Protection Law (Lei 13.709/2018 — LGPD), you have the right to:</p>
        <ul>
            <li>Access your personal data.</li>
            <li>Correct incomplete or outdated data.</li>
            <li>Request deletion of your data.</li>
            <li>Revoke consent at any time.</li>
            <li>Request data portability.</li>
            <li>Obtain information about data sharing.</li>
        </ul>

        <h2>8. Data deletion</h2>
        <p>To request deletion of your data, visit <a href="/data-deletion">crm.flut.com.br/data-deletion</a> or email <strong>privacidade@flut.com.br</strong>. Requests will be processed within 15 business days.</p>

        <h2>9. Cookies</h2>
        <p>We use essential cookies for platform operation (session, authentication). We do not use third-party tracking cookies.</p>

        <h2>10. Changes to this policy</h2>
        <p>We may update this policy periodically. Significant changes will be communicated via email or platform notice.</p>

        <h2>11. Contact</h2>
        <ul>
            <li><strong>Email:</strong> privacidade@flut.com.br</li>
            <li><strong>Website:</strong> <a href="https://crm.flut.com.br">crm.flut.com.br</a></li>
        </ul>
    </div>

    <div class="footer">
        ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA — CNPJ 46.724.626/0001-41 &copy; {{ date('Y') }}
    </div>
</div>
</body>
</html>
