<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Deletion / Exclusao de Dados — CRM Flut</title>
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
        ul, ol { padding-left:20px; margin-bottom:14px; }
        a { color:#b2ff00; }
        .lang-toggle { text-align:center; margin-bottom:24px; }
        .lang-toggle a { display:inline-block; padding:6px 16px; border:1px solid rgba(255,255,255,0.1); border-radius:8px; font-size:12px; color:rgba(255,255,255,0.5); text-decoration:none; margin:0 4px; }
        .lang-toggle a.active { border-color:rgba(178,255,0,0.3); color:#b2ff00; }
        .section-en { display:none; }
        .highlight { background:rgba(178,255,0,0.06); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:20px; margin:20px 0; }
        .highlight p { color:rgba(255,255,255,0.8); margin-bottom:6px; }
        .legal-entity { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:10px; padding:16px; margin:16px 0; font-size:13px; }
        .status-box { background:rgba(59,130,246,0.08); border:1px solid rgba(59,130,246,0.2); border-radius:12px; padding:20px; margin:20px 0; text-align:center; }
        .status-box p { color:rgba(255,255,255,0.7); }
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

    @if(isset($confirmation_code))
    <div class="status-box">
        <p><strong>Data deletion request received.</strong></p>
        <p>Confirmation code: <strong>{{ $confirmation_code }}</strong></p>
        <p>Your data will be deleted within 15 business days.</p>
    </div>
    @endif

    {{-- ==================== PORTUGUES ==================== --}}
    <div class="section-pt">
        <h1>Instrucoes para Exclusao de Dados</h1>
        <p class="updated">Ultima atualizacao: 14 de maio de 2026</p>

        <div class="legal-entity">
            <strong>Razao Social:</strong> ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA<br>
            <strong>CNPJ:</strong> 46.724.626/0001-41<br>
            <strong>Nome Fantasia:</strong> Flut / CRM Flut
        </div>

        <p>A <strong>ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA</strong> (CNPJ 46.724.626/0001-41), operando sob o nome fantasia <strong>Flut</strong>, respeita seu direito a privacidade e oferece meios simples para solicitar a exclusao dos seus dados pessoais armazenados na plataforma <strong>CRM Flut</strong>.</p>

        <h2>Quais dados armazenamos</h2>
        <p>Quando voce interage com empresas que utilizam o CRM Flut via WhatsApp Business API, podemos armazenar:</p>
        <ul>
            <li>Numero de telefone e nome de perfil do WhatsApp.</li>
            <li>Historico de mensagens trocadas com a empresa.</li>
            <li>Dados fornecidos durante o atendimento (nome, e-mail, etc.).</li>
        </ul>

        <h2>Como solicitar a exclusao</h2>
        <p>Voce pode solicitar a exclusao dos seus dados de duas formas:</p>

        <div class="highlight">
            <p><strong>Opcao 1 — Por e-mail</strong></p>
            <p>Envie um e-mail para <strong>privacidade@flut.com.br</strong> com o assunto "Exclusao de Dados" incluindo:</p>
            <ul>
                <li>Seu nome completo</li>
                <li>Numero de telefone associado ao WhatsApp</li>
                <li>Nome da empresa com quem interagiu (se souber)</li>
            </ul>
        </div>

        <div class="highlight">
            <p><strong>Opcao 2 — Pelo WhatsApp</strong></p>
            <p>Envie uma mensagem para o mesmo numero da empresa solicitando a exclusao dos seus dados. A empresa sera notificada e procedera com a remocao.</p>
        </div>

        <h2>Exclusao automatica via Facebook/Meta</h2>
        <p>Se voce conectou sua conta do Facebook a um servico que utiliza o CRM Flut, o Meta pode enviar automaticamente uma solicitacao de exclusao de dados em seu nome. Este processo e tratado automaticamente pela nossa plataforma.</p>

        <h2>Prazo para exclusao</h2>
        <p>Sua solicitacao sera processada em ate <strong>15 dias uteis</strong>. Voce recebera uma confirmacao por e-mail quando a exclusao for concluida.</p>

        <h2>O que sera excluido</h2>
        <ul>
            <li>Dados de contato (nome, telefone, e-mail).</li>
            <li>Historico de conversas e mensagens.</li>
            <li>Qualquer dado pessoal associado ao seu perfil.</li>
        </ul>

        <h2>Excecoes</h2>
        <p>Alguns dados podem ser retidos quando exigido por lei ou regulamentacao aplicavel (por exemplo, registros fiscais ou obrigacoes contratuais). Nesses casos, os dados serao mantidos pelo prazo legal minimo e depois excluidos.</p>

        <h2>Contato</h2>
        <ul>
            <li><strong>E-mail:</strong> privacidade@flut.com.br</li>
            <li><strong>Politica de Privacidade:</strong> <a href="/privacy">crm.flut.com.br/privacy</a></li>
            <li><strong>Termos de Servico:</strong> <a href="/terms">crm.flut.com.br/terms</a></li>
        </ul>
    </div>

    {{-- ==================== ENGLISH ==================== --}}
    <div class="section-en">
        <h1>Data Deletion Instructions</h1>
        <p class="updated">Last updated: May 14, 2026</p>

        <div class="legal-entity">
            <strong>Legal Entity:</strong> ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA<br>
            <strong>Tax ID (CNPJ):</strong> 46.724.626/0001-41<br>
            <strong>Trade Name:</strong> Flut / CRM Flut
        </div>

        <p><strong>ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA</strong> (CNPJ 46.724.626/0001-41), operating under the trade name <strong>Flut</strong>, respects your right to privacy and provides simple ways to request deletion of your personal data stored on the <strong>CRM Flut</strong> platform.</p>

        <h2>What data we store</h2>
        <p>When you interact with companies using CRM Flut via WhatsApp Business API, we may store:</p>
        <ul>
            <li>WhatsApp phone number and profile name.</li>
            <li>Message history exchanged with the company.</li>
            <li>Data provided during customer service (name, email, etc.).</li>
        </ul>

        <h2>How to request deletion</h2>
        <p>You can request deletion of your data in two ways:</p>

        <div class="highlight">
            <p><strong>Option 1 — By email</strong></p>
            <p>Send an email to <strong>privacidade@flut.com.br</strong> with the subject "Data Deletion" including:</p>
            <ul>
                <li>Your full name</li>
                <li>WhatsApp phone number</li>
                <li>Company name you interacted with (if known)</li>
            </ul>
        </div>

        <div class="highlight">
            <p><strong>Option 2 — Via WhatsApp</strong></p>
            <p>Send a message to the same company number requesting deletion of your data. The company will be notified and will proceed with the removal.</p>
        </div>

        <h2>Automatic deletion via Facebook/Meta</h2>
        <p>If you connected your Facebook account to a service that uses CRM Flut, Meta may automatically send a data deletion request on your behalf. This process is handled automatically by our platform.</p>

        <h2>Deletion timeframe</h2>
        <p>Your request will be processed within <strong>15 business days</strong>. You will receive an email confirmation when the deletion is complete.</p>

        <h2>What will be deleted</h2>
        <ul>
            <li>Contact data (name, phone, email).</li>
            <li>Conversation and message history.</li>
            <li>Any personal data associated with your profile.</li>
        </ul>

        <h2>Exceptions</h2>
        <p>Some data may be retained when required by law or applicable regulations (e.g., tax records or contractual obligations). In such cases, data will be kept for the minimum legal period and then deleted.</p>

        <h2>Contact</h2>
        <ul>
            <li><strong>Email:</strong> privacidade@flut.com.br</li>
            <li><strong>Privacy Policy:</strong> <a href="/privacy">crm.flut.com.br/privacy</a></li>
            <li><strong>Terms of Service:</strong> <a href="/terms">crm.flut.com.br/terms</a></li>
        </ul>
    </div>

    <div class="footer">
        ROGERIO SILVA GOMES TECNOLOGIA E INFORMATICA — CNPJ 46.724.626/0001-41 &copy; {{ date('Y') }}
    </div>
</div>
</body>
</html>
