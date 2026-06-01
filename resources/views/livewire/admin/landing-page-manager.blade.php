@php
$inputStyle = "width:100%; padding:7px 10px; font-size:12px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:7px; color:white; outline:none; box-sizing:border-box;";
$labelStyle = "font-size:10px; color:rgba(255,255,255,0.4); display:block; margin-bottom:4px;";
$sectionTypes = ['hero' => '🎯 Hero', 'features' => '✨ Features', 'testimonials' => '💬 Depoimentos', 'form' => '📝 Formulário', 'cta' => '🔗 CTA', 'text' => '📋 Texto', 'faq' => '❓ FAQ', 'stats' => '📊 Números', 'video' => '🎬 Vídeo', 'gallery' => '📸 Galeria', 'header' => '🏢 Header', 'footer' => '👣 Footer'];
@endphp
<div>
    {{-- Tabs --}}
    <div style="display:flex; gap:8px; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:12px;">
        @foreach(['pages' => 'Páginas', 'editor' => 'Editor', 'analytics' => 'Analytics'] as $k => $l)
        <button wire:click="$set('tab', '{{ $k }}')" style="padding:6px 16px; font-size:12px; font-weight:{{ $tab === $k ? '700' : '400' }}; border-radius:8px; cursor:pointer; border:1px solid {{ $tab === $k ? 'rgba(178,255,0,0.3)' : 'rgba(255,255,255,0.08)' }}; background:{{ $tab === $k ? 'rgba(178,255,0,0.1)' : 'transparent' }}; color:{{ $tab === $k ? '#b2ff00' : 'rgba(255,255,255,0.4)' }};">{{ $l }}</button>
        @endforeach
    </div>

    {{-- ═══ PÁGINAS ═══ --}}
    @if($tab === 'pages')
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
        <h3 style="font-size:14px; font-weight:700; color:white;">Landing Pages</h3>
        <button wire:click="$set('showForm', true)" style="padding:6px 14px; font-size:11px; font-weight:600; color:#111; background:#b2ff00; border:none; border-radius:8px; cursor:pointer;">+ Nova Página</button>
    </div>

    @if($showForm)
    <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(178,255,0,0.15); border-radius:12px; padding:16px; margin-bottom:16px;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div><label style="{{ $labelStyle }}">Título *</label><input wire:model="title" type="text" placeholder="Minha Landing Page" style="{{ $inputStyle }}"></div>
            <div><label style="{{ $labelStyle }}">Slug (URL)</label><input wire:model="slug" type="text" placeholder="minha-pagina" style="{{ $inputStyle }}"></div>
            <div class="col-span-2" style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Descrição (SEO)</label><input wire:model="description" type="text" placeholder="Descrição para mecanismos de busca" style="{{ $inputStyle }}"></div>
            <div><label style="{{ $labelStyle }}">Email notificação (leads)</label><input wire:model="notification_email" type="email" placeholder="admin@empresa.com" style="{{ $inputStyle }}"></div>
            <div><label style="{{ $labelStyle }}">URL de obrigado (após envio)</label><input wire:model="thank_you_url" type="text" placeholder="https://..." style="{{ $inputStyle }}"></div>
            <div><label style="{{ $labelStyle }}">Pixel Facebook</label><input wire:model="fb_pixel" type="text" placeholder="123456789" style="{{ $inputStyle }}"></div>
            <div><label style="{{ $labelStyle }}">Google Analytics ID</label><input wire:model="ga_id" type="text" placeholder="G-XXXXXXX" style="{{ $inputStyle }}"></div>
            <div><label style="{{ $labelStyle }}">Domínio próprio (CNAME)</label><input wire:model="custom_domain" type="text" placeholder="lp.empresa.com.br" style="{{ $inputStyle }}"></div>
            <div><label style="{{ $labelStyle }}">Widget FlutChat</label>
                <select wire:model="flutchat_widget_id" style="{{ $inputStyle }}">
                    <option value="">— Nenhum —</option>
                    @foreach($widgets as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                </select>
            </div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:14px;">
            <button wire:click="$set('showForm', false)" style="padding:6px 14px; font-size:11px; color:rgba(255,255,255,0.4); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:7px; cursor:pointer;">Cancelar</button>
            <button wire:click="savePage" style="padding:6px 16px; font-size:11px; font-weight:700; color:#111; background:#b2ff00; border:none; border-radius:7px; cursor:pointer;">Salvar</button>
        </div>
    </div>
    @endif

    @foreach($pages as $page)
    <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:12px; padding:14px 18px; margin-bottom:8px; display:flex; align-items:center; gap:14px;">
        <div style="width:12px; height:12px; border-radius:50%; background:{{ $page->status === 'published' ? '#4ade80' : '#f59e0b' }}; flex-shrink:0;" title="{{ $page->status === 'published' ? 'Publicada' : 'Rascunho' }}"></div>
        <div style="flex:1; min-width:0;">
            <p style="font-size:13px; font-weight:700; color:white;">{{ $page->title }}</p>
            <p style="font-size:10px; color:rgba(255,255,255,0.3); margin-top:2px;">{{ $page->leads_count }} leads · {{ $page->views_count }} views · /{{ $page->slug }}</p>
        </div>
        <div style="display:flex; gap:6px; flex-shrink:0;">
            <button wire:click="openEditor({{ $page->id }})" style="padding:4px 10px; font-size:10px; font-weight:600; color:#a78bfa; background:rgba(167,139,250,0.1); border:1px solid rgba(167,139,250,0.2); border-radius:6px; cursor:pointer;">Editor</button>
            @if($page->status === 'published')
            <a href="{{ $page->public_url }}" target="_blank" style="padding:4px 10px; font-size:10px; font-weight:600; color:#4ade80; background:rgba(74,222,128,0.1); border:1px solid rgba(74,222,128,0.2); border-radius:6px; cursor:pointer; text-decoration:none;">Ver</a>
            @endif
            <button wire:click="editPage({{ $page->id }})" style="padding:4px 10px; font-size:10px; color:#60a5fa; background:rgba(96,165,250,0.1); border:1px solid rgba(96,165,250,0.2); border-radius:6px; cursor:pointer;">Config</button>
            <button wire:click="toggleStatus({{ $page->id }})" style="padding:4px 10px; font-size:10px; color:{{ $page->status === 'published' ? '#f59e0b' : '#4ade80' }}; background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:6px; cursor:pointer;">{{ $page->status === 'published' ? 'Despublicar' : 'Publicar' }}</button>
            <button wire:click="duplicatePage({{ $page->id }})" style="padding:4px 10px; font-size:10px; color:rgba(255,255,255,0.4); background:transparent; border:1px solid rgba(255,255,255,0.1); border-radius:6px; cursor:pointer;">Duplicar</button>
            <button wire:click="deletePage({{ $page->id }})" wire:confirm="Excluir esta página?" style="padding:4px 10px; font-size:10px; color:#f87171; background:transparent; border:1px solid rgba(239,68,68,0.2); border-radius:6px; cursor:pointer;">✕</button>
        </div>
    </div>
    @endforeach
    @endif

    {{-- ═══ EDITOR ═══ --}}
    @if($tab === 'editor')
    @if(!$editingPageId)
    <p style="color:rgba(255,255,255,0.3); font-size:13px; text-align:center; padding:40px;">Selecione uma página e clique em "Editor".</p>
    @else
    @php $currentPage = $pages->firstWhere('id', $editingPageId); @endphp
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <h3 style="font-size:14px; font-weight:700; color:white;">Editor: {{ $currentPage?->title }}</h3>
            <a href="{{ $currentPage?->public_url }}" target="_blank" style="font-size:10px; color:#60a5fa; text-decoration:none; background:rgba(96,165,250,0.1); border:1px solid rgba(96,165,250,0.2); border-radius:6px; padding:3px 8px;">👁 Preview</a>
        </div>
        <div style="display:flex; gap:8px; align-items:center;">
            <select wire:model="addSectionType" style="padding:5px 8px; font-size:11px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:white; outline:none;">
                @foreach($sectionTypes as $type => $label)<option value="{{ $type }}">{{ $label }}</option>@endforeach
            </select>
            <button wire:click="addSection" style="padding:5px 12px; font-size:11px; font-weight:600; color:#111; background:#a78bfa; border:none; border-radius:6px; cursor:pointer;">+ Seção</button>
        </div>
    </div>

    <div id="lp-sortable"
         x-data
         x-init="
            if (typeof Sortable === 'undefined') {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js';
                s.onload = () => initSortable();
                document.head.appendChild(s);
            } else { $nextTick(() => initSortable()); }
            function initSortable() {
                const el = document.getElementById('lp-sortable');
                if (!el || el._sortable) return;
                el._sortable = Sortable.create(el, {
                    handle: '.lp-drag-handle',
                    animation: 200,
                    ghostClass: 'lp-ghost',
                    onEnd: () => {
                        const ids = [...el.querySelectorAll('[data-section-id]')].map(e => parseInt(e.dataset.sectionId));
                        @this.call('reorderSections', ids);
                    }
                });
            }
         ">
    <style>.lp-ghost{opacity:0.3;border:2px dashed #b2ff00 !important;} .lp-drag-handle{cursor:grab;} .lp-drag-handle:active{cursor:grabbing;}</style>
    @foreach($sections as $idx => $section)
    <div data-section-id="{{ $section['id'] }}" style="background:rgba(255,255,255,0.02); border:1px solid {{ $section['visible'] ? 'rgba(255,255,255,0.06)' : 'rgba(239,68,68,0.2)' }}; border-radius:10px; padding:12px 14px; margin-bottom:8px; {{ !$section['visible'] ? 'opacity:0.5;' : '' }}">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
            <span class="lp-drag-handle" style="color:rgba(255,255,255,0.15); font-size:14px; padding:0 4px;" title="Arrastar para reordenar">☰</span>
            <span style="font-size:11px; font-weight:700; color:#b2ff00;">{{ $sectionTypes[$section['type']] ?? $section['type'] }}</span>
            <span style="font-size:9px; color:rgba(255,255,255,0.2);">#{{ $section['sort_order'] }}</span>
            <span style="flex:1;"></span>
            <button wire:click="moveSectionUp({{ $section['id'] }})" style="font-size:10px; color:rgba(255,255,255,0.3); background:none; border:none; cursor:pointer;" title="Mover para cima">▲</button>
            <button wire:click="moveSectionDown({{ $section['id'] }})" style="font-size:10px; color:rgba(255,255,255,0.3); background:none; border:none; cursor:pointer;" title="Mover para baixo">▼</button>
            <button wire:click="toggleSectionVisibility({{ $section['id'] }})" style="font-size:10px; color:{{ $section['visible'] ? 'rgba(255,255,255,0.3)' : '#f87171' }}; background:none; border:none; cursor:pointer;">{{ $section['visible'] ? '👁' : '🚫' }}</button>
            <button wire:click="deleteSection({{ $section['id'] }})" wire:confirm="Excluir seção?" style="font-size:10px; color:#f87171; background:none; border:none; cursor:pointer;">✕</button>
        </div>
        {{-- Config inline por tipo --}}
        @php $cfg = $section['config'] ?? []; @endphp
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
            @if(isset($cfg['title']))
            <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Título</label>
                <input type="text" value="{{ $cfg['title'] }}" wire:change="updateSectionConfig({{ $section['id'] }}, 'title', $event.target.value)" style="{{ $inputStyle }}">
            </div>
            @endif
            @if(isset($cfg['subtitle']))
            <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Subtítulo</label>
                <input type="text" value="{{ $cfg['subtitle'] }}" wire:change="updateSectionConfig({{ $section['id'] }}, 'subtitle', $event.target.value)" style="{{ $inputStyle }}">
            </div>
            @endif
            @if(isset($cfg['content']))
            <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">Conteúdo</label>
                <textarea wire:change="updateSectionConfig({{ $section['id'] }}, 'content', $event.target.value)" style="{{ $inputStyle }} min-height:60px;">{{ $cfg['content'] }}</textarea>
            </div>
            @endif
            @if(isset($cfg['cta_text']))
            <div><label style="{{ $labelStyle }}">Texto do botão</label>
                <input type="text" value="{{ $cfg['cta_text'] }}" wire:change="updateSectionConfig({{ $section['id'] }}, 'cta_text', $event.target.value)" style="{{ $inputStyle }}">
            </div>
            @endif
            @if(isset($cfg['button_text']))
            <div><label style="{{ $labelStyle }}">Texto do botão</label>
                <input type="text" value="{{ $cfg['button_text'] }}" wire:change="updateSectionConfig({{ $section['id'] }}, 'button_text', $event.target.value)" style="{{ $inputStyle }}">
            </div>
            @endif
            @if(isset($cfg['cta_url']))
            <div><label style="{{ $labelStyle }}">URL do botão</label>
                <input type="text" value="{{ $cfg['cta_url'] }}" wire:change="updateSectionConfig({{ $section['id'] }}, 'cta_url', $event.target.value)" style="{{ $inputStyle }}">
            </div>
            @endif
            @if(isset($cfg['button_url']))
            <div><label style="{{ $labelStyle }}">URL do botão</label>
                <input type="text" value="{{ $cfg['button_url'] }}" wire:change="updateSectionConfig({{ $section['id'] }}, 'button_url', $event.target.value)" style="{{ $inputStyle }}">
            </div>
            @endif
            @if(isset($cfg['url']) && $section['type'] === 'video')
            <div style="grid-column:1/-1;"><label style="{{ $labelStyle }}">URL do vídeo (YouTube)</label>
                <input type="text" value="{{ $cfg['url'] }}" wire:change="updateSectionConfig({{ $section['id'] }}, 'url', $event.target.value)" style="{{ $inputStyle }}" placeholder="https://youtube.com/watch?v=...">
            </div>
            @endif
            @if(isset($cfg['bg_color']))
            <div><label style="{{ $labelStyle }}">Cor de fundo</label>
                <div style="display:flex; gap:4px;"><input type="color" value="{{ $cfg['bg_color'] }}" wire:change="updateSectionConfig({{ $section['id'] }}, 'bg_color', $event.target.value)" style="width:30px; height:28px; border:none; cursor:pointer;">
                <input type="text" value="{{ $cfg['bg_color'] }}" wire:change="updateSectionConfig({{ $section['id'] }}, 'bg_color', $event.target.value)" style="{{ $inputStyle }}"></div>
            </div>
            @endif
            @if(isset($cfg['text_color']))
            <div><label style="{{ $labelStyle }}">Cor do texto</label>
                <div style="display:flex; gap:4px;"><input type="color" value="{{ $cfg['text_color'] }}" wire:change="updateSectionConfig({{ $section['id'] }}, 'text_color', $event.target.value)" style="width:30px; height:28px; border:none; cursor:pointer;">
                <input type="text" value="{{ $cfg['text_color'] }}" wire:change="updateSectionConfig({{ $section['id'] }}, 'text_color', $event.target.value)" style="{{ $inputStyle }}"></div>
            </div>
            @endif
            @if(isset($cfg['button_color']))
            <div><label style="{{ $labelStyle }}">Cor do botão</label>
                <div style="display:flex; gap:4px;"><input type="color" value="{{ $cfg['button_color'] }}" wire:change="updateSectionConfig({{ $section['id'] }}, 'button_color', $event.target.value)" style="width:30px; height:28px; border:none; cursor:pointer;">
                <input type="text" value="{{ $cfg['button_color'] }}" wire:change="updateSectionConfig({{ $section['id'] }}, 'button_color', $event.target.value)" style="{{ $inputStyle }}"></div>
            </div>
            @endif
        </div>

        {{-- Imagem de fundo (Hero) --}}
        @if(isset($cfg['bg_image']))
        <div style="margin-top:8px;">
            <label style="{{ $labelStyle }}">Imagem de fundo</label>
            <div style="display:flex; align-items:center; gap:8px;">
                @if($cfg['bg_image'])<img src="{{ $cfg['bg_image'] }}" style="width:60px; height:36px; object-fit:cover; border-radius:4px;">@endif
                <label style="padding:4px 10px; font-size:10px; color:#b2ff00; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:6px; cursor:pointer;">
                    📷 Upload <input type="file" wire:model="sectionImage" style="display:none;" accept="image/*">
                </label>
                @if($sectionImage)
                <button wire:click="uploadSectionImage({{ $section['id'] }}, 'bg_image')" style="padding:4px 8px; font-size:10px; color:#111; background:#b2ff00; border:none; border-radius:4px; cursor:pointer;">Salvar</button>
                @endif
            </div>
        </div>
        @endif

        {{-- Logo (Header) --}}
        @if(isset($cfg['logo']) && $section['type'] === 'header')
        <div style="margin-top:8px;">
            <label style="{{ $labelStyle }}">Logo</label>
            <div style="display:flex; align-items:center; gap:8px;">
                @if($cfg['logo'])<img src="{{ $cfg['logo'] }}" style="height:28px;">@endif
                <label style="padding:4px 10px; font-size:10px; color:#b2ff00; background:rgba(178,255,0,0.08); border:1px solid rgba(178,255,0,0.2); border-radius:6px; cursor:pointer;">
                    📷 Upload <input type="file" wire:model="sectionImage" style="display:none;" accept="image/*">
                </label>
                @if($sectionImage)
                <button wire:click="uploadSectionImage({{ $section['id'] }}, 'logo')" style="padding:4px 8px; font-size:10px; color:#111; background:#b2ff00; border:none; border-radius:4px; cursor:pointer;">Salvar</button>
                @endif
            </div>
        </div>
        @endif

        {{-- Items editáveis (features, testimonials, faq, stats) --}}
        @if(isset($cfg['items']))
        <div style="margin-top:10px; border-top:1px solid rgba(255,255,255,0.06); padding-top:8px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                <label style="{{ $labelStyle }} margin-bottom:0;">Items ({{ count($cfg['items']) }})</label>
                <button wire:click="addSectionItem({{ $section['id'] }}, 'items')" style="font-size:9px; color:#b2ff00; background:none; border:none; cursor:pointer;">+ Adicionar</button>
            </div>
            @foreach($cfg['items'] as $ii => $item)
            <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.04); border-radius:6px; padding:6px 8px; margin-bottom:4px; display:flex; gap:6px; align-items:center;">
                @if(isset($item['icon']))
                <input type="text" value="{{ $item['icon'] }}" wire:change="updateSectionItem({{ $section['id'] }}, 'items', {{ $ii }}, 'icon', $event.target.value)" style="width:30px; {{ $inputStyle }} text-align:center; padding:4px;">
                @endif
                @if(isset($item['title']))
                <input type="text" value="{{ $item['title'] }}" wire:change="updateSectionItem({{ $section['id'] }}, 'items', {{ $ii }}, 'title', $event.target.value)" style="flex:1; {{ $inputStyle }} padding:4px 6px; font-size:11px;" placeholder="Título">
                @endif
                @if(isset($item['desc']))
                <input type="text" value="{{ $item['desc'] }}" wire:change="updateSectionItem({{ $section['id'] }}, 'items', {{ $ii }}, 'desc', $event.target.value)" style="flex:2; {{ $inputStyle }} padding:4px 6px; font-size:11px;" placeholder="Descrição">
                @endif
                @if(isset($item['name']))
                <input type="text" value="{{ $item['name'] }}" wire:change="updateSectionItem({{ $section['id'] }}, 'items', {{ $ii }}, 'name', $event.target.value)" style="flex:1; {{ $inputStyle }} padding:4px 6px; font-size:11px;" placeholder="Nome">
                @endif
                @if(isset($item['text']))
                <input type="text" value="{{ $item['text'] }}" wire:change="updateSectionItem({{ $section['id'] }}, 'items', {{ $ii }}, 'text', $event.target.value)" style="flex:2; {{ $inputStyle }} padding:4px 6px; font-size:11px;" placeholder="Texto">
                @endif
                @if(isset($item['q']))
                <input type="text" value="{{ $item['q'] }}" wire:change="updateSectionItem({{ $section['id'] }}, 'items', {{ $ii }}, 'q', $event.target.value)" style="flex:1; {{ $inputStyle }} padding:4px 6px; font-size:11px;" placeholder="Pergunta">
                @endif
                @if(isset($item['a']))
                <input type="text" value="{{ $item['a'] }}" wire:change="updateSectionItem({{ $section['id'] }}, 'items', {{ $ii }}, 'a', $event.target.value)" style="flex:2; {{ $inputStyle }} padding:4px 6px; font-size:11px;" placeholder="Resposta">
                @endif
                @if(isset($item['value']))
                <input type="text" value="{{ $item['value'] }}" wire:change="updateSectionItem({{ $section['id'] }}, 'items', {{ $ii }}, 'value', $event.target.value)" style="width:60px; {{ $inputStyle }} padding:4px 6px; font-size:11px;" placeholder="Valor">
                @endif
                @if(isset($item['label']))
                <input type="text" value="{{ $item['label'] }}" wire:change="updateSectionItem({{ $section['id'] }}, 'items', {{ $ii }}, 'label', $event.target.value)" style="flex:1; {{ $inputStyle }} padding:4px 6px; font-size:11px;" placeholder="Label">
                @endif
                <button wire:click="removeSectionItem({{ $section['id'] }}, 'items', {{ $ii }})" style="color:#f87171; background:none; border:none; cursor:pointer; font-size:10px; flex-shrink:0;">✕</button>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Campos do formulário --}}
        @if(isset($cfg['fields']))
        <div style="margin-top:10px; border-top:1px solid rgba(255,255,255,0.06); padding-top:8px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                <label style="{{ $labelStyle }} margin-bottom:0;">Campos do formulário</label>
                <button wire:click="addSectionItem({{ $section['id'] }}, 'fields')" style="font-size:9px; color:#b2ff00; background:none; border:none; cursor:pointer;">+ Campo</button>
            </div>
            @foreach($cfg['fields'] as $fi => $field)
            <div style="display:flex; gap:4px; align-items:center; margin-bottom:4px;">
                <input type="text" value="{{ $field['label'] }}" wire:change="updateSectionItem({{ $section['id'] }}, 'fields', {{ $fi }}, 'label', $event.target.value)" style="flex:1; {{ $inputStyle }} padding:4px 6px; font-size:11px;" placeholder="Label">
                <input type="text" value="{{ $field['key'] }}" wire:change="updateSectionItem({{ $section['id'] }}, 'fields', {{ $fi }}, 'key', $event.target.value)" style="width:80px; {{ $inputStyle }} padding:4px 6px; font-size:11px;" placeholder="key">
                <select wire:change="updateSectionItem({{ $section['id'] }}, 'fields', {{ $fi }}, 'type', $event.target.value)" style="width:70px; {{ $inputStyle }} padding:4px; font-size:10px;">
                    @foreach(['text'=>'Texto','email'=>'Email','tel'=>'Telefone','textarea'=>'Área de texto','select'=>'Select'] as $ftype => $flabel)
                    <option value="{{ $ftype }}" {{ ($field['type'] ?? 'text') === $ftype ? 'selected' : '' }}>{{ $flabel }}</option>
                    @endforeach
                </select>
                <button wire:click="removeSectionItem({{ $section['id'] }}, 'fields', {{ $fi }})" style="color:#f87171; background:none; border:none; cursor:pointer; font-size:10px;">✕</button>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Links (header/footer) --}}
        @if(isset($cfg['links']))
        <div style="margin-top:10px; border-top:1px solid rgba(255,255,255,0.06); padding-top:8px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                <label style="{{ $labelStyle }} margin-bottom:0;">Links</label>
                <button wire:click="addSectionItem({{ $section['id'] }}, 'links')" style="font-size:9px; color:#b2ff00; background:none; border:none; cursor:pointer;">+ Link</button>
            </div>
            @foreach($cfg['links'] as $li => $link)
            <div style="display:flex; gap:4px; align-items:center; margin-bottom:4px;">
                <input type="text" value="{{ $link['label'] }}" wire:change="updateSectionItem({{ $section['id'] }}, 'links', {{ $li }}, 'label', $event.target.value)" style="flex:1; {{ $inputStyle }} padding:4px 6px; font-size:11px;" placeholder="Label">
                <input type="text" value="{{ $link['url'] }}" wire:change="updateSectionItem({{ $section['id'] }}, 'links', {{ $li }}, 'url', $event.target.value)" style="flex:1; {{ $inputStyle }} padding:4px 6px; font-size:11px;" placeholder="URL">
                <button wire:click="removeSectionItem({{ $section['id'] }}, 'links', {{ $li }})" style="color:#f87171; background:none; border:none; cursor:pointer; font-size:10px;">✕</button>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endforeach
    </div>{{-- /lp-sortable --}}

    @if($currentPage)
    <div style="margin-top:16px; padding:12px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:10px;">
        <p style="font-size:10px; color:rgba(255,255,255,0.3); margin-bottom:6px;">🔗 URL da página:</p>
        <code style="font-size:11px; color:#b2ff00;">{{ $currentPage->public_url }}</code>
    </div>
    @endif
    @endif
    @endif

    {{-- ═══ ANALYTICS ═══ --}}
    @if($tab === 'analytics')
    <h3 style="font-size:14px; font-weight:700; color:white; margin-bottom:16px;">Analytics</h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:12px;">
        @foreach([['label' => 'Total Páginas', 'value' => $reports['total_pages'] ?? 0, 'color' => '#b2ff00'], ['label' => 'Publicadas', 'value' => $reports['published'] ?? 0, 'color' => '#4ade80'], ['label' => 'Visualizações', 'value' => $reports['total_views'] ?? 0, 'color' => '#60a5fa'], ['label' => 'Leads', 'value' => $reports['total_leads'] ?? 0, 'color' => '#f59e0b'], ['label' => 'Taxa Conversão', 'value' => ($reports['conversion'] ?? 0) . '%', 'color' => '#ec4899']] as $card)
        <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:12px; padding:16px; text-align:center;">
            <p style="font-size:24px; font-weight:800; color:{{ $card['color'] }};">{{ $card['value'] }}</p>
            <p style="font-size:10px; color:rgba(255,255,255,0.4); text-transform:uppercase;">{{ $card['label'] }}</p>
        </div>
        @endforeach
    </div>
    @endif
</div>
