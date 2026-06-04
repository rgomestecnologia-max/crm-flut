<?php

namespace App\Livewire\Admin;

use App\Models\LinkInBioLink;
use App\Models\LinkInBioPage;
use App\Services\MediaStorage;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class LinkInBioManager extends Component
{
    use WithFileUploads;

    public string $tab = 'list';

    // CRUD
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $title = '';
    public string $bioText = '';
    public string $selectedTheme = 'dark';

    // Editor
    public ?int $editingPageId = null;

    // Link form
    public string $addLinkType = 'link';
    public string $addLinkTitle = '';
    public string $addLinkUrl = '';
    public string $addLinkIcon = '';

    // Avatar upload
    public $avatarFile = null;

    public function savePage(): void
    {
        $this->validate(['title' => 'required|string|max:200']);

        $data = [
            'title'    => $this->title,
            'bio_text' => $this->bioText ?: null,
            'theme'    => LinkInBioPage::THEMES[$this->selectedTheme] ?? LinkInBioPage::THEMES['dark'],
        ];

        if ($this->editingId) {
            LinkInBioPage::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Página atualizada.');
        } else {
            $data['created_by'] = Auth::id();
            $page = LinkInBioPage::create($data);
            $this->dispatch('toast', type: 'success', message: 'Página criada.');
        }
        $this->resetForm();
    }

    public function editPage(int $id): void
    {
        $p = LinkInBioPage::findOrFail($id);
        $this->editingId = $p->id;
        $this->title = $p->title;
        $this->bioText = $p->bio_text ?? '';
        $this->selectedTheme = $this->detectTheme($p->theme);
        $this->showForm = true;
    }

    public function deletePage(int $id): void
    {
        LinkInBioPage::findOrFail($id)->delete();
        if ($this->editingPageId === $id) $this->editingPageId = null;
        $this->dispatch('toast', type: 'success', message: 'Página excluída.');
    }

    public function toggleStatus(int $id): void
    {
        $p = LinkInBioPage::findOrFail($id);
        $p->update(['status' => $p->status === 'published' ? 'draft' : 'published']);
        $this->dispatch('toast', type: 'success', message: $p->status === 'published' ? 'Publicada!' : 'Despublicada.');
    }

    public function duplicatePage(int $id): void
    {
        $original = LinkInBioPage::with('links')->findOrFail($id);
        $clone = $original->replicate();
        $clone->title = $original->title . ' (cópia)';
        $clone->slug = null;
        $clone->status = 'draft';
        $clone->views_count = 0;
        $clone->save();

        foreach ($original->links as $link) {
            $newLink = $link->replicate();
            $newLink->page_id = $clone->id;
            $newLink->clicks_count = 0;
            $newLink->save();
        }
        $this->dispatch('toast', type: 'success', message: 'Página duplicada.');
    }

    // Editor
    public function openEditor(int $pageId): void
    {
        $this->editingPageId = $pageId;
        $this->tab = 'editor';
    }

    public function uploadAvatar(): void
    {
        if (!$this->editingPageId || !$this->avatarFile) return;

        $this->validate(['avatarFile' => 'image|max:2048']);

        $dir = 'link-in-bio/' . date('Y/m');
        $path = MediaStorage::store($this->avatarFile, $dir);
        $url = MediaStorage::url($path);

        LinkInBioPage::findOrFail($this->editingPageId)->update(['avatar_url' => $url]);
        $this->avatarFile = null;
        $this->dispatch('toast', type: 'success', message: 'Avatar atualizado.');
        $this->refreshPreview();
    }

    public function updateTheme(string $themeKey): void
    {
        if (!$this->editingPageId) return;
        $theme = LinkInBioPage::THEMES[$themeKey] ?? null;
        if (!$theme) return;
        LinkInBioPage::findOrFail($this->editingPageId)->update(['theme' => $theme]);
        $this->dispatch('toast', type: 'success', message: 'Tema aplicado.');
        $this->refreshPreview();
    }

    public function updateBio(): void
    {
        if (!$this->editingPageId) return;
        LinkInBioPage::findOrFail($this->editingPageId)->update(['bio_text' => $this->bioText]);
        $this->refreshPreview();
    }

    public function updateThemeColor(string $key, string $value): void
    {
        if (!$this->editingPageId) return;
        $page = LinkInBioPage::findOrFail($this->editingPageId);
        $theme = $page->theme ?? LinkInBioPage::THEMES['dark'];
        $theme[$key] = $value;
        // Atualiza cores derivadas automaticamente
        if ($key === 'bg_color') {
            $theme['bg_gradient'] = null; // Remove gradiente ao mudar cor de fundo
        }
        $page->update(['theme' => $theme]);
        $this->refreshPreview();
    }

    public function addLink(): void
    {
        if (!$this->editingPageId) return;
        $maxOrder = LinkInBioLink::where('page_id', $this->editingPageId)->max('sort_order') ?? 0;

        LinkInBioLink::create([
            'page_id'    => $this->editingPageId,
            'type'       => $this->addLinkType,
            'title'      => $this->addLinkTitle ?: ($this->addLinkType === 'header' ? 'Seção' : 'Novo link'),
            'url'        => $this->addLinkUrl ?: null,
            'icon'       => $this->addLinkIcon ?: null,
            'sort_order' => $maxOrder + 1,
        ]);
        $this->addLinkTitle = '';
        $this->addLinkUrl = '';
        $this->addLinkIcon = '';
        $this->dispatch('toast', type: 'success', message: 'Link adicionado.');
        $this->refreshPreview();
    }

    public function updateLink(int $id, string $field, $value): void
    {
        $link = LinkInBioLink::find($id);
        if (!$link) return;
        $link->update([$field => $value]);
        $this->refreshPreview();
    }

    public function toggleLink(int $id): void
    {
        $link = LinkInBioLink::findOrFail($id);
        $link->update(['is_active' => !$link->is_active]);
        $this->refreshPreview();
    }

    public function deleteLink(int $id): void
    {
        LinkInBioLink::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Link removido.');
        $this->refreshPreview();
    }

    public function moveLinkUp(int $id): void
    {
        $link = LinkInBioLink::findOrFail($id);
        $prev = LinkInBioLink::where('page_id', $link->page_id)
            ->where('sort_order', '<', $link->sort_order)->orderByDesc('sort_order')->first();
        if ($prev) {
            $tmp = $link->sort_order;
            $link->update(['sort_order' => $prev->sort_order]);
            $prev->update(['sort_order' => $tmp]);
            $this->refreshPreview();
        }
    }

    public function moveLinkDown(int $id): void
    {
        $link = LinkInBioLink::findOrFail($id);
        $next = LinkInBioLink::where('page_id', $link->page_id)
            ->where('sort_order', '>', $link->sort_order)->orderBy('sort_order')->first();
        if ($next) {
            $tmp = $link->sort_order;
            $link->update(['sort_order' => $next->sort_order]);
            $next->update(['sort_order' => $tmp]);
            $this->refreshPreview();
        }
    }

    private function refreshPreview(): void
    {
        $this->dispatch('link-in-bio-updated');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->bioText = '';
        $this->selectedTheme = 'dark';
        $this->showForm = false;
    }

    private function detectTheme(?array $theme): string
    {
        if (!$theme) return 'dark';
        foreach (LinkInBioPage::THEMES as $key => $t) {
            if (($t['bg_color'] ?? '') === ($theme['bg_color'] ?? '')) return $key;
        }
        return 'dark';
    }

    public function openAnalytics(int $pageId): void
    {
        $this->editingPageId = $pageId;
        $this->tab = 'analytics';
    }

    public function render()
    {
        $pages = LinkInBioPage::withCount('links')->orderByDesc('updated_at')->get();

        $links = collect();
        $currentPage = null;
        if ($this->editingPageId && in_array($this->tab, ['editor', 'analytics'])) {
            $currentPage = LinkInBioPage::find($this->editingPageId);
            $links = LinkInBioLink::where('page_id', $this->editingPageId)->orderBy('sort_order')->get();
        }

        // Analytics
        $analytics = [];
        if ($this->editingPageId && $this->tab === 'analytics') {
            $totalClicks = $links->sum('clicks_count');
            $analytics = [
                'views'        => $currentPage->views_count ?? 0,
                'total_clicks' => $totalClicks,
                'ctr'          => ($currentPage->views_count ?? 0) > 0
                    ? round(($totalClicks / $currentPage->views_count) * 100, 1)
                    : 0,
                'links_count'  => $links->where('type', 'link')->count(),
            ];
        }

        return view('livewire.admin.link-in-bio-manager', compact('pages', 'links', 'currentPage', 'analytics'));
    }
}
