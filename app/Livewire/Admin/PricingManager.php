<?php

namespace App\Livewire\Admin;

use App\Models\PricingConfig;
use Livewire\Component;

class PricingManager extends Component
{
    public array $prices = [];

    public function mount(): void
    {
        // Só Rogerio Gomes (user_id 2) pode acessar
        if (auth()->id() !== 2) {
            abort(403);
        }

        PricingConfig::seed();
        $this->prices = PricingConfig::getAll();
    }

    public function save(): void
    {
        foreach ($this->prices as $key => $value) {
            PricingConfig::set($key, $value);
        }
        $this->dispatch('toast', type: 'success', message: 'Preços atualizados.');
    }

    public function render()
    {
        $labels = [
            'multi_base_price'     => ['Multi-atendimento — Preço base (até X usuários)', 'R$/mês'],
            'multi_base_users'     => ['Multi-atendimento — Usuários inclusos no base', 'usuários'],
            'multi_extra_user'     => ['Multi-atendimento — Usuário adicional', 'R$/mês'],
            'multi_extra_instance' => ['Multi-atendimento — Instância WhatsApp adicional', 'R$/mês'],
            'multi_setup'          => ['Multi-atendimento — Implantação', 'R$'],
            'crm_price'            => ['CRM — Mensalidade', 'R$/mês'],
            'crm_setup'            => ['CRM — Implantação', 'R$'],
            'email_5k_price'       => ['Disparos Email — Até 5 mil/mês', 'R$/mês'],
            'email_20k_price'      => ['Disparos Email — Até 20 mil/mês', 'R$/mês'],
            'email_50k_price'      => ['Disparos Email — Até 50 mil/mês', 'R$/mês'],
            'email_setup'          => ['Disparos Email — Implantação', 'R$'],
            'ia_flow_price'        => ['IA — Preço por fluxo/agente', 'R$/mês'],
            'ia_flow_setup'        => ['IA — Implantação por fluxo', 'R$'],
            'integration_setup'    => ['Integração externa — Implantação (cada)', 'R$'],
            'integration_monthly'  => ['Integração externa — Mensalidade (cada)', 'R$/mês'],
        ];

        return view('livewire.admin.pricing-manager', compact('labels'));
    }
}
