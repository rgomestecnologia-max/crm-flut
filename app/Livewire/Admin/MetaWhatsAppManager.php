<?php

namespace App\Livewire\Admin;

use App\Models\Company;
use App\Models\MetaWhatsAppConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class MetaWhatsAppManager extends Component
{
    public string $phone_number_id              = '';
    public string $whatsapp_business_account_id = '';
    public string $access_token                 = '';
    public string $verify_token                 = '';
    public string $phone_display                = '';
    public bool   $is_active                    = false;
    public string $whatsapp_provider            = 'evolution';

    public string $testResult    = '';
    public string $testStatus    = '';
    public string $webhookUrl    = '';

    public function mount(): void
    {
        $config = MetaWhatsAppConfig::current();

        if ($config) {
            $this->phone_number_id              = $config->phone_number_id ?? '';
            $this->whatsapp_business_account_id = $config->whatsapp_business_account_id ?? '';
            $this->access_token                 = $config->getRawOriginal('access_token') ?? '';
            $this->verify_token                 = $config->verify_token ?? '';
            $this->phone_display                = $config->phone_display ?? '';
            $this->is_active                    = $config->is_active;
        }

        if (!$this->verify_token) {
            $this->verify_token = Str::random(32);
        }

        $company = app(\App\Services\CurrentCompany::class)->model();
        $this->whatsapp_provider = $company?->whatsapp_provider ?? 'evolution';

        $this->webhookUrl = rtrim(config('app.url'), '/') . '/api/webhook/meta';
    }

    public function save(): void
    {
        $this->validate([
            'phone_number_id'              => 'required|string|max:50',
            'whatsapp_business_account_id' => 'nullable|string|max:50',
            'access_token'                 => 'required|string',
            'verify_token'                 => 'required|string|max:100',
            'phone_display'                => 'nullable|string|max:20',
        ]);

        MetaWhatsAppConfig::updateOrCreate(
            ['company_id' => app(\App\Services\CurrentCompany::class)->id()],
            [
                'phone_number_id'              => $this->phone_number_id,
                'whatsapp_business_account_id' => $this->whatsapp_business_account_id,
                'access_token'                 => $this->access_token,
                'verify_token'                 => $this->verify_token,
                'phone_display'                => $this->phone_display ?: null,
                'is_active'                    => $this->is_active,
            ]
        );

        $this->dispatch('toast', type: 'success', message: 'Configurações da Meta WhatsApp salvas.');
    }

    public function toggleActive(): void
    {
        $this->is_active = !$this->is_active;
        $this->save();
    }

    public function switchProvider(string $provider): void
    {
        $company = app(\App\Services\CurrentCompany::class)->model();
        if ($company) {
            $company->update(['whatsapp_provider' => $provider]);
            $this->whatsapp_provider = $provider;
            $this->dispatch('toast', type: 'success', message: 'Provider alterado para ' . ($provider === 'meta' ? 'Meta WhatsApp' : 'Evolution API') . '.');
        }
    }

    public function testConnection(): void
    {
        $this->testResult = '';
        $this->testStatus = '';

        if (!$this->phone_number_id || !$this->access_token) {
            $this->testResult = 'Preencha Phone Number ID e Access Token primeiro.';
            $this->testStatus = 'error';
            return;
        }

        try {
            $response = Http::withToken($this->access_token)
                ->timeout(10)
                ->get("https://graph.facebook.com/v21.0/{$this->phone_number_id}");

            if ($response->successful()) {
                $data = $response->json();
                $display = $data['display_phone_number'] ?? $data['verified_name'] ?? 'OK';
                $this->testResult = "Conexão OK — {$display}";
                $this->testStatus = 'success';

                if (!empty($data['display_phone_number'])) {
                    $this->phone_display = $data['display_phone_number'];
                }
            } else {
                $error = $response->json()['error']['message'] ?? $response->body();
                $this->testResult = "Erro: {$error}";
                $this->testStatus = 'error';
            }
        } catch (\Exception $e) {
            $this->testResult = 'Falha: ' . $e->getMessage();
            $this->testStatus = 'error';
        }
    }

    public function generateVerifyToken(): void
    {
        $this->verify_token = Str::random(32);
    }

    public function render()
    {
        return view('livewire.admin.meta-whatsapp-manager');
    }
}
