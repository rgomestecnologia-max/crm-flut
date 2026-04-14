<?php

namespace App\Livewire\Admin;

use App\Models\Automation;
use App\Models\CrmCustomField;
use App\Models\CrmPipeline;
use Livewire\Component;

class AutomationManager extends Component
{
    public bool    $showForm          = false;
    public ?int    $editingId         = null;
    public string  $name              = '';
    public ?int    $pipeline_id       = null;
    public string  $message_template  = '';
    public bool    $is_active         = true;
    public bool    $enable_ai_on_reply = false;

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'pipeline_id', 'message_template', 'is_active', 'enable_ai_on_reply']);
        $this->is_active = true;
        $this->showForm  = true;
    }

    public function edit(int $id): void
    {
        $a = Automation::findOrFail($id);

        $this->editingId          = $id;
        $this->name               = $a->name;
        $this->pipeline_id        = $a->pipeline_id;
        $this->message_template   = $a->message_template;
        $this->is_active          = $a->is_active;
        $this->enable_ai_on_reply = $a->enable_ai_on_reply;
        $this->showForm           = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'             => 'required|string|max:150',
            'pipeline_id'      => 'nullable|exists:crm_pipelines,id',
            'message_template' => 'required|string|max:4096',
        ]);

        $data = [
            'name'              => $this->name,
            'pipeline_id'       => $this->pipeline_id ?: null,
            'trigger'           => 'lead_created',
            'message_template'  => $this->message_template,
            'is_active'         => $this->is_active,
            'enable_ai_on_reply' => $this->enable_ai_on_reply,
        ];

        if ($this->editingId) {
            Automation::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Automação atualizada.');
        } else {
            Automation::create($data);
            $this->dispatch('toast', type: 'success', message: 'Automação criada com sucesso!');
        }

        $this->showForm = false;
        $this->reset(['editingId', 'name', 'pipeline_id', 'message_template', 'is_active']);
    }

    public function toggleActive(int $id): void
    {
        $a = Automation::findOrFail($id);
        $a->update(['is_active' => !$a->is_active]);
        $this->dispatch('toast', type: 'success', message: $a->is_active ? 'Automação pausada.' : 'Automação ativada.');
    }

    public function delete(int $id): void
    {
        Automation::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Automação removida.');
    }

    public function insertVariable(string $var): void
    {
        $this->message_template .= $var;
    }

    public function render()
    {
        $automations  = Automation::with('pipeline')->latest()->get();
        $pipelines    = CrmPipeline::active()->orderBy('sort_order')->get();
        $customFields = CrmCustomField::orderBy('sort_order')->get();

        return view('livewire.admin.automation-manager', compact('automations', 'pipelines', 'customFields'));
    }
}
