@php
$cardStyle = "background:linear-gradient(145deg, rgba(17,24,39,0.9), rgba(11,15,28,0.95)); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:20px 24px; position:relative; overflow:hidden;";
@endphp

<div>
    {{-- Header --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <div>
            <h2 style="font-size:16px; font-weight:800; color:white; font-family:'Syne',sans-serif;">Templates de Campanha</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.25); margin-top:2px;">Crie templates reutilizáveis para WhatsApp e Email</p>
        </div>
        <button wire:click="openCreate"
                style="display:flex; align-items:center; gap:6px; padding:8px 16px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-size:12px; font-weight:700; border-radius:10px; border:none; cursor:pointer;">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Novo Template
        </button>
    </div>

    {{-- Modal criar/editar --}}
    @if($showForm)
    <div style="position:fixed; inset:0; z-index:999; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px);" wire:click.self="$set('showForm', false)">
        <div style="background:#0f1320; border:1px solid rgba(255,255,255,0.1); border-radius:16px; padding:24px; width:560px; max-width:92vw; max-height:85vh; overflow-y:auto;">
            <h3 style="font-size:15px; font-weight:700; color:white; margin-bottom:16px;">{{ $editingId ? 'Editar Template' : 'Novo Template' }}</h3>

            <div style="display:flex; gap:12px; margin-bottom:14px;">
                <div style="flex:1;">
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; display:block; margin-bottom:4px;">Nome *</label>
                    <input wire:model="name" type="text" placeholder="Ex: Promoção de Verão"
                           style="width:100%; padding:8px 12px; font-size:13px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:white; outline:none; box-sizing:border-box;">
                    @error('name') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                </div>
                <div style="width:140px;">
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; display:block; margin-bottom:4px;">Canal</label>
                    <select wire:model.live="channel"
                            style="width:100%; padding:8px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:white; outline:none;">
                        <option value="whatsapp">WhatsApp</option>
                        <option value="email">Email</option>
                    </select>
                </div>
            </div>

            @if($channel === 'email')
            <div style="display:flex; gap:12px; margin-bottom:14px;">
                <div style="flex:1;">
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; display:block; margin-bottom:4px;">Assunto do email</label>
                    <input wire:model="subject" type="text" placeholder="Ex: Novidades especiais para você!"
                           style="width:100%; padding:8px 12px; font-size:13px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:white; outline:none; box-sizing:border-box;">
                </div>
                <div style="width:60px;">
                    <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; display:block; margin-bottom:4px;">Cor</label>
                    <input wire:model="headerColor" type="color" style="width:100%; height:34px; border:none; border-radius:8px; cursor:pointer; background:transparent;">
                </div>
            </div>
            @endif

            {{-- Upload imagem --}}
            <div style="margin-bottom:14px;">
                <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; display:block; margin-bottom:4px;">Imagem</label>
                <input wire:model="imageUpload" type="file" accept="image/*"
                       style="width:100%; padding:6px; font-size:11px; color:rgba(255,255,255,0.4); background:rgba(255,255,255,0.04); border:1px dashed rgba(255,255,255,0.15); border-radius:8px; cursor:pointer; box-sizing:border-box;">
                @if($existingImage)
                <div style="margin-top:8px; position:relative; display:inline-block;">
                    <img src="{{ $existingImage }}" style="max-height:120px; border-radius:8px; border:1px solid rgba(255,255,255,0.1);">
                    <a href="{{ $existingImage }}" download style="position:absolute; bottom:6px; right:6px; width:28px; height:28px; border-radius:6px; background:rgba(0,0,0,0.6); display:flex; align-items:center; justify-content:center; text-decoration:none; backdrop-filter:blur(4px);" title="Baixar imagem">
                        <svg width="14" height="14" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                </div>
                @endif
            </div>

            @if($channel === 'email')
            <div style="margin-bottom:14px;">
                <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; display:block; margin-bottom:4px;">Logo (email)</label>
                <input wire:model="logoUpload" type="file" accept="image/*"
                       style="width:100%; padding:6px; font-size:11px; color:rgba(255,255,255,0.4); background:rgba(255,255,255,0.04); border:1px dashed rgba(255,255,255,0.15); border-radius:8px; cursor:pointer; box-sizing:border-box;">
            </div>
            @endif

            {{-- AI Image Generation --}}
            <div style="background:rgba(139,92,246,0.04); border:1px solid rgba(139,92,246,0.15); border-radius:10px; padding:14px; margin-bottom:14px;">
                <div style="display:flex; align-items:center; gap:6px; margin-bottom:8px;">
                    <svg width="14" height="14" fill="none" stroke="#a78bfa" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    <span style="font-size:11px; font-weight:700; color:#a78bfa; text-transform:uppercase;">Ou gerar imagem com IA</span>
                </div>
                <textarea wire:model="aiPrompt" rows="2" placeholder="Descreva a imagem que deseja. Ex: Banner promocional de corte de cabelo masculino com fundo azul escuro e texto dourado..."
                          style="width:100%; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:white; outline:none; resize:vertical; box-sizing:border-box; margin-bottom:8px;"></textarea>
                <div style="margin-bottom:8px;">
                    <label style="font-size:10px; color:rgba(255,255,255,0.3);">Imagens de referência (opcional)</label>
                    <input wire:model="aiRefImages" type="file" accept="image/*" multiple
                           style="width:100%; padding:4px; font-size:10px; color:rgba(255,255,255,0.3); box-sizing:border-box;">
                </div>
                <button wire:click="generateImage" wire:loading.attr="disabled" wire:target="generateImage"
                        style="display:flex; align-items:center; gap:6px; padding:7px 16px; background:linear-gradient(135deg, #8b5cf6, #7c3aed); color:white; font-size:12px; font-weight:600; border:none; border-radius:8px; cursor:pointer;">
                    <span wire:loading.remove wire:target="generateImage">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Gerar Imagem
                    </span>
                    <span wire:loading wire:target="generateImage">Gerando...</span>
                </button>
            </div>

            {{-- Mensagem / Contexto IA --}}
            <div style="margin-bottom:14px;">
                @if($channel === 'whatsapp')
                <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; display:block; margin-bottom:4px;">Contexto para IA *</label>
                <textarea wire:model="message" rows="5" placeholder="Escreva o contexto da mensagem. A IA vai gerar variações únicas para cada destinatário, evitando bloqueio por envios repetidos.&#10;&#10;Ex: Olá {nome}! A empresa XYZ está com uma promoção imperdível de 20% em todos os serviços até sexta-feira. Agende agora!"
                          style="width:100%; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:white; outline:none; resize:vertical; box-sizing:border-box;"></textarea>
                @error('message') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                <div style="display:flex; align-items:flex-start; gap:8px; margin-top:6px; background:rgba(139,92,246,0.04); border:1px solid rgba(139,92,246,0.12); border-radius:8px; padding:8px 10px;">
                    <svg width="14" height="14" fill="none" stroke="#a78bfa" viewBox="0 0 24 24" style="flex-shrink:0; margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    <p style="font-size:10px; color:rgba(255,255,255,0.4); line-height:1.5; margin:0;">A IA gera uma <strong style="color:rgba(255,255,255,0.6);">mensagem diferente</strong> para cada destinatário com base neste contexto, evitando bloqueio por repetição. Use <strong style="color:rgba(255,255,255,0.6);">{nome}</strong> para personalizar com o nome do contato.</p>
                </div>
                @else
                <label style="font-size:10px; font-weight:700; color:rgba(255,255,255,0.4); text-transform:uppercase; display:block; margin-bottom:4px;">Mensagem *</label>
                <textarea wire:model="message" rows="5" placeholder="Texto do email. Use {nome} para personalizar com o nome do contato e {email} para o email."
                          style="width:100%; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:white; outline:none; resize:vertical; box-sizing:border-box;"></textarea>
                @error('message') <span style="font-size:10px; color:#f87171;">{{ $message }}</span> @enderror
                @endif
            </div>

            {{-- Actions --}}
            <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:18px;">
                <button wire:click="$set('showForm', false)"
                        style="padding:8px 16px; font-size:12px; color:rgba(255,255,255,0.4); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:8px; cursor:pointer;">Cancelar</button>
                <button wire:click="save"
                        style="padding:8px 20px; font-size:12px; font-weight:700; color:#111; background:linear-gradient(135deg, #b2ff00, #8fcc00); border:none; border-radius:8px; cursor:pointer;">
                    {{ $editingId ? 'Atualizar' : 'Criar Template' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Templates grid --}}
    @if($templates->isEmpty())
    <div style="{{ $cardStyle }} text-align:center; padding:40px;">
        <svg width="40" height="40" fill="none" stroke="rgba(255,255,255,0.1)" viewBox="0 0 24 24" style="margin:0 auto 12px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/></svg>
        <p style="font-size:13px; color:rgba(255,255,255,0.25);">Nenhum template criado</p>
        <p style="font-size:11px; color:rgba(255,255,255,0.15); margin-top:4px;">Crie templates para reutilizar em suas campanhas</p>
    </div>
    @else
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:16px;">
        @foreach($templates as $tpl)
        <div style="{{ $cardStyle }}">
            @if($tpl->getImageUrl())
            <img src="{{ $tpl->getImageUrl() }}" style="width:100%; height:140px; object-fit:cover; border-radius:10px; margin-bottom:12px; border:1px solid rgba(255,255,255,0.06);">
            @else
            <div style="width:100%; height:140px; border-radius:10px; margin-bottom:12px; background:rgba(255,255,255,0.02); border:1px dashed rgba(255,255,255,0.08); display:flex; align-items:center; justify-content:center;">
                <svg width="32" height="32" fill="none" stroke="rgba(255,255,255,0.1)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            @endif
            <div style="display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                <span style="font-size:9px; font-weight:700; padding:2px 7px; border-radius:4px; text-transform:uppercase;
                    {{ $tpl->channel === 'whatsapp' ? 'background:rgba(34,197,94,0.12); color:#4ade80;' : 'background:rgba(59,130,246,0.12); color:#60a5fa;' }}">
                    {{ $tpl->channel === 'whatsapp' ? 'WhatsApp' : 'Email' }}
                </span>
                <span style="font-size:14px; font-weight:700; color:white; flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $tpl->name }}</span>
            </div>
            <p style="font-size:11px; color:rgba(255,255,255,0.3); line-height:1.5; max-height:48px; overflow:hidden;">{{ \Illuminate\Support\Str::limit($tpl->message, 100) }}</p>
            <div style="display:flex; gap:6px; margin-top:12px;">
                <button wire:click="openEdit({{ $tpl->id }})"
                        style="flex:1; padding:6px; font-size:10px; font-weight:600; color:rgba(255,255,255,0.5); background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:6px; cursor:pointer;">Editar</button>
                <button wire:click="duplicate({{ $tpl->id }})"
                        style="padding:6px 10px; font-size:10px; color:rgba(255,255,255,0.3); background:transparent; border:1px solid rgba(255,255,255,0.06); border-radius:6px; cursor:pointer;">Duplicar</button>
                <button wire:click="delete({{ $tpl->id }})" wire:confirm="Excluir este template?"
                        style="padding:6px 10px; font-size:10px; color:#f87171; background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.15); border-radius:6px; cursor:pointer;">Excluir</button>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
