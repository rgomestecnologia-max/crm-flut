<?php

namespace App\Livewire\Admin;

use App\Models\FlutChatFlow;
use App\Models\FlutChatLead;
use App\Models\FlutChatStep;
use App\Models\FlutChatWidget;
use Illuminate\Support\Str;
use Livewire\Component;

class FlutChatManager extends Component
{
    // Widget
    public ?int $editingWidgetId = null;
    public string $widgetName = '';
    public string $widgetTitle = 'Olá! Como posso ajudar?';
    public string $widgetSubtitle = '';
    public string $widgetColor = '#b2ff00';
    public string $widgetPosition = 'bottom-right';
    public string $widgetWhatsapp = '';
    public string $widgetWhatsappMsg = '';
    public bool $showWidgetForm = false;

    // Flow editor
    public ?int $editingFlowId = null;
    public ?int $selectedWidgetId = null;

    // Step editor
    public bool $showStepForm = false;
    public ?int $editingStepId = null;
    public string $stepType = 'message';
    public string $stepContent = '';
    public string $stepInputKey = '';
    public string $stepInputPlaceholder = '';
    public array $stepOptions = [['label' => '', 'next_step_id' => '']];
    public ?int $stepNextId = null;
    public string $stepActionType = '';
    public string $stepActionValue = '';

    // Tab
    public string $tab = 'widgets';

    // ── Widget CRUD ──

    public function saveWidget(): void
    {
        $this->validate([
            'widgetName'  => 'required|string|max:100',
            'widgetTitle' => 'required|string|max:200',
            'widgetColor' => 'required|string|max:7',
        ]);

        $data = [
            'name'             => $this->widgetName,
            'title'            => $this->widgetTitle,
            'subtitle'         => $this->widgetSubtitle ?: null,
            'color'            => $this->widgetColor,
            'position'         => $this->widgetPosition,
            'whatsapp_number'  => $this->widgetWhatsapp ?: null,
            'whatsapp_message' => $this->widgetWhatsappMsg ?: null,
        ];

        if ($this->editingWidgetId) {
            FlutChatWidget::findOrFail($this->editingWidgetId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Widget atualizado.');
        } else {
            $widget = FlutChatWidget::create($data);
            // Cria fluxo padrão
            $flow = FlutChatFlow::create([
                'widget_id' => $widget->id,
                'name'      => 'Fluxo principal',
                'is_active' => true,
            ]);
            // Steps iniciais
            $step2 = FlutChatStep::create(['flow_id' => $flow->id, 'type' => 'action', 'content' => 'Obrigado! Redirecionando para o WhatsApp...', 'action_type' => 'whatsapp', 'sort_order' => 2]);
            FlutChatStep::create(['flow_id' => $flow->id, 'type' => 'input', 'content' => 'Qual é o seu nome?', 'input_key' => 'nome', 'input_placeholder' => 'Seu nome...', 'next_step_id' => $step2->id, 'sort_order' => 1]);

            $this->dispatch('toast', type: 'success', message: 'Widget criado com fluxo inicial.');
        }

        $this->resetWidgetForm();
    }

    public function editWidget(int $id): void
    {
        $w = FlutChatWidget::findOrFail($id);
        $this->editingWidgetId  = $w->id;
        $this->widgetName       = $w->name;
        $this->widgetTitle      = $w->title;
        $this->widgetSubtitle   = $w->subtitle ?? '';
        $this->widgetColor      = $w->color;
        $this->widgetPosition   = $w->position;
        $this->widgetWhatsapp   = $w->whatsapp_number ?? '';
        $this->widgetWhatsappMsg = $w->whatsapp_message ?? '';
        $this->showWidgetForm   = true;
    }

    public function deleteWidget(int $id): void
    {
        FlutChatWidget::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Widget excluído.');
    }

    public function toggleWidget(int $id): void
    {
        $w = FlutChatWidget::findOrFail($id);
        $w->update(['is_active' => !$w->is_active]);
    }

    private function resetWidgetForm(): void
    {
        $this->editingWidgetId = null;
        $this->widgetName = '';
        $this->widgetTitle = 'Olá! Como posso ajudar?';
        $this->widgetSubtitle = '';
        $this->widgetColor = '#b2ff00';
        $this->widgetPosition = 'bottom-right';
        $this->widgetWhatsapp = '';
        $this->widgetWhatsappMsg = '';
        $this->showWidgetForm = false;
    }

    // ── Flow/Steps Editor ──

    public function openFlowEditor(int $widgetId): void
    {
        $this->selectedWidgetId = $widgetId;
        $widget = FlutChatWidget::findOrFail($widgetId);
        $flow = $widget->activeFlow ?? $widget->flows()->first();
        $this->editingFlowId = $flow?->id;
        $this->tab = 'editor';
    }

    public function saveStep(): void
    {
        $data = [
            'flow_id'           => $this->editingFlowId,
            'type'              => $this->stepType,
            'content'           => $this->stepContent ?: null,
            'input_key'         => $this->stepType === 'input' ? $this->stepInputKey : null,
            'input_placeholder' => $this->stepType === 'input' ? $this->stepInputPlaceholder : null,
            'options'           => $this->stepType === 'options' ? array_filter($this->stepOptions, fn($o) => !empty($o['label'])) : null,
            'next_step_id'      => in_array($this->stepType, ['message', 'input']) ? ($this->stepNextId ?: null) : null,
            'action_type'       => $this->stepType === 'action' ? $this->stepActionType : null,
            'action_value'      => $this->stepType === 'action' ? $this->stepActionValue : null,
        ];

        if ($this->editingStepId) {
            FlutChatStep::findOrFail($this->editingStepId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Step atualizado.');
        } else {
            $maxOrder = FlutChatStep::where('flow_id', $this->editingFlowId)->max('sort_order') ?? 0;
            $data['sort_order'] = $maxOrder + 1;
            FlutChatStep::create($data);
            $this->dispatch('toast', type: 'success', message: 'Step adicionado.');
        }

        $this->resetStepForm();
    }

    public function editStep(int $id): void
    {
        $s = FlutChatStep::findOrFail($id);
        $this->editingStepId      = $s->id;
        $this->stepType           = $s->type;
        $this->stepContent        = $s->content ?? '';
        $this->stepInputKey       = $s->input_key ?? '';
        $this->stepInputPlaceholder = $s->input_placeholder ?? '';
        $this->stepOptions        = $s->options ?: [['label' => '', 'next_step_id' => '']];
        $this->stepNextId         = $s->next_step_id;
        $this->stepActionType     = $s->action_type ?? '';
        $this->stepActionValue    = $s->action_value ?? '';
        $this->showStepForm       = true;
    }

    public function moveStepUp(int $id): void
    {
        $step = FlutChatStep::findOrFail($id);
        $prev = FlutChatStep::where('flow_id', $step->flow_id)
            ->where('sort_order', '<', $step->sort_order)
            ->orderByDesc('sort_order')->first();
        if ($prev) {
            $tmpOrder = $step->sort_order;
            $step->update(['sort_order' => $prev->sort_order]);
            $prev->update(['sort_order' => $tmpOrder]);
        }
    }

    public function moveStepDown(int $id): void
    {
        $step = FlutChatStep::findOrFail($id);
        $next = FlutChatStep::where('flow_id', $step->flow_id)
            ->where('sort_order', '>', $step->sort_order)
            ->orderBy('sort_order')->first();
        if ($next) {
            $tmpOrder = $step->sort_order;
            $step->update(['sort_order' => $next->sort_order]);
            $next->update(['sort_order' => $tmpOrder]);
        }
    }

    public function deleteStep(int $id): void
    {
        FlutChatStep::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Step excluído.');
    }

    public function addOption(): void
    {
        $this->stepOptions[] = ['label' => '', 'next_step_id' => ''];
    }

    public function removeOption(int $index): void
    {
        unset($this->stepOptions[$index]);
        $this->stepOptions = array_values($this->stepOptions);
    }

    private function resetStepForm(): void
    {
        $this->editingStepId = null;
        $this->stepType = 'message';
        $this->stepContent = '';
        $this->stepInputKey = '';
        $this->stepInputPlaceholder = '';
        $this->stepOptions = [['label' => '', 'next_step_id' => '']];
        $this->stepNextId = null;
        $this->stepActionType = '';
        $this->stepActionValue = '';
        $this->showStepForm = false;
    }

    public function render()
    {
        $widgets = FlutChatWidget::withCount('leads')->orderBy('name')->get();

        $flowSteps = collect();
        $allSteps = collect();
        if ($this->editingFlowId) {
            $flowSteps = FlutChatStep::where('flow_id', $this->editingFlowId)->orderBy('sort_order')->get();
            $allSteps = $flowSteps;
        }

        $recentLeads = collect();
        if ($this->tab === 'leads') {
            $recentLeads = FlutChatLead::with('widget')->latest()->take(50)->get();
        }

        return view('livewire.admin.flut-chat-manager', compact('widgets', 'flowSteps', 'allSteps', 'recentLeads'));
    }
}
