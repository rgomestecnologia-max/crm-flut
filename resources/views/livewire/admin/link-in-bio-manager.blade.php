@php
$inputStyle = "width:100%; padding:8px 12px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:white; outline:none; box-sizing:border-box;";
$labelStyle = "font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px; text-transform:uppercase; font-weight:600; letter-spacing:0.5px;";
$themes = \App\Models\LinkInBioPage::THEMES;
@endphp
<div>
    {{-- Tabs --}}
    <div style="display:flex; gap:8px; margin-bottom:16px;">
        @foreach(['list' => 'Páginas', 'editor' => 'Editor'] as $k => $l)
        <button wire:click="$set('tab', '{{ $k }}')" style="padding:5px 14px; font-size:11px; font-weight:{{ $tab === $k ? '600' : '400' }}; border-radius:7px; cursor:pointer; border:1px solid {{ $tab === $k ? 'rgba(249,115,22,0.3)' : 'rgba(255,255,255,0.08)' }}; background:{{ $tab === $k ? 'rgba(249,115,22,0.1)' : 'transparent' }}; color:{{ $tab === $k ? '#fb923c' : 'rgba(255,255,255,0.4)' }};">{{ $l }}</button>
        @endforeach
    </div>

    {{-- ═══ LISTA ═══ --}}
    @if($tab === 'list')
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
        <h3 style="font-size:14px; font-weight:700; color:white;">Link in Bio</h3>
        <button wire:click="$set('showForm', true)" style="padding:6px 14px; font-size:11px; font-weight:600; color:#111; background:#fb923c; border:none; border-radius:8px; cursor:pointer;">+ Nova Página</button>
    </div>

    @if($showForm)
    <div style="background:rgba(249,115,22,0.04); border:1px solid rgba(249,115,22,0.15); border-radius:12px; padding:16px; margin-bottom:14px;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <div><label style="{{ $labelStyle }}">Título *</label><input wire:model="title" type="text" placeholder="Ex: Meus Links" style="{{ $inputStyle }}"></div>
            <div><label style="{{ $labelStyle }}">Tema</label>
                <select wire:model="selectedTheme" style="{{ $inputStyle }}">
                    @foreach($themes as $key => $t)
                    <option value="{{ $key }}">{{ $t['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Bio / Descrição</label>
                <textarea wire:model="bioText" rows="2" placeholder="Uma frase sobre você ou sua empresa" style="{{ $inputStyle }} resize:none;"></textarea>
            </div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:12px;">
            <button wire:click="$set('showForm', false)" style="padding:6px 14px; font-size:11px; color:rgba(255,255,255,0.4); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:7px; cursor:pointer;">Cancelar</button>
            <button wire:click="savePage" style="padding:6px 16px; font-size:11px; font-weight:700; color:#111; background:#fb923c; border:none; border-radius:7px; cursor:pointer;">Salvar</button>
        </div>
    </div>
    @endif

    @foreach($pages as $p)
    <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:10px; padding:12px 16px; margin-bottom:6px; display:flex; align-items:center; gap:12px;">
        @if($p->avatar_url)
        <img src="{{ $p->avatar_url }}" style="width:36px; height:36px; border-radius:50%; object-fit:cover; flex-shrink:0;">
        @else
        <div style="width:36px; height:36px; border-radius:50%; background:rgba(249,115,22,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <svg width="16" height="16" fill="none" stroke="#fb923c" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
        </div>
        @endif
        <div style="flex:1; min-width:0;">
            <p style="font-size:13px; font-weight:700; color:white;">{{ $p->title }}</p>
            <p style="font-size:10px; color:rgba(255,255,255,0.3);">{{ $p->links_count }} links · {{ $p->views_count }} views · {{ ucfirst($p->status) }}</p>
        </div>
        <div style="display:flex; gap:6px; flex-shrink:0;">
            <button wire:click="openEditor({{ $p->id }})" style="padding:4px 10px; font-size:10px; font-weight:600; color:#fb923c; background:rgba(249,115,22,0.1); border:1px solid rgba(249,115,22,0.2); border-radius:6px; cursor:pointer;">Editor</button>
            @if($p->status === 'published')
            <a href="{{ $p->public_url }}" target="_blank" style="padding:4px 10px; font-size:10px; color:#60a5fa; background:rgba(96,165,250,0.1); border:1px solid rgba(96,165,250,0.2); border-radius:6px; cursor:pointer; text-decoration:none;">Ver</a>
            @endif
            <button wire:click="toggleStatus({{ $p->id }})" style="padding:4px 10px; font-size:10px; color:{{ $p->status === 'published' ? '#f59e0b' : '#4ade80' }}; background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:6px; cursor:pointer;">{{ $p->status === 'published' ? 'Despublicar' : 'Publicar' }}</button>
            <button wire:click="editPage({{ $p->id }})" style="padding:4px 10px; font-size:10px; color:rgba(255,255,255,0.4); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:6px; cursor:pointer;">Config</button>
            <button wire:click="duplicatePage({{ $p->id }})" style="padding:4px 10px; font-size:10px; color:rgba(255,255,255,0.3); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:6px; cursor:pointer;">Duplicar</button>
            <button wire:click="deletePage({{ $p->id }})" wire:confirm="Excluir esta página?" style="padding:4px 10px; font-size:10px; color:#f87171; background:transparent; border:1px solid rgba(239,68,68,0.2); border-radius:6px; cursor:pointer;">✕</button>
        </div>
    </div>
    @endforeach
    @if($pages->isEmpty())
    <p style="color:rgba(255,255,255,0.2); font-size:12px; text-align:center; padding:40px;">Nenhuma página criada.</p>
    @endif
    @endif

    {{-- ═══ EDITOR ═══ --}}
    @if($tab === 'editor')
    @if(!$editingPageId)
    <p style="color:rgba(255,255,255,0.3); font-size:13px; text-align:center; padding:40px;">Selecione uma página e clique em "Editor".</p>
    @else
    <div style="display:flex; gap:16px; height:calc(100vh - 200px);">
        {{-- Lado esquerdo: configurações --}}
        <div style="flex:1; overflow-y:auto; padding-right:8px;">
            <h3 style="font-size:14px; font-weight:700; color:white; margin-bottom:14px;">{{ $currentPage?->title }}</h3>

            {{-- Avatar --}}
            <div style="margin-bottom:14px; display:flex; align-items:center; gap:12px;">
                @if($currentPage?->avatar_url)
                <img src="{{ $currentPage->avatar_url }}" style="width:56px; height:56px; border-radius:50%; object-fit:cover;">
                @else
                <div style="width:56px; height:56px; border-radius:50%; background:rgba(249,115,22,0.15); display:flex; align-items:center; justify-content:center;">
                    <svg width="24" height="24" fill="none" stroke="#fb923c" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                </div>
                @endif
                <div>
                    <input wire:model="avatarFile" type="file" accept="image/*" style="font-size:10px; color:rgba(255,255,255,0.4);">
                    @if($avatarFile)
                    <button wire:click="uploadAvatar" style="margin-top:4px; padding:3px 10px; font-size:10px; font-weight:600; color:#111; background:#fb923c; border:none; border-radius:5px; cursor:pointer;">Upload</button>
                    @endif
                </div>
            </div>

            {{-- Bio --}}
            <div style="margin-bottom:14px;">
                <label style="{{ $labelStyle }}">Bio / Descrição</label>
                <textarea wire:model.blur="bioText" wire:change="updateBio" rows="2" style="{{ $inputStyle }} resize:none;"></textarea>
            </div>

            {{-- Temas --}}
            <div style="margin-bottom:14px;">
                <label style="{{ $labelStyle }}">Tema</label>
                <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:6px;">
                    @foreach($themes as $key => $t)
                    <button wire:click="updateTheme('{{ $key }}')"
                            style="padding:8px; border-radius:8px; cursor:pointer; text-align:center; border:2px solid {{ ($currentPage?->theme['bg_color'] ?? '') === $t['bg_color'] ? '#fb923c' : 'rgba(255,255,255,0.06)' }}; background:{{ $t['bg_gradient'] ?? $t['bg_color'] }};">
                        <div style="width:20px; height:20px; border-radius:50%; background:{{ $t['button_bg'] }}; border:{{ $t['button_border'] !== 'none' ? $t['button_border'] : '1px solid rgba(0,0,0,0.1)' }}; margin:0 auto 4px;"></div>
                        <span style="font-size:8px; color:{{ $t['text_color'] }};">{{ $t['name'] }}</span>
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Adicionar link --}}
            <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:10px; padding:12px; margin-bottom:14px;">
                <div style="display:flex; gap:6px; margin-bottom:8px;">
                    <select wire:model="addLinkType" style="padding:5px 8px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:white;">
                        <option value="link">🔗 Link</option>
                        <option value="header">📝 Título</option>
                        <option value="social">📱 Rede Social</option>
                        <option value="divider">➖ Divisor</option>
                    </select>
                    <input wire:model="addLinkTitle" type="text" placeholder="Título" style="flex:1; {{ $inputStyle }}">
                </div>
                @if($addLinkType !== 'divider')
                <div style="display:flex; gap:6px; margin-bottom:8px;">
                    <input wire:model="addLinkUrl" type="url" placeholder="https://..." style="flex:1; {{ $inputStyle }}">
                </div>
                @endif
                <button wire:click="addLink" style="width:100%; padding:6px; font-size:11px; font-weight:600; color:#111; background:#fb923c; border:none; border-radius:6px; cursor:pointer;">+ Adicionar</button>
            </div>

            {{-- Lista de links --}}
            @foreach($links as $link)
            <div style="display:flex; align-items:center; gap:8px; padding:8px 10px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:8px; margin-bottom:4px; {{ !$link->is_active ? 'opacity:0.4;' : '' }}">
                <div style="display:flex; flex-direction:column; gap:2px;">
                    <button wire:click="moveLinkUp({{ $link->id }})" style="font-size:8px; color:rgba(255,255,255,0.3); background:none; border:none; cursor:pointer; padding:0;">▲</button>
                    <button wire:click="moveLinkDown({{ $link->id }})" style="font-size:8px; color:rgba(255,255,255,0.3); background:none; border:none; cursor:pointer; padding:0;">▼</button>
                </div>
                <span style="font-size:12px; flex-shrink:0;">
                    {{ $link->type === 'link' ? '🔗' : ($link->type === 'header' ? '📝' : ($link->type === 'social' ? '📱' : '➖')) }}
                </span>
                <div style="flex:1; min-width:0;">
                    <input type="text" value="{{ $link->title }}" wire:change="updateLink({{ $link->id }}, 'title', $event.target.value)"
                           style="width:100%; padding:3px 6px; font-size:11px; font-weight:600; background:transparent; border:none; color:white; outline:none;">
                    @if($link->type !== 'divider' && $link->type !== 'header')
                    <input type="url" value="{{ $link->url }}" wire:change="updateLink({{ $link->id }}, 'url', $event.target.value)"
                           style="width:100%; padding:2px 6px; font-size:10px; background:transparent; border:none; color:rgba(255,255,255,0.3); outline:none;">
                    @endif
                </div>
                <button wire:click="toggleLink({{ $link->id }})" style="font-size:9px; color:{{ $link->is_active ? '#4ade80' : '#6b7280' }}; background:none; border:none; cursor:pointer;">{{ $link->is_active ? '●' : '○' }}</button>
                <button wire:click="deleteLink({{ $link->id }})" style="font-size:9px; color:#f87171; background:none; border:none; cursor:pointer;">✕</button>
            </div>
            @endforeach
        </div>

        {{-- Lado direito: preview --}}
        <div style="width:375px; flex-shrink:0; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:16px; overflow:hidden;">
            <div style="padding:8px 12px; background:rgba(255,255,255,0.04); border-bottom:1px solid rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:space-between;">
                <span style="font-size:10px; color:rgba(255,255,255,0.3);">Preview</span>
                @if($currentPage?->status === 'published')
                <a href="{{ $currentPage->public_url }}" target="_blank" style="font-size:10px; color:#60a5fa; text-decoration:none;">Abrir →</a>
                @endif
            </div>
            <iframe src="{{ $currentPage?->public_url }}?preview=1" style="width:100%; height:calc(100% - 36px); border:none; background:white;"></iframe>
        </div>
    </div>
    @endif
    @endif
</div>
