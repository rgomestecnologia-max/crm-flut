<?php

namespace App\Livewire\Admin;

use App\Models\Company;
use App\Models\MetaMessageTemplate;
use App\Models\MetaWhatsAppConfig;
use App\Services\MetaWhatsAppService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class MetaWhatsAppManager extends Component
{
    public string $phone_number_id              = '';
    public string $whatsapp_business_account_id = '';
    public string $access_token                 = '';  // Só para input — não carrega do banco
    public bool   $hasAccessToken               = false;
    public string $verify_token                 = '';
    public string $phone_display                = '';
    public bool   $is_active                    = false;
    public string $whatsapp_provider            = 'evolution';

    public string $testResult    = '';
    public string $testStatus    = '';
    public string $webhookUrl    = '';
    public ?string $metaAppId    = '';

    public function mount(): void
    {
        $config = MetaWhatsAppConfig::current();

        if ($config) {
            $this->phone_number_id              = $config->phone_number_id ?? '';
            $this->whatsapp_business_account_id = $config->whatsapp_business_account_id ?? '';
            $this->access_token                 = '';  // Nunca carrega no Livewire (evita erro de encriptação)
            $this->hasAccessToken               = !empty($config->getRawOriginal('access_token'));
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
        $this->metaAppId  = config('services.meta.app_id') ?? '';
    }

    public function save(): void
    {
        $rules = [
            'phone_number_id'              => 'required|string|max:50',
            'whatsapp_business_account_id' => 'nullable|string|max:50',
            'verify_token'                 => 'required|string|max:100',
            'phone_display'                => 'nullable|string|max:20',
        ];
        // Token obrigatório só se não tem um salvo
        if (!$this->hasAccessToken) {
            $rules['access_token'] = 'required|string';
        }
        $this->validate($rules);

        $data = [
            'phone_number_id'              => $this->phone_number_id,
            'whatsapp_business_account_id' => $this->whatsapp_business_account_id,
            'verify_token'                 => $this->verify_token,
            'phone_display'                => $this->phone_display ?: null,
            'is_active'                    => $this->is_active,
        ];
        // Só atualiza token se foi preenchido (novo token)
        if ($this->access_token) {
            $data['access_token'] = $this->access_token;
            $this->hasAccessToken = true;
            $this->access_token   = ''; // Limpa da property
        }

        MetaWhatsAppConfig::updateOrCreate(
            ['company_id' => app(\App\Services\CurrentCompany::class)->id()],
            $data
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

        $token = $this->access_token ?: MetaWhatsAppConfig::current()?->getRawOriginal('access_token');
        if (!$this->phone_number_id || !$token) {
            $this->testResult = 'Preencha Phone Number ID e Access Token primeiro.';
            $this->testStatus = 'error';
            return;
        }

        try {
            $response = Http::withToken($token)
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

    /**
     * Processa o resultado do Embedded Signup (Facebook Login).
     * Recebe o code do OAuth, troca por token permanente, e busca phone_number_id + WABA ID.
     */
    public function processEmbeddedSignup(string $code): void
    {
        try {
            $appId     = config('services.meta.app_id');
            $appSecret = config('services.meta.app_secret');

            if (!$appId || !$appSecret) {
                $this->dispatch('toast', type: 'error', message: 'META_APP_ID e META_APP_SECRET não configurados no servidor.');
                return;
            }

            // 1. Trocar code por short-lived token
            $tokenResponse = Http::get('https://graph.facebook.com/v21.0/oauth/access_token', [
                'client_id'     => $appId,
                'client_secret' => $appSecret,
                'code'          => $code,
            ]);

            if (!$tokenResponse->successful()) {
                $error = $tokenResponse->json()['error']['message'] ?? 'Erro ao trocar code por token';
                $this->dispatch('toast', type: 'error', message: $error);
                Log::error('Embedded Signup: token exchange failed', ['response' => $tokenResponse->body()]);
                return;
            }

            $shortToken = $tokenResponse->json()['access_token'];

            // 2. Trocar short-lived por long-lived token (60 dias)
            // Para System User tokens, o short-lived já pode ser permanente
            // Mas tentamos trocar por segurança
            $longResponse = Http::get('https://graph.facebook.com/v21.0/oauth/access_token', [
                'grant_type'    => 'fb_exchange_token',
                'client_id'     => $appId,
                'client_secret' => $appSecret,
                'fb_exchange_token' => $shortToken,
            ]);

            $accessToken = $longResponse->successful()
                ? ($longResponse->json()['access_token'] ?? $shortToken)
                : $shortToken;

            // 3. Buscar WABA ID compartilhado via debug_token ou business endpoints
            $wabaId = null;
            $phoneNumberId = null;
            $phoneDisplay = null;

            // Buscar WABAs compartilhados com o app
            $sharedResponse = Http::withToken($accessToken)
                ->get('https://graph.facebook.com/v21.0/debug_token', [
                    'input_token' => $accessToken,
                ]);

            $granularScopes = $sharedResponse->json()['data']['granular_scopes'] ?? [];
            foreach ($granularScopes as $scope) {
                if ($scope['permission'] === 'whatsapp_business_messaging' && !empty($scope['target_ids'])) {
                    $wabaId = $scope['target_ids'][0];
                    break;
                }
                if ($scope['permission'] === 'whatsapp_business_management' && !empty($scope['target_ids']) && !$wabaId) {
                    $wabaId = $scope['target_ids'][0];
                }
            }

            // 4. Buscar phone numbers do WABA
            if ($wabaId) {
                $phonesResponse = Http::withToken($accessToken)
                    ->get("https://graph.facebook.com/v21.0/{$wabaId}/phone_numbers");

                if ($phonesResponse->successful()) {
                    $phones = $phonesResponse->json()['data'] ?? [];
                    if (!empty($phones[0])) {
                        $phoneNumberId = $phones[0]['id'];
                        $phoneDisplay  = $phones[0]['display_phone_number'] ?? null;
                    }
                }
            }

            // 5. Preencher os campos
            $this->access_token = $accessToken;
            if ($wabaId) $this->whatsapp_business_account_id = $wabaId;
            if ($phoneNumberId) $this->phone_number_id = $phoneNumberId;
            if ($phoneDisplay) $this->phone_display = $phoneDisplay;

            if (!$this->verify_token) {
                $this->verify_token = Str::random(32);
            }

            // 6. Salvar automaticamente
            $this->save();

            // 7. Inscrever app no WABA para receber webhooks
            if ($wabaId) {
                Http::withToken($accessToken)->post("https://graph.facebook.com/v21.0/{$wabaId}/subscribed_apps");
            }

            // 8. Alternar provider para meta
            $this->switchProvider('meta');

            $this->dispatch('toast', type: 'success', message: 'WhatsApp conectado com sucesso!' . ($phoneDisplay ? " ({$phoneDisplay})" : ''));

            Log::info('Embedded Signup: sucesso', [
                'waba_id' => $wabaId,
                'phone_number_id' => $phoneNumberId,
                'phone_display' => $phoneDisplay,
            ]);

        } catch (\Exception $e) {
            Log::error('Embedded Signup: erro', ['error' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Erro no Embedded Signup: ' . $e->getMessage());
        }
    }

    /**
     * Fallback: processa quando o FB.login retorna accessToken direto (sem code).
     */
    public function processEmbeddedSignupToken(string $accessToken): void
    {
        try {
            $appId     = config('services.meta.app_id');
            $appSecret = config('services.meta.app_secret');

            // Tentar trocar por long-lived token
            if ($appId && $appSecret) {
                $longResponse = Http::get('https://graph.facebook.com/v21.0/oauth/access_token', [
                    'grant_type'        => 'fb_exchange_token',
                    'client_id'         => $appId,
                    'client_secret'     => $appSecret,
                    'fb_exchange_token' => $accessToken,
                ]);

                if ($longResponse->successful() && ($longResponse->json()['access_token'] ?? null)) {
                    $accessToken = $longResponse->json()['access_token'];
                }
            }

            // Reutilizar a lógica de buscar WABA + phone do processEmbeddedSignup
            $wabaId = null;
            $phoneNumberId = null;
            $phoneDisplay = null;

            $sharedResponse = Http::withToken($accessToken)
                ->get('https://graph.facebook.com/v21.0/debug_token', [
                    'input_token' => $accessToken,
                ]);

            $granularScopes = $sharedResponse->json()['data']['granular_scopes'] ?? [];
            foreach ($granularScopes as $scope) {
                if ($scope['permission'] === 'whatsapp_business_messaging' && !empty($scope['target_ids'])) {
                    $wabaId = $scope['target_ids'][0];
                    break;
                }
                if ($scope['permission'] === 'whatsapp_business_management' && !empty($scope['target_ids']) && !$wabaId) {
                    $wabaId = $scope['target_ids'][0];
                }
            }

            if ($wabaId) {
                $phonesResponse = Http::withToken($accessToken)
                    ->get("https://graph.facebook.com/v21.0/{$wabaId}/phone_numbers");

                if ($phonesResponse->successful()) {
                    $phones = $phonesResponse->json()['data'] ?? [];
                    if (!empty($phones[0])) {
                        $phoneNumberId = $phones[0]['id'];
                        $phoneDisplay  = $phones[0]['display_phone_number'] ?? null;
                    }
                }
            }

            $this->access_token = $accessToken;
            if ($wabaId) $this->whatsapp_business_account_id = $wabaId;
            if ($phoneNumberId) $this->phone_number_id = $phoneNumberId;
            if ($phoneDisplay) $this->phone_display = $phoneDisplay;

            if (!$this->verify_token) {
                $this->verify_token = Str::random(32);
            }

            $this->save();

            if ($wabaId) {
                Http::withToken($accessToken)->post("https://graph.facebook.com/v21.0/{$wabaId}/subscribed_apps");
            }

            $this->switchProvider('meta');

            $this->dispatch('toast', type: 'success', message: 'WhatsApp conectado com sucesso!' . ($phoneDisplay ? " ({$phoneDisplay})" : ''));

            Log::info('Embedded Signup (token): sucesso', [
                'waba_id' => $wabaId,
                'phone_number_id' => $phoneNumberId,
                'phone_display' => $phoneDisplay,
            ]);

        } catch (\Exception $e) {
            Log::error('Embedded Signup (token): erro', ['error' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Erro: ' . $e->getMessage());
        }
    }

    public function syncTemplates(): void
    {
        if (!$this->whatsapp_business_account_id || !$this->access_token) {
            $this->dispatch('toast', type: 'error', message: 'Preencha o WABA ID e Access Token primeiro.');
            return;
        }

        $config = MetaWhatsAppConfig::current();
        if (!$config) {
            $this->dispatch('toast', type: 'error', message: 'Salve a configuração primeiro.');
            return;
        }

        $service = new MetaWhatsAppService($config);
        $result = $service->fetchTemplates($this->whatsapp_business_account_id);

        if (!($result['success'] ?? false)) {
            $this->dispatch('toast', type: 'error', message: 'Erro ao buscar templates: ' . ($result['error'] ?? 'desconhecido'));
            return;
        }

        $companyId = app(\App\Services\CurrentCompany::class)->id();
        $synced = 0;

        foreach ($result['data'] as $tpl) {
            MetaMessageTemplate::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'name'       => $tpl['name'],
                    'language'   => $tpl['language'],
                ],
                [
                    'template_id' => $tpl['id'] ?? null,
                    'category'    => $tpl['category'] ?? null,
                    'status'      => $tpl['status'] ?? 'UNKNOWN',
                    'components'  => $tpl['components'] ?? [],
                ]
            );
            $synced++;
        }

        $this->dispatch('toast', type: 'success', message: "{$synced} templates sincronizados.");
    }

    public function render()
    {
        $templates = MetaMessageTemplate::approved()
            ->orderBy('name')
            ->get();

        return view('livewire.admin.meta-whatsapp-manager', compact('templates'));
    }
}
