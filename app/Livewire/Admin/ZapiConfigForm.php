<?php

namespace App\Livewire\Admin;

use App\Models\ZapiConfig;
use App\Services\ZapiService;
use Livewire\Component;

class ZapiConfigForm extends Component
{
    // Campos que existem no painel Z-API
    public string $instance_id  = '';   // ID da Instância
    public string $token        = '';   // Token da Instância
    public string $client_token = '';   // Security Token (opcional — aba Segurança do Z-API)

    public string $connectionStatus = 'disconnected';
    public bool   $testing          = false;
    public bool   $tokenSaved       = false;
    public bool   $isActive         = false;

    public function mount(): void
    {
        $config = ZapiConfig::first();
        if ($config) {
            $this->instance_id      = $config->instance_id;
            $this->connectionStatus = $config->connection_status;
            $this->tokenSaved       = !empty($config->token);
            $this->isActive         = (bool) $config->is_active;
        }
    }

    public function toggleActive(): void
    {
        $config = ZapiConfig::first();
        if (!$config) return;

        $this->isActive = !$this->isActive;
        $config->update(['is_active' => $this->isActive]);

        $msg = $this->isActive ? 'Z-API ativado.' : 'Z-API desativado. Sistema usará Evolution API.';
        $this->dispatch('toast', type: 'warning', message: $msg);
    }

    public function save(): void
    {
        $rules = [
            'instance_id'  => 'required|string|max:200',
            'client_token' => 'nullable|string|max:200',
        ];

        // Token só é obrigatório se ainda não foi salvo
        if (!$this->tokenSaved || !empty($this->token)) {
            $rules['token'] = 'required|string|max:200';
        }

        $validated = $this->validate($rules);

        $existing = ZapiConfig::first();
        $data = [
            'instance_id'  => $validated['instance_id'],
            'client_token' => $validated['client_token'] ?? null,
            'is_active'    => $existing?->is_active ?? false,
        ];

        // Só atualiza o token se foi digitado
        if (!empty($this->token)) {
            $data['token'] = $this->token;
        }

        ZapiConfig::updateOrCreate(['id' => 1], $data);

        $this->token     = '';
        $this->tokenSaved = true;
        $this->dispatch('toast', type: 'success', message: 'Credenciais Z-API salvas com sucesso.');
    }

    public function testConnection(): void
    {
        $config = ZapiConfig::first();

        if (!$config || !$config->token) {
            $this->dispatch('toast', type: 'error', message: 'Salve as credenciais antes de testar.');
            return;
        }

        $this->testing = true;
        $service = new ZapiService($config);
        $result  = $service->getConnectionStatus();

        if ($result['success'] ?? false) {
            $connected = $result['connected'] ?? false;
            $status    = $connected ? 'connected' : 'disconnected';

            $updateData = ['connection_status' => $status];
            if (!empty($result['phone'])) {
                $updateData['phone_number'] = $result['phone'];
            }

            $config->update($updateData);
            $this->connectionStatus = $status;

            $msg = $connected
                ? '✓ WhatsApp conectado' . (!empty($result['phone']) ? ' — ' . $result['phone'] : '')
                : 'Instância desconectada. Escaneie o QR Code no painel Z-API.';

            $this->dispatch('toast', type: $connected ? 'success' : 'warning', message: $msg);
        } else {
            $this->dispatch('toast', type: 'error', message: 'Erro: ' . ($result['error'] ?? 'Verifique as credenciais.'));
        }

        $this->testing = false;
    }

    public function render()
    {
        return view('livewire.admin.zapi-config-form');
    }
}
