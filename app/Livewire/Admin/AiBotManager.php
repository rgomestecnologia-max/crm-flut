<?php

namespace App\Livewire\Admin;

use App\Models\AiBotConfig;
use App\Models\ChatbotMenuConfig;
use App\Models\Department;
use App\Models\GlobalSetting;
use Livewire\Component;

class AiBotManager extends Component
{
    public bool   $is_active                 = false;
    public string $system_prompt             = '';
    public string $department_routing_prompt = '';
    public string $initial_greeting          = '';
    public int    $max_bot_turns             = 5;
    public string $handoff_message           = '';

    public function mount(): void
    {
        $config = AiBotConfig::current();
        if ($config) {
            $this->is_active                 = $config->is_active;
            $this->system_prompt             = $config->system_prompt ?? '';
            $this->department_routing_prompt = $config->department_routing_prompt ?? '';
            $this->initial_greeting          = $config->initial_greeting ?? '';
            $this->max_bot_turns             = $config->max_bot_turns ?? 5;
            $this->handoff_message           = $config->handoff_message ?? '';
        }
    }

    public function toggleActive(): void
    {
        $config = AiBotConfig::current() ?? AiBotConfig::create([]);

        if (!$config->hasKey()) {
            $this->dispatch('toast', type: 'error', message: 'A chave da API Gemini não está configurada. Peça ao administrador do sistema para configurar em Configurações Globais.');
            return;
        }

        $this->is_active = !$this->is_active;
        $config->update(['is_active' => $this->is_active]);

        if ($this->is_active) {
            ChatbotMenuConfig::query()->update(['is_active' => false]);
            $this->dispatch('toast', type: 'success', message: 'IA ativada. Chatbot foi desativado automaticamente.');
        } else {
            $this->dispatch('toast', type: 'success', message: 'IA de atendimento desativada.');
        }
    }

    public function save(): void
    {
        $this->validate([
            'system_prompt'             => 'nullable|string|max:8000',
            'department_routing_prompt' => 'nullable|string|max:2000',
            'initial_greeting'          => 'nullable|string|max:1000',
            'max_bot_turns'             => 'required|integer|min:1|max:50',
            'handoff_message'           => 'nullable|string|max:1000',
        ]);

        $data = [
            'system_prompt'             => $this->system_prompt ?: null,
            'department_routing_prompt' => $this->department_routing_prompt ?: null,
            'initial_greeting'          => $this->initial_greeting ?: null,
            'max_bot_turns'             => $this->max_bot_turns,
            'handoff_message'           => $this->handoff_message ?: null,
        ];

        AiBotConfig::updateOrCreate(
            ['company_id' => app(\App\Services\CurrentCompany::class)->id()],
            $data
        );

        $this->dispatch('toast', type: 'success', message: 'Configurações do robô salvas.');
    }

    public function render()
    {
        $departments    = Department::active()->get();
        $globalKeySet   = !empty(GlobalSetting::get('gemini_api_key'));
        $globalModel    = GlobalSetting::get('gemini_model', 'gemini-2.0-flash');
        return view('livewire.admin.ai-bot-manager', compact('departments', 'globalKeySet', 'globalModel'));
    }
}
