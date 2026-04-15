<?php

namespace App\Livewire\Admin;

use App\Models\EvolutionApiConfig;
use App\Services\EvolutionApiService;
use Livewire\Component;

class EvolutionApiManager extends Component
{
    // Form fields
    public string $server_url    = '';
    public string $global_api_key = '';
    public string $instance_name = 'crm-whatsapp';
    public bool   $groups_ignore  = false;
    public bool   $always_online  = false;
    public bool   $read_messages  = false;
    public bool   $reject_call    = false;
    public string $msg_call       = '';

    // State
    public ?string $connectionStatus = null;
    public ?string $profileName      = null;
    public ?string $phoneNumber      = null;
    public ?string $qrBase64         = null;
    public ?string $pairingCode      = null;
    public ?string $instanceApiKey   = null;
    public bool    $instanceExists   = false;
    public bool    $loading          = false;
    public ?array  $webhookInfo      = null;

    public function mount(): void
    {
        $config = EvolutionApiConfig::current();

        if ($config) {
            $this->server_url     = $config->server_url;
            $this->global_api_key = $config->getRawOriginal('global_api_key');
            $this->instance_name  = $config->instance_name;
            $this->instanceApiKey = $config->getRawOriginal('instance_api_key') ?? '';
            $this->groups_ignore  = $config->groups_ignore;
            $this->always_online  = $config->always_online;
            $this->read_messages  = $config->read_messages;
            $this->reject_call    = $config->reject_call;
            $this->msg_call       = $config->msg_call ?? '';
            $this->connectionStatus = $config->connection_status;
            $this->profileName    = $config->profile_name;
            $this->phoneNumber    = $config->phone_number;

            // Carrega info do webhook configurado no Evolution
            $this->loadWebhookInfo();
        } else {
            // Empresa nova sem config: pré-preenche URL do servidor e API key global
            // a partir de qualquer config existente, pois esses valores são do servidor
            // Evolution (compartilhados), não da instância por empresa.
            $existing = EvolutionApiConfig::withoutCompanyScope()->first();
            if ($existing) {
                $this->server_url     = $existing->server_url;
                $this->global_api_key = $existing->getRawOriginal('global_api_key');
            }
            // Sugere nome de instância baseado no slug da empresa pra evitar colisão
            $company = app(\App\Services\CurrentCompany::class)->model();
            if ($company) {
                $this->instance_name = $company->slug;
            }
        }
    }

    public function saveConfig(): void
    {
        $this->validate([
            'server_url'     => 'required|url',
            'global_api_key' => 'required|string',
            'instance_name'  => 'required|string|max:50',
        ]);

        $config = EvolutionApiConfig::current() ?? new EvolutionApiConfig();
        $config->fill([
            'server_url'     => rtrim($this->server_url, '/'),
            'global_api_key' => $this->global_api_key,
            'instance_name'  => $this->instance_name,
            'groups_ignore'  => $this->groups_ignore,
            'always_online'  => $this->always_online,
            'read_messages'  => $this->read_messages,
            'reject_call'    => $this->reject_call,
            'msg_call'       => $this->msg_call,
            'is_active'      => true,
        ]);
        $config->save();

        $this->dispatch('toast', type: 'success', message: 'Configuração salva com sucesso.');
    }

    public function testServer(): void
    {
        $this->loading = true;

        try {
            $svc    = $this->makeService();
            $result = $svc->fetchInstances();

            if ($result['success'] ?? false) {
                $count = is_array($result) ? count(array_filter(array_keys($result), 'is_int')) : 0;
                $this->dispatch('toast', type: 'success', message: 'Servidor online! Evolution API respondendo corretamente.');
            } else {
                $this->dispatch('toast', type: 'error', message: 'Falha ao conectar: ' . ($result['error'] ?? 'verifique a URL e a API Key'));
            }
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: 'Erro: ' . $e->getMessage());
        } finally {
            $this->loading = false;
        }
    }

    public function createInstance(): void
    {
        $this->loading = true;

        try {
            $svc    = $this->makeService();
            $result = $svc->createInstance([
                'instanceName' => $this->instance_name,
                'integration'  => 'WHATSAPP-BAILEYS',
                'qrcode'       => false,
                'groupsIgnore' => $this->groups_ignore,
                'alwaysOnline' => $this->always_online,
                'readMessages' => $this->read_messages,
                'rejectCall'   => $this->reject_call,
                'msgCall'      => $this->msg_call ?: '',
            ]);

            $created = false;

            if ($result['success'] ?? false) {
                // v2 retorna hash como string, v1 como objeto com apikey
                $key = is_string($result['hash'] ?? null)
                    ? $result['hash']
                    : ($result['hash']['apikey'] ?? null);
                $this->instanceApiKey = $key;
                $this->instanceExists = true;
                $created = true;

                // Persiste a instance key
                $config = EvolutionApiConfig::current();
                if ($config && $key) {
                    $config->update(['instance_api_key' => $key]);
                }

                $this->dispatch('toast', type: 'success', message: 'Instância criada com sucesso.');
            } else {
                // Monta mensagem de erro detalhada — Evolution devolve o detalhe em
                // response.message (array) ou error (string genérica como "Forbidden").
                $errorDetail = $result['error'] ?? '';
                $responseMsg = $result['response']['message'] ?? [];
                if (is_array($responseMsg)) {
                    $responseMsg = implode(' ', $responseMsg);
                }
                $fullError = trim($errorDetail . ' ' . $responseMsg);

                // Se já existe, considera ok — vamos configurar o webhook mesmo assim
                if (str_contains(strtolower($fullError), 'already') || str_contains(strtolower($fullError), 'already in use')) {
                    $this->instanceExists = true;
                    $created = true; // já existe, mas precisamos configurar webhook
                    $this->dispatch('toast', type: 'warning', message: 'Instância já existe no servidor. Configurando webhook...');
                } else {
                    $this->dispatch('toast', type: 'error', message: $fullError ?: 'Erro ao criar instância');
                }
            }

            // Configura o webhook automaticamente para que o QR code e status
            // cheguem via webhook (qrcode.updated, connection.update, messages.upsert).
            if ($created) {
                $this->setupWebhook();
            }
        } finally {
            $this->loading = false;
        }
    }

    public function connectInstance(): void
    {
        $this->loading    = true;
        $this->qrBase64   = null;
        $this->pairingCode = null;

        try {
            // Limpa QR anterior no banco
            $config = EvolutionApiConfig::current();
            if ($config) {
                $config->update(['qr_code' => null, 'pairing_code' => null, 'connection_status' => 'connecting']);
            }

            // Garante que o webhook está configurado antes de conectar, senão
            // o QR code que chega via webhook (qrcode.updated) não tem pra onde ir.
            $this->setupWebhook();

            $svc    = $this->makeService();
            $result = $svc->connectInstance();

            if ($result['success'] ?? false) {
                // v1: QR code retorna direto; v2: chega via webhook qrcode.updated
                $this->qrBase64    = $result['base64'] ?? null;
                $this->pairingCode = $result['pairingCode'] ?? null;

                // Persiste no banco também para que o pollQrCode() não sobrescreva
                // com null quando ler do DB antes do webhook chegar.
                if ($config && ($this->qrBase64 || $this->pairingCode)) {
                    $config->update([
                        'qr_code'      => $this->qrBase64,
                        'pairing_code' => $this->pairingCode,
                    ]);
                }

                if ($this->qrBase64) {
                    $this->dispatch('toast', type: 'success', message: 'QR Code gerado. Escaneie com o WhatsApp.');
                } else {
                    $this->dispatch('toast', type: 'success', message: 'Aguardando QR Code via webhook... (pode levar alguns segundos)');
                    $this->connectionStatus = 'connecting';
                }
            } else {
                $this->dispatch('toast', type: 'error', message: 'Erro ao conectar: ' . ($result['error'] ?? 'desconhecido'));
            }
        } finally {
            $this->loading = false;
        }
    }

    public function pollQrCode(): void
    {
        $config = EvolutionApiConfig::current();
        if (!$config) return;

        // Atualiza estado a partir do banco (webhook já pode ter gravado o QR)
        $this->connectionStatus = $config->connection_status;
        $this->qrBase64         = $config->getRawOriginal('qr_code');
        $this->pairingCode      = $config->pairing_code;

        if ($config->connection_status === 'open') {
            $this->profileName = $config->profile_name;
            $this->phoneNumber = $config->phone_number;
        }
    }

    public function checkStatus(): void
    {
        $this->loading = true;

        try {
            $svc    = $this->makeService();
            $result = $svc->connectionState();

            if ($result['success'] ?? false) {
                $state = $result['instance']['state'] ?? $result['state'] ?? 'unknown';
                $this->connectionStatus = $state;

                $config = EvolutionApiConfig::current();
                if ($config) {
                    $config->update(['connection_status' => $state]);
                }

                if ($state === 'open') {
                    $this->qrBase64    = null;
                    $this->pairingCode = null;
                    $this->refreshInstanceInfo();
                    $this->dispatch('toast', type: 'success', message: 'WhatsApp conectado!');
                } else {
                    $this->dispatch('toast', type: 'warning', message: 'Status: ' . $state);
                }
            } else {
                $this->dispatch('toast', type: 'error', message: 'Erro ao verificar status: ' . ($result['error'] ?? ''));
            }
        } finally {
            $this->loading = false;
        }
    }

    public function restartInstance(): void
    {
        $this->loading = true;

        try {
            $svc    = $this->makeService();
            $result = $svc->restartInstance();

            if ($result['success'] ?? false) {
                $this->dispatch('toast', type: 'success', message: 'Instância reiniciada.');
                $this->connectionStatus = 'connecting';
            } else {
                $this->dispatch('toast', type: 'error', message: $result['error'] ?? 'Erro ao reiniciar');
            }
        } finally {
            $this->loading = false;
        }
    }

    public function logoutInstance(): void
    {
        $this->loading = true;

        try {
            $svc    = $this->makeService();
            $result = $svc->logoutInstance();

            if ($result['success'] ?? false) {
                $this->connectionStatus = 'disconnected';
                $this->qrBase64         = null;
                $this->pairingCode      = null;

                $config = EvolutionApiConfig::current();
                if ($config) {
                    $config->update(['connection_status' => 'disconnected', 'phone_number' => null, 'profile_name' => null]);
                }

                $this->dispatch('toast', type: 'success', message: 'Desconectado do WhatsApp.');
            } else {
                $this->dispatch('toast', type: 'error', message: $result['error'] ?? 'Erro ao desconectar');
            }
        } finally {
            $this->loading = false;
        }
    }

    public function saveSettings(): void
    {
        $this->loading = true;

        try {
            $svc    = $this->makeService();
            $result = $svc->setSettings([
                'reject_call'       => $this->reject_call,
                'msg_call'          => $this->msg_call ?: '',
                'groups_ignore'     => $this->groups_ignore,
                'always_online'     => $this->always_online,
                'read_messages'     => $this->read_messages,
                'read_status'       => false,
                'sync_full_history' => false,
                'wavoipToken'       => '',
            ]);

            // Salva também no banco
            $config = EvolutionApiConfig::current();
            if ($config) {
                $config->update([
                    'groups_ignore' => $this->groups_ignore,
                    'always_online' => $this->always_online,
                    'read_messages' => $this->read_messages,
                    'reject_call'   => $this->reject_call,
                    'msg_call'      => $this->msg_call,
                ]);
            }

            $this->dispatch('toast', type: 'success', message: 'Configurações salvas na instância.');
        } finally {
            $this->loading = false;
        }
    }

    public function setupWebhook(): void
    {
        $this->loading = true;

        try {
            $webhookUrl = rtrim(config('app.url'), '/') . '/api/webhook/evolution';
            $svc        = $this->makeService();
            $result     = $svc->setWebhook($webhookUrl, [], false, true); // base64=true: mídia chega em base64 para armazenamento local

            if ($result['success'] ?? false) {
                $this->dispatch('toast', type: 'success', message: 'Webhook configurado: ' . $webhookUrl);
                $this->loadWebhookInfo();
            } else {
                $this->dispatch('toast', type: 'error', message: 'Erro ao configurar webhook: ' . ($result['error'] ?? ''));
            }
        } finally {
            $this->loading = false;
        }
    }

    public function loadWebhookInfo(): void
    {
        try {
            $svc    = $this->makeService();
            $result = $svc->getWebhook();

            if ($result['success'] ?? false) {
                $this->webhookInfo = $result;
            }
        } catch (\Throwable) {}
    }

    private function refreshInstanceInfo(): void
    {
        try {
            $svc     = $this->makeService();
            $result  = $svc->fetchInstances($this->instance_name);
            $instances = $result[0] ?? $result['instance'] ?? null;

            if ($instances) {
                $profile = is_array($instances) ? ($instances['profileName'] ?? null) : null;
                $phone   = is_array($instances) ? ($instances['owner'] ?? null) : null;

                if ($profile || $phone) {
                    $this->profileName = $profile;
                    $this->phoneNumber = $phone ? preg_replace('/\D/', '', $phone) : null;

                    $config = EvolutionApiConfig::current();
                    if ($config) {
                        $config->update([
                            'profile_name' => $this->profileName,
                            'phone_number' => $this->phoneNumber,
                        ]);
                    }
                }
            }
        } catch (\Throwable) {}
    }

    private function makeService(): EvolutionApiService
    {
        // Sempre busca do banco para garantir chaves atualizadas
        $config = EvolutionApiConfig::current() ?? new EvolutionApiConfig();

        // Sobrepõe com os valores do form (que podem ter sido editados mas não salvos)
        $config->server_url      = rtrim($this->server_url, '/');
        $config->global_api_key  = $this->global_api_key;
        $config->instance_name   = $this->instance_name;

        // Só usa a chave do form se foi preenchida; senão usa a do banco
        if ($this->instanceApiKey) {
            $config->instance_api_key = $this->instanceApiKey;
        }

        // Sincroniza o estado local com o banco
        $this->instanceApiKey = $config->getRawOriginal('instance_api_key') ?: $this->instanceApiKey;

        return new EvolutionApiService($config);
    }

    public function render()
    {
        return view('livewire.admin.evolution-api-manager');
    }
}
