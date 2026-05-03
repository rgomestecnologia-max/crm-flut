<?php

namespace App\Livewire\Admin;

use App\Models\AiBotConfig;
use App\Models\ChatbotMenuConfig;
use App\Models\Department;
use Livewire\Component;

class ChatbotMenuManager extends Component
{
    public bool   $is_active              = false;
    public bool   $reply_in_groups        = false;
    public string $company_name           = '';
    public string $welcome_template       = '';
    public string $menu_prompt            = '';
    public string $invalid_option_message = '';
    public string $after_selection_message = '';
    public array  $menu_departments       = [];
    public bool   $business_hours_enabled = false;
    public array  $business_hours         = [];
    public string $outside_hours_message  = '';

    public function mount(): void
    {
        $config = ChatbotMenuConfig::current();

        if ($config) {
            $this->is_active               = $config->is_active;
            $this->reply_in_groups         = $config->reply_in_groups ?? false;
            $this->company_name            = $config->company_name;
            $this->welcome_template        = $config->welcome_template;
            $this->menu_prompt             = $config->menu_prompt;
            $this->invalid_option_message  = $config->invalid_option_message;
            $this->after_selection_message = $config->after_selection_message ?? '';
            $this->menu_departments        = $config->menu_departments ?? [];
            $this->business_hours_enabled  = $config->business_hours_enabled ?? false;
            $this->business_hours          = $config->business_hours ?? $this->defaultBusinessHours();
            $this->outside_hours_message   = $config->outside_hours_message ?? '';
        } else {
            $this->welcome_template       = "Olá {nome}!\nSeja muito bem-vindo(a) ao atendimento digital da {empresa}.";
            $this->menu_prompt            = 'Digite o *número* do setor que deseja falar:';
            $this->invalid_option_message = 'Opção inválida. Por favor, digite apenas o número correspondente ao setor desejado.';
            $this->after_selection_message = 'Perfeito! Direcionando você para o setor de *{departamento}*. Em breve um de nossos atendentes irá te responder. 😊';
            $this->business_hours          = $this->defaultBusinessHours();
            $this->outside_hours_message   = 'Olá! Nosso horário de atendimento é de segunda a sexta, das 08:00 às 18:00. Deixe sua mensagem que retornaremos assim que possível!';
        }
    }

    public function toggleActive(): void
    {
        $this->is_active = !$this->is_active;
        $this->saveConfig();

        if ($this->is_active) {
            // Desativa a IA de atendimento automaticamente
            AiBotConfig::where('id', 1)->update(['is_active' => false]);
            $this->dispatch('toast', type: 'success', message: 'Chatbot ativado. IA de Atendimento foi desativada automaticamente.');
        } else {
            $this->dispatch('toast', type: 'success', message: 'Chatbot desativado.');
        }
    }

    public function save(): void
    {
        $this->validate([
            'company_name'            => 'required|string|max:200',
            'welcome_template'        => 'required|string|max:2000',
            'menu_prompt'             => 'required|string|max:500',
            'invalid_option_message'  => 'required|string|max:500',
            'after_selection_message' => 'nullable|string|max:500',
        ]);

        $this->saveConfig();
        $this->dispatch('toast', type: 'success', message: 'Configurações do menu salvas.');
    }

    private function saveConfig(): void
    {
        ChatbotMenuConfig::updateOrCreate(
            ['company_id' => app(\App\Services\CurrentCompany::class)->id()],
            [
            'is_active'               => $this->is_active,
            'reply_in_groups'         => $this->reply_in_groups,
            'company_name'            => $this->company_name,
            'welcome_template'        => $this->welcome_template,
            'menu_prompt'             => $this->menu_prompt,
            'invalid_option_message'  => $this->invalid_option_message,
            'after_selection_message'  => $this->after_selection_message ?: null,
            'menu_departments'        => !empty($this->menu_departments) ? array_map('intval', $this->menu_departments) : null,
            'business_hours_enabled'  => $this->business_hours_enabled,
            'business_hours'          => $this->business_hours,
            'outside_hours_message'   => $this->outside_hours_message ?: null,
        ]);
    }

    private function defaultBusinessHours(): array
    {
        $default = [];
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
            $default[$day] = ['active' => true, 'start' => '08:00', 'end' => '18:00'];
        }
        $default['saturday'] = ['active' => false, 'start' => '08:00', 'end' => '12:00'];
        $default['sunday']   = ['active' => false, 'start' => '08:00', 'end' => '12:00'];
        return $default;
    }

    public function render()
    {
        $departments = Department::active()->orderBy('sort_order')->orderBy('name')->get();
        return view('livewire.admin.chatbot-menu-manager', compact('departments'));
    }
}
