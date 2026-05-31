(function(){
  const script = document.currentScript;
  const widgetId = new URL(script.src).searchParams.get('id');
  if (!widgetId) return;

  const API = script.src.split('/js/')[0] + '/api/flut-chat/' + widgetId;

  let config = null, steps = [], currentStep = null, collected = {}, chatOpen = false, aiMode = false, aiMessages = [];
  let liveConvId = null, liveLastMsgId = 0, livePollTimer = null;

  // ── Styles ──
  const css = document.createElement('style');
  css.textContent = `
    #flut-chat-btn{position:fixed;z-index:99998;width:60px;height:60px;border-radius:50%;border:none;cursor:pointer;box-shadow:0 4px 20px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;transition:transform .2s}
    #flut-chat-btn:hover{transform:scale(1.1)}
    #flut-chat-box{position:fixed;z-index:99999;width:370px;max-width:calc(100vw - 24px);height:520px;max-height:calc(100vh - 100px);border-radius:16px;overflow:hidden;display:none;flex-direction:column;box-shadow:0 16px 60px rgba(0,0,0,0.4);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif}
    #flut-chat-box.open{display:flex}
    #flut-chat-header{padding:16px 18px;display:flex;align-items:center;gap:12px;flex-shrink:0}
    #flut-chat-header .info h3{font-size:15px;font-weight:700;color:#fff;margin:0}
    #flut-chat-header .info p{font-size:11px;color:rgba(255,255,255,.7);margin:2px 0 0}
    #flut-chat-header .close{background:none;border:none;color:rgba(255,255,255,.6);cursor:pointer;font-size:20px;margin-left:auto}
    #flut-chat-messages{flex:1;overflow-y:auto;padding:16px;background:#fff;display:flex;flex-direction:column;gap:10px}
    .fc-msg{max-width:85%;padding:10px 14px;border-radius:14px;font-size:13px;line-height:1.5;animation:fcFade .3s ease}
    .fc-bot{background:#f0f0f0;color:#333;border-bottom-left-radius:4px;align-self:flex-start}
    .fc-user{color:#fff;border-bottom-right-radius:4px;align-self:flex-end}
    .fc-options{display:flex;flex-direction:column;gap:6px;align-self:flex-start;max-width:85%}
    .fc-opt-btn{padding:8px 16px;border-radius:20px;border:2px solid;background:#fff;cursor:pointer;font-size:12px;font-weight:600;transition:all .2s}
    .fc-opt-btn:hover{color:#fff!important}
    .fc-typing{display:flex;gap:4px;align-self:flex-start;padding:10px 14px;background:#f0f0f0;border-radius:14px}
    .fc-dot{width:6px;height:6px;border-radius:50%;background:#bbb;animation:fcBounce 1.2s infinite}
    .fc-dot:nth-child(2){animation-delay:.2s}.fc-dot:nth-child(3){animation-delay:.4s}
    #flut-chat-input{display:flex;align-items:center;gap:8px;padding:12px 14px;background:#fff;border-top:1px solid #eee;flex-shrink:0}
    #flut-chat-input input,#flut-chat-input select{flex:1;border:2px solid #e5e5e5;border-radius:24px;padding:10px 16px;font-size:13px;outline:none;transition:border-color .2s;background:#fff;color:#333;font-family:inherit}
    #flut-chat-input select{cursor:pointer;appearance:auto}
    #flut-chat-input button{width:38px;height:38px;border-radius:50%;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    @keyframes fcFade{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
    @keyframes fcBounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}
    @media(max-width:480px){#flut-chat-box{width:100vw;height:100vh;max-height:100vh;border-radius:0;bottom:0!important;right:0!important;left:0!important}}
  `;
  document.head.appendChild(css);

  // ── Load config ──
  fetch(API + '/config').then(r => r.json()).then(data => {
    if (data.error) return;
    config = data.widget;
    steps = data.steps || [];
    if (steps.length) currentStep = steps.find(s => s.sort_order === Math.min(...steps.map(x => x.sort_order)));
    render();
  }).catch(() => {});

  function render() {
    if (!config) return;
    const pos = config.position === 'bottom-left' ? 'left:20px' : 'right:20px';

    // Button
    const btn = document.createElement('button');
    btn.id = 'flut-chat-btn';
    btn.setAttribute('style', `bottom:20px;${pos};background:#25D366`);
    btn.innerHTML = `<svg width="32" height="32" fill="white" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>`;
    btn.onclick = () => toggle();
    document.body.appendChild(btn);

    // Chat box
    const box = document.createElement('div');
    box.id = 'flut-chat-box';
    box.setAttribute('style', `bottom:90px;${pos}`);
    box.innerHTML = `
      <div id="flut-chat-header" style="background:${config.color}">
        ${config.avatar_url ? `<img src="${config.avatar_url}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.3)">` : config.logo_url ? `<img src="${config.logo_url}" style="width:36px;height:36px;border-radius:50%;object-fit:cover">` : ''}
        <div class="info"><h3>${esc(config.title)}</h3>${config.subtitle ? `<p>${esc(config.subtitle)}</p>` : ''}</div>
        <button class="close" onclick="document.getElementById('flut-chat-box').classList.remove('open');document.getElementById('flut-chat-btn').style.display='flex'">✕</button>
      </div>
      <div id="flut-chat-messages"></div>
      <div id="flut-chat-input" style="display:none">
        <input type="text" placeholder="Sua resposta..." id="flut-chat-field">
        <button id="flut-chat-send" style="background:${config.color}">
          <svg width="18" height="18" fill="white" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
        </button>
      </div>
    `;
    document.body.appendChild(box);

    document.getElementById('flut-chat-send').onclick = sendInput;
    document.getElementById('flut-chat-field').addEventListener('keydown', e => { if (e.key === 'Enter') sendInput(); });

    // trigger-flut class support
    document.querySelectorAll('.trigger-flut').forEach(el => {
      el.addEventListener('click', e => { e.preventDefault(); toggle(); });
    });
  }

  function toggle() {
    const box = document.getElementById('flut-chat-box');
    const btn = document.getElementById('flut-chat-btn');
    chatOpen = !chatOpen;
    if (chatOpen) {
      // Sem steps: abre WhatsApp direto
      if (!steps.length && config.whatsapp_number) {
        const msg = config.whatsapp_message || 'Olá!';
        window.open(`https://wa.me/${config.whatsapp_number.replace(/\D/g,'')}?text=${encodeURIComponent(msg)}`, '_blank');
        chatOpen = false;
        return;
      }
      box.classList.add('open');
      btn.style.display = 'none';
      if (!document.getElementById('flut-chat-messages').children.length && currentStep) {
        // Inicia conversa ao vivo quando o chat abre
        if (!liveConvId) startLiveConversation();
        setTimeout(() => processStep(currentStep), 500);
      }
    } else {
      box.classList.remove('open');
      btn.style.display = 'flex';
    }
  }

  function processStep(step) {
    if (!step) return;

    if (step.type === 'message') {
      showTyping();
      setTimeout(() => { hideTyping(); addBot(step.content); goNext(step); }, 800);
    }
    else if (step.type === 'input') {
      showTyping();
      setTimeout(() => { hideTyping(); addBot(step.content); showInput(step); }, 800);
    }
    else if (step.type === 'options') {
      showTyping();
      setTimeout(() => { hideTyping(); addBot(step.content); showOptions(step); }, 800);
    }
    else if (step.type === 'select') {
      showTyping();
      setTimeout(() => { hideTyping(); addBot(step.content); showSelect(step); }, 800);
    }
    else if (step.type === 'action') {
      if (step.content) { showTyping(); setTimeout(() => { hideTyping(); addBot(step.content); setTimeout(() => doAction(step), 1000); }, 800); }
      else doAction(step);
    }
  }

  function goNext(step) {
    if (step.next_step_id) {
      const next = steps.find(s => s.id === step.next_step_id);
      if (next) setTimeout(() => processStep(next), 600);
    }
  }

  function showInput(step) {
    const input = document.getElementById('flut-chat-input');
    const field = document.getElementById('flut-chat-field');
    input.style.display = 'flex';
    field.placeholder = step.input_placeholder || 'Sua resposta...';
    field.dataset.key = step.input_key || '';
    field.dataset.stepId = step.id;
    field.value = '';
    field.focus();
  }

  function sendInput() {
    const field = document.getElementById('flut-chat-field');
    const val = field.value.trim();
    if (!val) return;

    if (aiMode) { sendAiMessage(val); return; }

    addUser(val);
    if (field.dataset.key) collected[field.dataset.key] = val;
    sendLiveMessage(val);
    field.value = '';
    document.getElementById('flut-chat-input').style.display = 'none';

    const step = steps.find(s => s.id === parseInt(field.dataset.stepId));
    if (step) goNext(step);
  }

  function showOptions(step) {
    const msgs = document.getElementById('flut-chat-messages');
    const div = document.createElement('div');
    div.className = 'fc-options';
    (step.options || []).forEach(opt => {
      const btn = document.createElement('button');
      btn.className = 'fc-opt-btn';
      btn.style.borderColor = config.color;
      btn.style.color = config.color;
      btn.onmouseover = () => { btn.style.background = config.color; };
      btn.onmouseout = () => { btn.style.background = '#fff'; btn.style.color = config.color; };
      btn.textContent = opt.label;
      btn.onclick = () => {
        addUser(opt.label);
        sendLiveMessage(opt.label);
        div.remove();
        collected['opcao'] = opt.label;
        if (opt.next_step_id) {
          const next = steps.find(s => s.id === parseInt(opt.next_step_id));
          if (next) setTimeout(() => processStep(next), 400);
        }
      };
      div.appendChild(btn);
    });
    msgs.appendChild(div);
    scroll();
  }

  function showSelect(step) {
    const inputArea = document.getElementById('flut-chat-input');
    const field = document.getElementById('flut-chat-field');
    // Esconde o input de texto e cria select
    field.style.display = 'none';
    // Remove select anterior se existir
    const old = document.getElementById('flut-chat-select');
    if (old) old.remove();

    const sel = document.createElement('select');
    sel.id = 'flut-chat-select';
    sel.innerHTML = '<option value="" disabled selected>-- Selecione uma opção --</option>';
    (step.options || []).forEach((opt, i) => {
      const o = document.createElement('option');
      o.value = i;
      o.textContent = opt.label;
      sel.appendChild(o);
    });
    sel.dataset.stepId = step.id;
    inputArea.insertBefore(sel, inputArea.querySelector('button'));
    inputArea.style.display = 'flex';

    // Override do botão enviar para o select
    const sendBtn = document.getElementById('flut-chat-send');
    const origClick = sendBtn.onclick;
    const handleSelectSend = () => {
      if (!sel.value) return;
      const opt = step.options[parseInt(sel.value)];
      addUser(opt.label);
      sendLiveMessage(opt.label);
      collected['opcao'] = opt.label;
      sel.remove();
      field.style.display = '';
      inputArea.style.display = 'none';
      sendBtn.onclick = origClick;
      if (opt.next_step_id) {
        const next = steps.find(s => s.id === parseInt(opt.next_step_id));
        if (next) setTimeout(() => processStep(next), 400);
      }
    };
    sendBtn.onclick = handleSelectSend;
    scroll();
  }

  function doAction(step) {
    const action = step.action_type;

    // Save lead
    fetch(API + '/lead', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ data: collected, action: action, page_url: window.location.href })
    }).catch(() => {});

    if (action === 'whatsapp') {
      const num = config.whatsapp_number || step.action_value || '';
      let msg = config.whatsapp_message || '';
      Object.keys(collected).forEach(k => { msg = msg.replace(`{${k}}`, collected[k]); });
      if (!msg) msg = 'Olá! ' + Object.entries(collected).map(([k,v]) => `${k}: ${v}`).join(', ');
      window.open(`https://wa.me/${num.replace(/\D/g,'')}?text=${encodeURIComponent(msg)}`, '_blank');
    }
    else if (action === 'ia') {
      aiMode = true;
      addBot('Agora você está conversando com nossa IA. Como posso ajudar?');
      document.getElementById('flut-chat-input').style.display = 'flex';
      document.getElementById('flut-chat-field').placeholder = 'Digite sua mensagem...';
      document.getElementById('flut-chat-field').focus();
      // Inicia conversa ao vivo para agentes acompanharem
      startLiveConversation();
    }
    else if (action === 'redirect') {
      window.open(step.action_value || '/', '_blank');
    }
    else if (action === 'lead') {
      addBot('Obrigado! Suas informações foram recebidas. Entraremos em contato em breve! 😊');
    }
  }

  // ── Chat ao vivo ──
  function getVisitorId() {
    let vid = localStorage.getItem('flut_visitor_id');
    if (!vid) { vid = 'v_' + Math.random().toString(36).substr(2, 12) + Date.now().toString(36); localStorage.setItem('flut_visitor_id', vid); }
    return vid;
  }

  function startLiveConversation() {
    const visitorName = collected['nome'] || collected['name'] || null;
    fetch(API + '/conversation', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ visitor_id: getVisitorId(), visitor_name: visitorName })
    }).then(r => r.json()).then(data => {
      if (data.conversation_id) {
        liveConvId = data.conversation_id;
        // Inicia polling para receber mensagens do agente
        if (livePollTimer) clearInterval(livePollTimer);
        livePollTimer = setInterval(pollLiveMessages, 3000);
      }
    }).catch(() => {});
  }

  function sendLiveMessage(text) {
    if (!liveConvId || !text) return;
    fetch(API + '/conversation/' + liveConvId + '/message', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ content: text, visitor_name: collected['nome'] || collected['name'] || null })
    }).catch(() => {});
  }

  function pollLiveMessages() {
    if (!liveConvId) return;
    fetch(API + '/conversation/' + liveConvId + '/messages?after=' + liveLastMsgId)
      .then(r => r.json())
      .then(data => {
        (data.messages || []).forEach(msg => {
          if (msg.id > liveLastMsgId) liveLastMsgId = msg.id;
          // Só mostra mensagens do agente (visitor msgs já foram adicionadas localmente)
          if (msg.sender_type === 'agent') {
            addBot(msg.content);
          }
        });
      }).catch(() => {});
  }

  function sendAiMessage(text) {
    addUser(text);
    document.getElementById('flut-chat-field').value = '';
    aiMessages.push({role:'user', content: text});
    // Salva na conversa ao vivo
    sendLiveMessage(text);
    showTyping();

    fetch(API + '/ai', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ messages: aiMessages })
    }).then(r => r.json()).then(data => {
      hideTyping();
      const reply = data.reply || 'Desculpe, tente novamente.';
      aiMessages.push({role:'assistant', content: reply});
      addBot(reply);
    }).catch(() => { hideTyping(); addBot('Erro ao conectar com a IA.'); });
  }

  // ── UI Helpers ──
  function addBot(text) {
    const msgs = document.getElementById('flut-chat-messages');
    const wrap = document.createElement('div');
    wrap.style.cssText = 'display:flex;align-items:flex-start;gap:8px;align-self:flex-start;max-width:90%';
    const avatarSrc = config.avatar_url || null;
    if (avatarSrc) {
      wrap.innerHTML = `<img src="${avatarSrc}" style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0;margin-top:2px">`;
    } else {
      wrap.innerHTML = `<div style="width:30px;height:30px;border-radius:50%;background:#25D366;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px"><svg width="16" height="16" fill="white" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg></div>`;
    }
    const bubble = document.createElement('div');
    bubble.className = 'fc-msg fc-bot';
    bubble.style.margin = '0';
    bubble.innerHTML = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
    wrap.appendChild(bubble);
    msgs.appendChild(wrap);
    scroll();
  }
  function addUser(text) {
    const msgs = document.getElementById('flut-chat-messages');
    const div = document.createElement('div');
    div.className = 'fc-msg fc-user';
    div.style.background = config.color;
    div.textContent = text;
    msgs.appendChild(div);
    scroll();
  }
  function showTyping() {
    const msgs = document.getElementById('flut-chat-messages');
    let t = document.getElementById('fc-typing');
    if (!t) {
      t = document.createElement('div');
      t.id = 'fc-typing';
      t.style.cssText = 'display:flex;align-items:flex-start;gap:8px;align-self:flex-start';
      const avatarSrc = config.avatar_url || null;
      if (avatarSrc) {
        t.innerHTML = `<img src="${avatarSrc}" style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0;margin-top:2px">`;
      } else {
        t.innerHTML = `<div style="width:30px;height:30px;border-radius:50%;background:#25D366;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px"><svg width="16" height="16" fill="white" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg></div>`;
      }
      const dots = document.createElement('div');
      dots.className = 'fc-typing';
      dots.innerHTML = '<div class="fc-dot"></div><div class="fc-dot"></div><div class="fc-dot"></div>';
      t.appendChild(dots);
      msgs.appendChild(t);
    }
    scroll();
  }
  function hideTyping() { const t = document.getElementById('fc-typing'); if (t) t.remove(); }
  function scroll() { const m = document.getElementById('flut-chat-messages'); if (m) m.scrollTop = m.scrollHeight; }
  function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
})();
