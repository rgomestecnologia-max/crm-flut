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
    public $multi_image, $crm_image, $email_image, $ia_image, $integration_image, $chat_interno_image, $landing_image, $flutchat_image, $flutzap_image, $consultoria_image;

    public function mount(): void
    {
        // Admin e vendedor podem acessar
        if (!auth()->user()->isAdmin() && !auth()->user()->isVendedor()) {
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
            'chat_interno_image' => 'chat_interno_screenshot',
            'landing_image' => 'landing_screenshot',
            'flutchat_image' => 'flutchat_screenshot',
            'flutzap_image' => 'flutzap_screenshot',
            'consultoria_image' => 'consultoria_screenshot',
        ];

        foreach ($imageKeys as $prop => $configKey) {
            if ($this->$prop) {
                $filename = str_replace('_image', '', $prop) . '.' . $this->$prop->getClientOriginalExtension();
                $path = 'modules/' . $filename;
                \App\Services\MediaStorage::put($path, file_get_contents($this->$prop->getRealPath()));
                $this->prices[$configKey] = \App\Services\MediaStorage::url($path);
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
            // Remove do R2 ou storage local
            $filename = basename(parse_url($path, PHP_URL_PATH));
            \App\Services\MediaStorage::delete('modules/' . $filename);
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
            'multi_messenger_price' => ['Multi-atendimento — Messenger Facebook', 'R$/mês'],
            'multi_instagram_price' => ['Multi-atendimento — Instagram Direct', 'R$/mês'],
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
            'chat_interno_price'   => ['Chat Interno — Mensalidade', 'R$/mês'],
            'chat_interno_setup'   => ['Chat Interno — Implantação', 'R$'],
            'flutchat_price'       => ['FlutChat — Mensalidade (sem IA)', 'R$/mês'],
            'flutchat_ia_price'    => ['FlutChat — Mensalidade (com IA)', 'R$/mês'],
            'flutchat_setup'       => ['FlutChat — Implantação', 'R$'],
            'flutzap_price'        => ['FlutZap — Mensalidade', 'R$/mês'],
            'flutzap_setup'        => ['FlutZap — Implantação', 'R$'],
            'consultoria_price'    => ['Gestão Consultiva e Operacional — Mensalidade', 'R$/mês'],
            'consultoria_hours'    => ['Gestão Consultiva e Operacional — Horas/mês', 'horas'],
            'consultoria_setup'    => ['Gestão Consultiva e Operacional — Implantação', 'R$'],
            'landing_price'        => ['Landing Pages — Mensalidade', 'R$/mês'],
            'landing_setup'        => ['Landing Pages — Implantação', 'R$'],
        ];

        return view('livewire.admin.pricing-manager', compact('labels'));
    }
}
