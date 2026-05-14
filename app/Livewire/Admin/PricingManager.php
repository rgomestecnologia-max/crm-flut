<?php

namespace App\Livewire\Admin;

use App\Models\PricingConfig;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class PricingManager extends Component
{
    use WithFileUploads;

    public array $prices = [];
    public $multi_image, $crm_image, $email_image, $ia_image, $integration_image;

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
        $imageKeys = [
            'multi_image' => 'multi_screenshot',
            'crm_image' => 'crm_screenshot',
            'email_image' => 'email_screenshot',
            'ia_image' => 'ia_screenshot',
            'integration_image' => 'integration_screenshot',
        ];

        foreach ($imageKeys as $prop => $configKey) {
            if ($this->$prop) {
                $filename = str_replace('_image', '', $prop) . '.' . $this->$prop->getClientOriginalExtension();
                $this->$prop->storeAs('modules', $filename, 'public');
                $this->prices[$configKey] = '/storage/modules/' . $filename;
                $this->$prop = null;
            }
        }

        foreach ($this->prices as $key => $value) {
            PricingConfig::set($key, $value);
        }
        $this->dispatch('toast', type: 'success', message: 'Preços atualizados.');
    }

    public function removeScreenshot(string $configKey): void
    {
        $path = $this->prices[$configKey] ?? '';
        if ($path) {
            $storagePath = str_replace('/storage/', '', $path);
            Storage::disk('public')->delete($storagePath);
        }
        $this->prices[$configKey] = '';
        PricingConfig::set($configKey, '');
        $this->dispatch('toast', type: 'success', message: 'Imagem removida.');
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
