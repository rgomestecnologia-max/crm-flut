<?php

namespace App\Livewire\Admin;

use App\Models\Automation;
use App\Models\CrmCustomField;
use App\Models\CrmPipeline;
use App\Models\MetaMessageTemplate;
use App\Services\WhatsAppProvider;
use Livewire\Component;

class AutomationManager extends Component
{
    public bool    $showForm           = false;
    public ?int    $editingId          = null;
    public string  $name               = '';
    public ?int    $pipeline_id        = null;
    public string  $message_template   = '';
    public bool    $is_active          = true;
    public bool    $enable_ai_on_reply = false;
    public bool    $ai_first_response  = false;
    public bool    $ai_greeting        = false;
    public string  $follow_up_message       = '';
    public int     $follow_up_delay_minutes = 120;
    public string  $reply_yes_message       = '';
    public string  $reply_no_message        = '';
    public ?int    $reply_yes_stage_id = null;
    public ?int    $reply_no_stage_id  = null;
    public string  $meta_template_name = '';

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'pipeline_id', 'message_template', 'meta_template_name', 'is_active', 'enable_ai_on_reply', 'ai_first_response', 'ai_greeting', 'follow_up_message', 'follow_up_delay_minutes', 'reply_yes_message', 'reply_no_message', 'reply_yes_stage_id', 'reply_no_stage_id']);
        $this->follow_up_delay_minutes = 120;
        $this->is_active = true;
        $this->showForm  = true;
    }

    public function edit(int $id): void
    {
        $a = Automation::findOrFail($id);

        $this->editingId          = $id;
        $this->name               = $a->name;
        $this->pipeline_id        = $a->pipeline_id;
        $this->message_template   = $a->message_template ?? '';
        $this->is_active          = $a->is_active;
        $this->enable_ai_on_reply = (bool) $a->enable_ai_on_reply;
        $this->ai_first_response  = (bool) $a->ai_first_response;
        $this->ai_greeting        = (bool) $a->ai_greeting;
        $this->follow_up_message       = $a->follow_up_message ?? '';
        $this->follow_up_delay_minutes = $a->follow_up_delay_minutes ?? 120;
        $this->reply_yes_message       = $a->reply_yes_message ?? '';
        $this->reply_no_message        = $a->reply_no_message ?? '';
        $this->reply_yes_stage_id = $a->reply_yes_stage_id;
        $this->reply_no_stage_id  = $a->reply_no_stage_id;
        $this->meta_template_name = $a->meta_template_name ?? '';
        $this->showForm           = true;
    }

    public function save(): void
    {
        $rules = [
            'name'             => 'required|string|max:150',
            'pipeline_id'      => 'nullable|exists:crm_pipelines,id',
        ];

        // Mensagem template só é obrigatória se não usar IA direta
        if (!$this->ai_first_response) {
            $rules['message_template'] = 'required|string|max:4096';
        }

        $this->validate($rules);

        $data = [
            'name'               => $this->name,
            'pipeline_id'        => $this->pipeline_id ?: null,
            'trigger'            => 'lead_created',
            'message_template'   => $this->message_template ?: null,
            'is_active'           => $this->is_active,
            'enable_ai_on_reply'  => $this->enable_ai_on_reply,
            'ai_first_response'   => $this->ai_first_response,
            'ai_greeting'         => $this->ai_greeting,
            'follow_up_message'        => $this->follow_up_message ?: null,
            'follow_up_delay_minutes'  => $this->follow_up_message ? $this->follow_up_delay_minutes : null,
            'reply_yes_message'        => $this->reply_yes_message ?: null,
            'reply_no_message'         => $this->reply_no_message ?: null,
            'reply_yes_stage_id'  => $this->reply_yes_stage_id ?: null,
            'reply_no_stage_id'   => $this->reply_no_stage_id ?: null,
            'meta_template_name'  => $this->meta_template_name ?: null,
        ];

        if ($this->editingId) {
            Automation::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Automação atualizada.');
        } else {
            Automation::create($data);
            $this->dispatch('toast', type: 'success', message: 'Automação criada com sucesso!');
        }

        $this->showForm = false;
        $this->reset(['editingId', 'name', 'pipeline_id', 'message_template', 'meta_template_name', 'is_active', 'enable_ai_on_reply', 'ai_first_response', 'ai_greeting', 'follow_up_message', 'follow_up_delay_minutes', 'reply_yes_message', 'reply_no_message', 'reply_yes_stage_id', 'reply_no_stage_id']);
        $this->follow_up_delay_minutes = 120;
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
        $automations   = Automation::with('pipeline')->latest()->get();
        $pipelines     = CrmPipeline::active()->with(['stages' => fn($q) => $q->orderBy('sort_order')])->orderBy('sort_order')->get();
        $customFields  = CrmCustomField::orderBy('sort_order')->get();
        $isMeta        = WhatsAppProvider::isMeta();
        $metaTemplates = $isMeta ? MetaMessageTemplate::approved()->orderBy('name')->get() : collect();

        return view('livewire.admin.automation-manager', compact('automations', 'pipelines', 'customFields', 'isMeta', 'metaTemplates'));
    }
}
