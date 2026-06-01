<?php

namespace App\Livewire\Admin;

use App\Models\LandingPage;
use App\Models\LandingPageSection;
use App\Models\LandingPageLead;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class LandingPageManager extends Component
{
    use WithFileUploads;

    public string $tab = 'pages';

    // CRUD
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $title = '';
    public string $slug = '';
    public string $description = '';
    public string $notification_email = '';
    public string $thank_you_url = '';
    public string $fb_pixel = '';
    public string $ga_id = '';
    public string $custom_domain = '';
    public string $custom_css = '';
    public ?int $flutchat_widget_id = null;

    // Editor
    public ?int $editingPageId = null;
    public array $sections = [];
    public string $addSectionType = 'hero';
    public $sectionImage = null; // upload temporário

    public function savePage(): void
    {
        $this->validate([
            'title' => 'required|string|max:200',
        ]);

        $data = [
            'title'             => $this->title,
            'slug'              => $this->slug ?: Str::slug($this->title),
            'description'       => $this->description ?: null,
            'notification_email' => $this->notification_email ?: null,
            'thank_you_url'     => $this->thank_you_url ?: null,
            'fb_pixel'          => $this->fb_pixel ?: null,
            'ga_id'             => $this->ga_id ?: null,
            'custom_domain'     => $this->custom_domain ?: null,
            'custom_css'        => $this->custom_css ?: null,
            'flutchat_widget_id' => $this->flutchat_widget_id,
        ];

        if ($this->editingId) {
            LandingPage::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Página atualizada.');
        } else {
            $page = LandingPage::create($data);
            // Seções iniciais
            LandingPageSection::create(['landing_page_id' => $page->id, 'type' => 'hero', 'sort_order' => 1, 'config' => ['title' => 'Seu título aqui', 'subtitle' => 'Subtítulo da sua página', 'cta_text' => 'Saiba mais', 'cta_url' => '#form', 'bg_color' => '#111827', 'text_color' => '#ffffff']]);
            LandingPageSection::create(['landing_page_id' => $page->id, 'type' => 'form', 'sort_order' => 2, 'config' => ['title' => 'Entre em contato', 'fields' => [['label' => 'Nome', 'key' => 'nome', 'type' => 'text', 'required' => true], ['label' => 'WhatsApp', 'key' => 'whatsapp', 'type' => 'tel', 'required' => true], ['label' => 'E-mail', 'key' => 'email', 'type' => 'email', 'required' => false]], 'button_text' => 'Enviar', 'bg_color' => '#0f172a', 'text_color' => '#ffffff', 'button_color' => '#b2ff00']]);
            $this->dispatch('toast', type: 'success', message: 'Página criada com seções iniciais.');
        }

        $this->resetForm();
    }

    public function editPage(int $id): void
    {
        $page = LandingPage::findOrFail($id);
        $this->editingId = $page->id;
        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->description = $page->description ?? '';
        $this->notification_email = $page->notification_email ?? '';
        $this->thank_you_url = $page->thank_you_url ?? '';
        $this->fb_pixel = $page->fb_pixel ?? '';
        $this->ga_id = $page->ga_id ?? '';
        $this->custom_domain = $page->custom_domain ?? '';
        $this->custom_css = $page->custom_css ?? '';
        $this->flutchat_widget_id = $page->flutchat_widget_id;
        $this->showForm = true;
    }

    public function deletePage(int $id): void
    {
        LandingPage::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Página excluída.');
    }

    public function duplicatePage(int $id): void
    {
        $page = LandingPage::findOrFail($id);
        $new = $page->replicate();
        $new->title = $page->title . ' (cópia)';
        $new->slug = $page->slug . '-copia-' . Str::random(4);
        $new->status = 'draft';
        $new->views_count = 0;
        $new->save();

        foreach ($page->sections as $section) {
            $s = $section->replicate();
            $s->landing_page_id = $new->id;
            $s->save();
        }

        $this->dispatch('toast', type: 'success', message: 'Página duplicada.');
    }

    public function toggleStatus(int $id): void
    {
        $page = LandingPage::findOrFail($id);
        $page->update(['status' => $page->status === 'published' ? 'draft' : 'published']);
        $this->dispatch('toast', type: 'success', message: $page->status === 'published' ? 'Página publicada.' : 'Página despublicada.');
    }

    // Editor de seções
    public function openEditor(int $pageId): void
    {
        $this->editingPageId = $pageId;
        $this->loadSections();
        $this->tab = 'editor';
    }

    public function loadSections(): void
    {
        if (!$this->editingPageId) return;
        $this->sections = LandingPageSection::where('landing_page_id', $this->editingPageId)
            ->orderBy('sort_order')->get()->toArray();
    }

    public function addSection(): void
    {
        if (!$this->editingPageId) return;
        $maxOrder = LandingPageSection::where('landing_page_id', $this->editingPageId)->max('sort_order') ?? 0;

        $defaults = $this->getSectionDefaults($this->addSectionType);
        LandingPageSection::create([
            'landing_page_id' => $this->editingPageId,
            'type' => $this->addSectionType,
            'sort_order' => $maxOrder + 1,
            'config' => $defaults,
        ]);
        $this->loadSections();
        $this->dispatch('toast', type: 'success', message: 'Seção adicionada.');
    }

    public function updateSectionConfig(int $sectionId, string $key, $value): void
    {
        $section = LandingPageSection::find($sectionId);
        if (!$section) return;
        $config = $section->config ?? [];
        $config[$key] = $value;
        $section->update(['config' => $config]);
        $this->loadSections();
    }

    public function uploadSectionImage(int $sectionId, string $key): void
    {
        if (!$this->sectionImage) return;
        $this->validate(['sectionImage' => 'image|max:5120']);
        $dir = 'landing-pages/' . date('Y/m');
        $path = \App\Services\MediaStorage::store($this->sectionImage, $dir);
        $url = \App\Services\MediaStorage::url($path);
        $this->updateSectionConfig($sectionId, $key, $url);
        $this->sectionImage = null;
        $this->dispatch('toast', type: 'success', message: 'Imagem enviada.');
    }

    public function addSectionItem(int $sectionId, string $key): void
    {
        $section = LandingPageSection::find($sectionId);
        if (!$section) return;
        $config = $section->config ?? [];
        $items = $config[$key] ?? [];
        $items[] = match($key) {
            'items' => match($section->type) {
                'features' => ['icon' => '⭐', 'title' => 'Novo item', 'desc' => 'Descrição'],
                'testimonials' => ['name' => 'Nome', 'text' => 'Depoimento', 'photo' => ''],
                'faq' => ['q' => 'Pergunta?', 'a' => 'Resposta'],
                'stats' => ['value' => '0', 'label' => 'Label'],
                default => [],
            },
            'fields' => ['label' => 'Novo campo', 'key' => 'campo_' . count($items), 'type' => 'text', 'required' => false],
            'links' => ['label' => 'Link', 'url' => '#'],
            'images' => '',
            default => [],
        };
        $config[$key] = $items;
        $section->update(['config' => $config]);
        $this->loadSections();
    }

    public function removeSectionItem(int $sectionId, string $key, int $index): void
    {
        $section = LandingPageSection::find($sectionId);
        if (!$section) return;
        $config = $section->config ?? [];
        $items = $config[$key] ?? [];
        unset($items[$index]);
        $config[$key] = array_values($items);
        $section->update(['config' => $config]);
        $this->loadSections();
    }

    public function updateSectionItem(int $sectionId, string $key, int $index, string $field, $value): void
    {
        $section = LandingPageSection::find($sectionId);
        if (!$section) return;
        $config = $section->config ?? [];
        if (isset($config[$key][$index])) {
            $config[$key][$index][$field] = $value;
            $section->update(['config' => $config]);
            $this->loadSections();
        }
    }

    public function reorderSections(array $ids): void
    {
        foreach ($ids as $order => $id) {
            LandingPageSection::where('id', $id)->update(['sort_order' => $order + 1]);
        }
        $this->loadSections();
    }

    public function moveSectionUp(int $id): void
    {
        $section = LandingPageSection::findOrFail($id);
        $prev = LandingPageSection::where('landing_page_id', $section->landing_page_id)
            ->where('sort_order', '<', $section->sort_order)->orderByDesc('sort_order')->first();
        if ($prev) {
            $tmp = $section->sort_order;
            $section->update(['sort_order' => $prev->sort_order]);
            $prev->update(['sort_order' => $tmp]);
            $this->loadSections();
        }
    }

    public function moveSectionDown(int $id): void
    {
        $section = LandingPageSection::findOrFail($id);
        $next = LandingPageSection::where('landing_page_id', $section->landing_page_id)
            ->where('sort_order', '>', $section->sort_order)->orderBy('sort_order')->first();
        if ($next) {
            $tmp = $section->sort_order;
            $section->update(['sort_order' => $next->sort_order]);
            $next->update(['sort_order' => $tmp]);
            $this->loadSections();
        }
    }

    public function deleteSection(int $id): void
    {
        LandingPageSection::findOrFail($id)->delete();
        $this->loadSections();
        $this->dispatch('toast', type: 'success', message: 'Seção removida.');
    }

    public function toggleSectionVisibility(int $id): void
    {
        $s = LandingPageSection::findOrFail($id);
        $s->update(['visible' => !$s->visible]);
        $this->loadSections();
    }

    private function getSectionDefaults(string $type): array
    {
        return match($type) {
            'hero' => ['title' => 'Título Principal', 'subtitle' => 'Subtítulo descritivo', 'cta_text' => 'Saiba mais', 'cta_url' => '#form', 'bg_color' => '#111827', 'text_color' => '#ffffff', 'bg_image' => ''],
            'features' => ['title' => 'Nossos Diferenciais', 'items' => [['icon' => '⚡', 'title' => 'Rápido', 'desc' => 'Entrega ágil'], ['icon' => '🔒', 'title' => 'Seguro', 'desc' => 'Dados protegidos'], ['icon' => '💬', 'title' => 'Suporte', 'desc' => 'Atendimento dedicado']], 'bg_color' => '#0f172a', 'text_color' => '#ffffff'],
            'testimonials' => ['title' => 'O que dizem nossos clientes', 'items' => [['name' => 'Cliente 1', 'text' => 'Excelente serviço!', 'photo' => ''], ['name' => 'Cliente 2', 'text' => 'Recomendo muito!', 'photo' => '']], 'bg_color' => '#1e293b', 'text_color' => '#ffffff'],
            'form' => ['title' => 'Fale conosco', 'fields' => [['label' => 'Nome', 'key' => 'nome', 'type' => 'text', 'required' => true], ['label' => 'WhatsApp', 'key' => 'whatsapp', 'type' => 'tel', 'required' => true]], 'button_text' => 'Enviar', 'bg_color' => '#0f172a', 'text_color' => '#ffffff', 'button_color' => '#b2ff00'],
            'cta' => ['title' => 'Pronto para começar?', 'subtitle' => '', 'button_text' => 'Fale conosco', 'button_url' => '#form', 'bg_color' => '#b2ff00', 'text_color' => '#111827'],
            'text' => ['content' => 'Seu texto aqui...', 'bg_color' => '#ffffff', 'text_color' => '#333333'],
            'video' => ['url' => '', 'title' => '', 'bg_color' => '#0f172a'],
            'faq' => ['title' => 'Perguntas Frequentes', 'items' => [['q' => 'Pergunta 1?', 'a' => 'Resposta 1'], ['q' => 'Pergunta 2?', 'a' => 'Resposta 2']], 'bg_color' => '#1e293b', 'text_color' => '#ffffff'],
            'stats' => ['items' => [['value' => '500+', 'label' => 'Clientes'], ['value' => '98%', 'label' => 'Satisfação'], ['value' => '24h', 'label' => 'Suporte']], 'bg_color' => '#111827', 'text_color' => '#ffffff'],
            'gallery' => ['title' => 'Galeria', 'images' => [], 'bg_color' => '#0f172a'],
            'header' => ['logo' => '', 'links' => [['label' => 'Início', 'url' => '#'], ['label' => 'Contato', 'url' => '#form']], 'bg_color' => '#111827', 'text_color' => '#ffffff'],
            'footer' => ['text' => '© 2026 Todos os direitos reservados.', 'links' => [], 'bg_color' => '#0f172a', 'text_color' => 'rgba(255,255,255,0.5)'],
            default => ['bg_color' => '#ffffff', 'text_color' => '#333333'],
        };
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->slug = '';
        $this->description = '';
        $this->notification_email = '';
        $this->thank_you_url = '';
        $this->fb_pixel = '';
        $this->ga_id = '';
        $this->custom_domain = '';
        $this->custom_css = '';
        $this->flutchat_widget_id = null;
        $this->showForm = false;
    }

    public function render()
    {
        $pages = LandingPage::withCount('leads')->orderByDesc('updated_at')->get();
        $widgets = \App\Models\FlutChatWidget::where('is_active', true)->get();

        $reports = [];
        if ($this->tab === 'analytics') {
            $totalViews = LandingPage::sum('views_count');
            $totalLeads = LandingPageLead::count();
            $reports = [
                'total_pages' => $pages->count(),
                'published'   => $pages->where('status', 'published')->count(),
                'total_views' => $totalViews,
                'total_leads' => $totalLeads,
                'conversion'  => $totalViews > 0 ? round(($totalLeads / $totalViews) * 100, 1) : 0,
            ];
        }

        return view('livewire.admin.landing-page-manager', compact('pages', 'widgets', 'reports'));
    }
}
