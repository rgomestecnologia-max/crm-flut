<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service / Termos de Serviço — CRM Flut</title>
    <meta name="description" content="Terms of Service for CRM Flut platform. Rules and conditions for using our WhatsApp Business API customer service and CRM solution.">
    <meta property="og:title" content="Terms of Service — CRM Flut">
    <meta property="og:description" content="Terms of Service for CRM Flut — WhatsApp Business Platform customer service and CRM solution.">
    <meta property="og:url" content="https://crm.flut.com.br/terms">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="CRM Flut">
    <meta property="og:image" content="https://crm.flut.com.br/images/logo-flut-large.png">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:#080C16; color:rgba(255,255,255,0.8); min-height:100vh; }
        .container { max-width:720px; margin:0 auto; padding:40px 20px 60px; }
        .logo { text-align:center; margin-bottom:32px; }
        .logo img { height:36px; }
        h1 { font-family:'Syne',sans-serif; font-size:24px; font-weight:800; color:white; margin-bottom:4px; }
        .updated { font-size:12px; color:rgba(255,255,255,0.3); margin-bottom:32px; }
        h2 { font-family:'Syne',sans-serif; font-size:16px; font-weight:700; color:#b2ff00; margin:28px 0 10px; }
        p, li { font-size:14px; line-height:1.8; color:rgba(255,255,255,0.65); margin-bottom:10px; }
        ul { padding-left:20px; margin-bottom:14px; }
        a { color:#b2ff00; }
        .lang-toggle { text-align:center; margin-bottom:24px; }
        .lang-toggle a { display:inline-block; padding:6px 16px; border:1px solid rgba(255,255,255,0.1); border-radius:8px; font-size:12px; color:rgba(255,255,255,0.5); text-decoration:none; margin:0 4px; cursor:pointer; }
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
        <a data-lang="pt" class="active" onclick="switchLang('pt')">Português</a>
        <a data-lang="en" onclick="switchLang('en')">English</a>
    </div>

    {{-- ==================== PORTUGUÊS ==================== --}}
    <div class="section-pt">
        <h1>Termos de Serviço</h1>
        <p class="updated">Última atualização: 14 de maio de 2026</p>

        <div class="legal-entity">
            <strong>Razão Social:</strong> ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA<br>
            <strong>CNPJ:</strong> 46.724.626/0001-41<br>
            <strong>Nome Fantasia:</strong> Flut / CRM Flut
        </div>

        <p>Estes Termos de Serviço ("Termos") regem o uso da plataforma <strong>CRM Flut</strong> operada pela <strong>ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA</strong> (CNPJ 46.724.626/0001-41). Ao acessar ou utilizar a plataforma, você concorda com estes Termos.</p>

        <h2>1. Descrição do serviço</h2>
        <p>O CRM Flut é uma plataforma SaaS de atendimento ao cliente via WhatsApp, gestão de relacionamento (CRM), disparos de mensagens em massa e inteligência artificial para automação de atendimento. A plataforma utiliza a <strong>WhatsApp Business Platform (Cloud API)</strong> fornecida pela Meta Platforms, Inc.</p>

        <h2>2. Elegibilidade</h2>
        <p>Para utilizar a plataforma, você deve ter pelo menos 18 anos e capacidade legal para celebrar contratos. O uso é destinado exclusivamente a fins comerciais e empresariais.</p>

        <h2>3. Conta do usuário</h2>
        <ul>
            <li>Você é responsável por manter a confidencialidade das suas credenciais de acesso.</li>
            <li>Você é responsável por todas as atividades realizadas em sua conta.</li>
            <li>Você deve notificar imediatamente sobre qualquer uso não autorizado da sua conta.</li>
        </ul>

        <h2>4. Uso aceitável</h2>
        <p>Você concorda em não:</p>
        <ul>
            <li>Utilizar a plataforma para envio de spam ou mensagens não solicitadas.</li>
            <li>Violar leis aplicáveis, incluindo a LGPD e as <a href="https://www.whatsapp.com/legal/business-policy" target="_blank">Políticas Comerciais do WhatsApp</a>.</li>
            <li>Transmitir conteúdo ilegal, ofensivo, difamatório ou que viole direitos de terceiros.</li>
            <li>Tentar acessar áreas restritas da plataforma ou sistemas de outros usuários.</li>
            <li>Realizar engenharia reversa, descompilar ou desmontar a plataforma.</li>
        </ul>

        <h2>5. Planos e pagamento</h2>
        <ul>
            <li>Os preços e planos estão disponíveis em <a href="/pricing">crm.flut.com.br/pricing</a>.</li>
            <li>O pagamento é devido conforme o plano contratado (mensal).</li>
            <li>Reservamo-nos o direito de alterar preços mediante aviso prévio de 30 dias.</li>
        </ul>

        <h2>6. Propriedade intelectual</h2>
        <p>A plataforma CRM Flut, incluindo software, design, marcas e conteúdo, é propriedade exclusiva da ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA. Nenhum direito de propriedade intelectual é transferido ao usuário.</p>

        <h2>7. Limitação de responsabilidade</h2>
        <ul>
            <li>A plataforma é fornecida "como está", sem garantias de qualquer tipo.</li>
            <li>Não nos responsabilizamos por interrupções temporárias, perdas de dados causadas por terceiros ou uso indevido da plataforma.</li>
            <li>A responsabilidade total está limitada ao valor pago pelo usuário nos últimos 12 meses.</li>
        </ul>

        <h2>8. Privacidade e proteção de dados</h2>
        <p>O tratamento de dados pessoais é regido pela nossa <a href="/privacy">Política de Privacidade</a>. Para exclusão de dados, acesse <a href="/data-deletion">crm.flut.com.br/data-deletion</a>.</p>

        <h2>9. Suspensão e rescisão</h2>
        <ul>
            <li>Podemos suspender ou encerrar o acesso à plataforma em caso de violação destes Termos.</li>
            <li>O usuário pode cancelar sua conta a qualquer momento entrando em contato com o suporte.</li>
            <li>Após o cancelamento, os dados serão retidos conforme a Política de Privacidade.</li>
        </ul>

        <h2>10. Alterações nos termos</h2>
        <p>Podemos atualizar estes Termos periodicamente. Alterações significativas serão comunicadas com antecedência. O uso continuado da plataforma após alterações constitui aceitação dos novos Termos.</p>

        <h2>11. Legislação aplicável</h2>
        <p>Estes Termos são regidos pelas leis da República Federativa do Brasil. Qualquer disputa será resolvida no foro da comarca de São Paulo/SP.</p>

        <h2>12. Contato</h2>
        <ul>
            <li><strong>E-mail:</strong> contato@flut.com.br</li>
            <li><strong>Site:</strong> <a href="https://crm.flut.com.br">crm.flut.com.br</a></li>
        </ul>
    </div>

    {{-- ==================== ENGLISH ==================== --}}
    <div class="section-en">
        <h1>Terms of Service</h1>
        <p class="updated">Last updated: May 14, 2026</p>

        <div class="legal-entity">
            <strong>Legal Entity:</strong> ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA<br>
            <strong>Tax ID (CNPJ):</strong> 46.724.626/0001-41<br>
            <strong>Trade Name:</strong> Flut / CRM Flut
        </div>

        <p>These Terms of Service ("Terms") govern the use of the <strong>CRM Flut</strong> platform operated by <strong>ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA</strong> (CNPJ 46.724.626/0001-41). By accessing or using the platform, you agree to these Terms.</p>

        <h2>1. Service description</h2>
        <p>CRM Flut is a SaaS platform for WhatsApp customer service, customer relationship management (CRM), mass messaging, and AI-powered service automation. The platform uses the <strong>WhatsApp Business Platform (Cloud API)</strong> provided by Meta Platforms, Inc.</p>

        <h2>2. Eligibility</h2>
        <p>To use the platform, you must be at least 18 years old and have legal capacity to enter into contracts. Use is intended exclusively for commercial and business purposes.</p>

        <h2>3. User account</h2>
        <ul>
            <li>You are responsible for maintaining the confidentiality of your login credentials.</li>
            <li>You are responsible for all activities conducted through your account.</li>
            <li>You must immediately notify us of any unauthorized use of your account.</li>
        </ul>

        <h2>4. Acceptable use</h2>
        <p>You agree not to:</p>
        <ul>
            <li>Use the platform for sending spam or unsolicited messages.</li>
            <li>Violate applicable laws, including LGPD and <a href="https://www.whatsapp.com/legal/business-policy" target="_blank">WhatsApp Business Policy</a>.</li>
            <li>Transmit illegal, offensive, defamatory content or content that violates third-party rights.</li>
            <li>Attempt to access restricted areas of the platform or other users' systems.</li>
            <li>Reverse engineer, decompile, or disassemble the platform.</li>
        </ul>

        <h2>5. Plans and payment</h2>
        <ul>
            <li>Pricing and plans are available at <a href="/pricing">crm.flut.com.br/pricing</a>.</li>
            <li>Payment is due according to the contracted plan (monthly).</li>
            <li>We reserve the right to change prices with 30 days' prior notice.</li>
        </ul>

        <h2>6. Intellectual property</h2>
        <p>The CRM Flut platform, including software, design, trademarks, and content, is the exclusive property of ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA. No intellectual property rights are transferred to the user.</p>

        <h2>7. Limitation of liability</h2>
        <ul>
            <li>The platform is provided "as is", without warranties of any kind.</li>
            <li>We are not liable for temporary interruptions, data loss caused by third parties, or misuse of the platform.</li>
            <li>Total liability is limited to the amount paid by the user in the last 12 months.</li>
        </ul>

        <h2>8. Privacy and data protection</h2>
        <p>Personal data processing is governed by our <a href="/privacy">Privacy Policy</a>. To request data deletion, visit <a href="/data-deletion">crm.flut.com.br/data-deletion</a>.</p>

        <h2>9. Suspension and termination</h2>
        <ul>
            <li>We may suspend or terminate access to the platform in case of violation of these Terms.</li>
            <li>Users may cancel their account at any time by contacting support.</li>
            <li>After cancellation, data will be retained as described in the Privacy Policy.</li>
        </ul>

        <h2>10. Changes to terms</h2>
        <p>We may update these Terms periodically. Significant changes will be communicated in advance. Continued use of the platform after changes constitutes acceptance of the new Terms.</p>

        <h2>11. Governing law</h2>
        <p>These Terms are governed by the laws of the Federative Republic of Brazil. Any disputes shall be resolved in the courts of São Paulo/SP, Brazil.</p>

        <h2>12. Contact</h2>
        <ul>
            <li><strong>Email:</strong> contato@flut.com.br</li>
            <li><strong>Website:</strong> <a href="https://crm.flut.com.br">crm.flut.com.br</a></li>
        </ul>
    </div>

    <div class="footer">
        ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA — CNPJ 46.724.626/0001-41 &copy; {{ date('Y') }}
    </div>
</div>
</body>
</html>
