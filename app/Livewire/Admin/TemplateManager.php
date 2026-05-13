<?php

namespace App\Livewire\Admin;

use App\Models\MetaMessageTemplate;
use Livewire\WithFileUploads;
use App\Models\MetaWhatsAppConfig;
use App\Services\MetaWhatsAppService;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class TemplateManager extends Component
{
    use WithFileUploads;

    // List
    public $templates = [];
    public string $statusFilter = '';
    public string $searchQuery = '';

    // Create form
    public bool $showForm = false;
    public string $step = 'category'; // category, editor
    public string $templateName = '';
    public string $category = 'MARKETING'; // MARKETING, UTILITY
    public string $language = 'pt_BR';
    public string $headerType = 'none'; // none, text, image
    public string $headerText = '';
    public $headerImage = null; // Livewire upload
    public ?string $headerImageUrl = null;
    public string $bodyText = '';
    public string $footerText = '';
    public array $buttons = [];
    public string $newButtonType = ''; // quick_reply, url, phone
    public string $newButtonText = '';
    public string $newButtonValue = '';

    public function mount()
    {
        $this->loadTemplates();
    }

    public function loadTemplates()
    {
        $query = MetaMessageTemplate::orderBy('name');
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        if ($this->searchQuery) {
            $query->where('name', 'like', "%{$this->searchQuery}%");
        }
        $this->templates = $query->get()->toArray();
    }

    public function setFilter($status)
    {
        $this->statusFilter = $status;
        $this->loadTemplates();
    }

    public function updatedSearchQuery()
    {
        $this->loadTemplates();
    }

    // === CREATE FLOW ===

    public function openCreate()
    {
        $this->resetForm();
        $this->showForm = true;
        $this->step = 'category';
    }

    public function nextStep()
    {
        $this->step = 'editor';
    }

    public function cancelCreate()
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function addButton()
    {
        if (!$this->newButtonText || !$this->newButtonType) return;
        if (count($this->buttons) >= 3) {
            $this->dispatch('toast', type: 'error', message: 'Máximo de 3 botões');
            return;
        }

        $this->buttons[] = [
            'type' => $this->newButtonType,
            'text' => $this->newButtonText,
            'value' => $this->newButtonValue,
        ];
        $this->newButtonText = '';
        $this->newButtonValue = '';
        $this->newButtonType = '';
    }

    public function removeButton($index)
    {
        unset($this->buttons[$index]);
        $this->buttons = array_values($this->buttons);
    }

    public function saveDraft()
    {
        $this->validate([
            'templateName' => 'required|string|max:512|regex:/^[a-z0-9_]+$/',
            'bodyText'     => 'required|string|max:1024',
        ]);

        $components = $this->buildComponents();

        MetaMessageTemplate::updateOrCreate(
            ['name' => $this->templateName, 'language' => $this->language],
            [
                'category'   => $this->category,
                'status'     => 'DRAFT',
                'components' => $components,
            ]
        );

        $this->dispatch('toast', type: 'success', message: 'Rascunho salvo!');
        $this->showForm = false;
        $this->resetForm();
        $this->loadTemplates();
    }

    public function submitTemplate()
    {
        $this->validate([
            'templateName' => 'required|string|max:512|regex:/^[a-z0-9_]+$/',
            'bodyText'     => 'required|string|max:1024',
            'category'     => 'required|in:MARKETING,UTILITY',
        ]);

        $config = MetaWhatsAppConfig::current();
        if (!$config || !$config->whatsapp_business_account_id) {
            $this->dispatch('toast', type: 'error', message: 'Configure a Meta API primeiro (WABA ID necessário)');
            return;
        }

        $components = $this->buildComponents();

        $payload = [
            'name'       => $this->templateName,
            'language'   => $this->language,
            'category'   => $this->category,
            'components' => $components,
        ];

        $service = new MetaWhatsAppService($config);
        $result = $service->createTemplate($config->whatsapp_business_account_id, $payload);

        if ($result['success']) {
            // Salvar localmente
            MetaMessageTemplate::updateOrCreate(
                ['name' => $this->templateName, 'language' => $this->language],
                [
                    'template_id' => $result['data']['id'] ?? null,
                    'category'    => $this->category,
                    'status'      => $result['data']['status'] ?? 'PENDING',
                    'components'  => $components,
                ]
            );

            $this->dispatch('toast', type: 'success', message: 'Template enviado para análise da Meta!');
            $this->showForm = false;
            $this->resetForm();
            $this->loadTemplates();
        } else {
            $this->dispatch('toast', type: 'error', message: 'Erro: ' . ($result['error'] ?? 'Falha desconhecida'));
        }
    }

    public function syncTemplates()
    {
        $config = MetaWhatsAppConfig::current();
        if (!$config || !$config->whatsapp_business_account_id) {
            $this->dispatch('toast', type: 'error', message: 'WABA ID não configurado');
            return;
        }

        $service = new MetaWhatsAppService($config);
        $result = $service->fetchTemplates($config->whatsapp_business_account_id);

        if (!$result['success']) {
            $this->dispatch('toast', type: 'error', message: 'Erro ao sincronizar: ' . ($result['error'] ?? ''));
            return;
        }

        $count = 0;
        foreach ($result['data'] as $t) {
            MetaMessageTemplate::updateOrCreate(
                ['name' => $t['name'], 'language' => $t['language']],
                [
                    'template_id' => $t['id'] ?? null,
                    'category'    => $t['category'] ?? null,
                    'status'      => $t['status'] ?? 'UNKNOWN',
                    'components'  => $t['components'] ?? [],
                ]
            );
            $count++;
        }

        $this->loadTemplates();
        $this->dispatch('toast', type: 'success', message: "{$count} templates sincronizados!");
    }

    public function deleteTemplate($id)
    {
        $template = MetaMessageTemplate::find($id);
        if (!$template) return;

        $config = MetaWhatsAppConfig::current();
        if ($config && $config->whatsapp_business_account_id) {
            $service = new MetaWhatsAppService($config);
            $service->deleteTemplate($config->whatsapp_business_account_id, $template->name);
        }

        $template->delete();
        $this->loadTemplates();
        $this->dispatch('toast', type: 'success', message: 'Template removido');
    }

    private function buildComponents(): array
    {
        $components = [];

        if ($this->headerType === 'text' && $this->headerText) {
            $components[] = ['type' => 'HEADER', 'format' => 'TEXT', 'text' => $this->headerText];
        } elseif ($this->headerType === 'image') {
            $header = ['type' => 'HEADER', 'format' => 'IMAGE'];
            if ($this->headerImage) {
                $path = $this->headerImage->store('template-headers', 'public');
                $this->headerImageUrl = '/storage/' . $path;
                $header['example'] = ['header_handle' => [$this->headerImageUrl]];
            }
            $components[] = $header;
        }

        $components[] = ['type' => 'BODY', 'text' => $this->bodyText];

        if ($this->footerText) {
            $components[] = ['type' => 'FOOTER', 'text' => $this->footerText];
        }

        if (!empty($this->buttons)) {
            $btns = [];
            foreach ($this->buttons as $btn) {
                if ($btn['type'] === 'quick_reply') {
                    $btns[] = ['type' => 'QUICK_REPLY', 'text' => $btn['text']];
                } elseif ($btn['type'] === 'url') {
                    $btns[] = ['type' => 'URL', 'text' => $btn['text'], 'url' => $btn['value']];
                } elseif ($btn['type'] === 'phone') {
                    $btns[] = ['type' => 'PHONE_NUMBER', 'text' => $btn['text'], 'phone_number' => $btn['value']];
                }
            }
            if (!empty($btns)) {
                $components[] = ['type' => 'BUTTONS', 'buttons' => $btns];
            }
        }

        return $components;
    }

    private function resetForm()
    {
        $this->step = 'category';
        $this->templateName = '';
        $this->category = 'MARKETING';
        $this->language = 'pt_BR';
        $this->headerType = 'none';
        $this->headerText = '';
        $this->headerImage = null;
        $this->headerImageUrl = null;
        $this->bodyText = '';
        $this->footerText = '';
        $this->buttons = [];
        $this->newButtonType = '';
        $this->newButtonText = '';
        $this->newButtonValue = '';
    }

    public function render()
    {
        $counts = [
            'all'      => MetaMessageTemplate::count(),
            'approved' => MetaMessageTemplate::where('status', 'APPROVED')->count(),
            'pending'  => MetaMessageTemplate::whereIn('status', ['PENDING', 'IN_REVIEW'])->count(),
            'rejected' => MetaMessageTemplate::where('status', 'REJECTED')->count(),
            'draft'    => MetaMessageTemplate::where('status', 'DRAFT')->count(),
        ];

        return view('livewire.admin.template-manager', compact('counts'));
    }
}
