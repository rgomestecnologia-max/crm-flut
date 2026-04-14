<?php

namespace App\Livewire\Admin;

use App\Models\AiBotConfig;
use App\Models\ChatbotMenuConfig;
use App\Models\Department;
use Livewire\Component;

class ChatbotMenuManager extends Component
{
    public bool   $is_active              = false;
    public string $company_name           = '';
    public string $welcome_template       = '';
    public string $menu_prompt            = '';
    public string $invalid_option_message = '';
    public string $after_selection_message = '';

    public function mount(): void
    {
        $config = ChatbotMenuConfig::current();

        if ($config) {
            $this->is_active               = $config->is_active;
            $this->company_name            = $config->company_name;
            $this->welcome_template        = $config->welcome_template;
            $this->menu_prompt             = $config->menu_prompt;
            $this->invalid_option_message  = $config->invalid_option_message;
            $this->after_selection_message = $config->after_selection_message ?? '';
        } else {
            $this->welcome_template       = "Olá {nome}!\nSeja muito bem-vindo(a) ao atendimento digital da {empresa}.";
            $this->menu_prompt            = 'Digite o *número* do setor que deseja falar:';
            $this->invalid_option_message = 'Opção inválida. Por favor, digite apenas o número correspondente ao setor desejado.';
            $this->after_selection_message = 'Perfeito! Direcionando você para o setor de *{departamento}*. Em breve um de nossos atendentes irá te responder. 😊';
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
        ChatbotMenuConfig::updateOrCreate(['id' => 1], [
            'is_active'               => $this->is_active,
            'company_name'            => $this->company_name,
            'welcome_template'        => $this->welcome_template,
            'menu_prompt'             => $this->menu_prompt,
            'invalid_option_message'  => $this->invalid_option_message,
            'after_selection_message' => $this->after_selection_message ?: null,
        ]);
    }

    public function render()
    {
        $departments = Department::active()->orderBy('name')->get();
        return view('livewire.admin.chatbot-menu-manager', compact('departments'));
    }
}
