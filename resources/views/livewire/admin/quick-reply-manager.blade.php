@php
$inputStyle = "width:100%; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:10px 14px; font-size:13px; color:white; outline:none; transition:all 0.2s; font-family:inherit; box-sizing:border-box;";
$inputFocus = "onfocus=\"this.style.borderColor='rgba(178,255,0,0.5)'; this.style.boxShadow='0 0 0 3px rgba(178,255,0,0.07)'\" onblur=\"this.style.borderColor='rgba(255,255,255,0.08)'; this.style.boxShadow='none'\"";
$labelStyle = "display:block; font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;";
@endphp

<div style="max-width:800px; margin:0 auto; padding:24px 16px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <div>
            <h2 style="font-size:18px; font-weight:800; color:white; font-family:'Syne',sans-serif;">Respostas Rápidas</h2>
            <p style="font-size:11px; color:rgba(255,255,255,0.3); margin-top:2px;">Atalhos de mensagens prontas para usar no atendimento (⚡ no chat)</p>
        </div>
        <button wire:click="openCreate"
                style="display:flex; align-items:center; gap:6px; padding:8px 16px; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:8px; color:#b2ff00; font-size:12px; font-weight:600; cursor:pointer;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nova Resposta
        </button>
    </div>

    @if($showForm)
    <div style="background:rgba(17,24,39,0.9); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:20px; margin-bottom:20px;">
        <h3 style="font-size:13px; font-weight:700; color:white; margin-bottom:16px;">{{ $editingId ? 'Editar Resposta' : 'Nova Resposta Rápida' }}</h3>
        <div style="display:flex; flex-direction:column; gap:14px;">
            <div>
                <label style="{{ $labelStyle }}">Título / atalho *</label>
                <input wire:model="title" type="text" placeholder="Ex: Saudação, Horário, Endereço..." style="{{ $inputStyle }}" {!! $inputFocus !!}>
                @error('title') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div>
                <label style="{{ $labelStyle }}">Conteúdo da mensagem *</label>
                <textarea wire:model="content" rows="5" placeholder="Olá! Obrigado por entrar em contato..." style="{{ $inputStyle }} resize:none; line-height:1.6;" {!! $inputFocus !!}></textarea>
                @error('content') <p style="font-size:11px; color:#f87171; margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div>
                <label style="{{ $labelStyle }}">Departamento (opcional)</label>
                <select wire:model="department_id" style="{{ $inputStyle }}">
                    <option value="">Todos os departamentos</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
                <p style="font-size:10px; color:rgba(255,255,255,0.2); margin-top:4px;">Se vazio, todos os agentes podem usar. Se definido, só agentes do departamento.</p>
            </div>
        </div>
        <div style="display:flex; gap:8px; margin-top:16px;">
            <button wire:click="save" style="padding:8px 20px; background:linear-gradient(135deg, #b2ff00, #8fcc00); color:#111; font-size:12px; font-weight:700; border-radius:9px; border:none; cursor:pointer;">
                {{ $editingId ? 'Atualizar' : 'Criar' }}
            </button>
            <button wire:click="$set('showForm', false)" style="padding:8px 16px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:9px; color:rgba(255,255,255,0.4); font-size:12px; cursor:pointer;">Cancelar</button>
        </div>
    </div>
    @endif

    @if($replies->isEmpty())
        <div style="text-align:center; padding:40px; color:rgba(255,255,255,0.3);">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 12px; opacity:0.4;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <p style="font-size:13px;">Nenhuma resposta rápida criada.</p>
        </div>
    @else
        <div style="display:flex; flex-direction:column; gap:8px;">
            @foreach($replies as $qr)
            <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:12px; padding:14px 16px; display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                        <span style="font-size:13px; font-weight:700; color:white;">⚡ {{ $qr->title }}</span>
                        @if($qr->department)
                        <span style="font-size:9px; font-weight:600; padding:2px 6px; border-radius:4px; background:rgba(96,165,250,0.12); color:#60a5fa; border:1px solid rgba(96,165,250,0.2);">{{ $qr->department->name }}</span>
                        @endif
                    </div>
                    <p style="font-size:12px; color:rgba(255,255,255,0.4); line-height:1.5; white-space:pre-wrap; max-height:60px; overflow:hidden;">{{ $qr->content }}</p>
                </div>
                <div style="display:flex; gap:6px; flex-shrink:0;">
                    <button wire:click="openEdit({{ $qr->id }})" style="font-size:11px; color:#b2ff00; background:none; border:none; cursor:pointer;">Editar</button>
                    <button wire:click="delete({{ $qr->id }})" wire:confirm="Remover esta resposta rápida?" style="font-size:11px; color:#f87171; background:none; border:none; cursor:pointer;">Excluir</button>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
