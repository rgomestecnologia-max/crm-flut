<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetaWhatsAppConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaSignupCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $code = $request->query('code');

        if (!$code) {
            return redirect()->route('admin.meta-whatsapp.index')
                ->with('error', 'Nenhum código recebido do Meta.');
        }

        $appId     = config('services.meta.app_id');
        $appSecret = config('services.meta.app_secret');

        if (!$appId || !$appSecret) {
            return redirect()->route('admin.meta-whatsapp.index')
                ->with('error', 'META_APP_ID ou META_APP_SECRET não configurados.');
        }

        try {
            // 1. Trocar code por short-lived token
            $tokenResponse = Http::get('https://graph.facebook.com/v21.0/oauth/access_token', [
                'client_id'     => $appId,
                'client_secret' => $appSecret,
                'redirect_uri'  => route('admin.meta-whatsapp.callback'),
                'code'          => $code,
            ]);

            if (!$tokenResponse->successful()) {
                $error = $tokenResponse->json()['error']['message'] ?? 'Erro ao trocar code por token';
                Log::error('MetaSignupCallback: token exchange failed', ['response' => $tokenResponse->body()]);
                return redirect()->route('admin.meta-whatsapp.index')
                    ->with('error', $error);
            }

            $shortToken = $tokenResponse->json()['access_token'];

            // 2. Trocar por long-lived token (60 dias)
            $longResponse = Http::get('https://graph.facebook.com/v21.0/oauth/access_token', [
                'grant_type'        => 'fb_exchange_token',
                'client_id'         => $appId,
                'client_secret'     => $appSecret,
                'fb_exchange_token' => $shortToken,
            ]);

            $accessToken = $longResponse->successful()
                ? ($longResponse->json()['access_token'] ?? $shortToken)
                : $shortToken;

            // 3. Buscar WABA ID via debug_token
            $wabaId        = null;
            $phoneNumberId = null;
            $phoneDisplay  = null;

            $debugResponse = Http::withToken($accessToken)
                ->get('https://graph.facebook.com/v21.0/debug_token', [
                    'input_token' => $accessToken,
                ]);

            $granularScopes = $debugResponse->json()['data']['granular_scopes'] ?? [];
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

            // 5. Salvar configuração
            $companyId = app(\App\Services\CurrentCompany::class)->id();

            $config = MetaWhatsAppConfig::updateOrCreate(
                ['company_id' => $companyId],
                [
                    'access_token'                 => $accessToken,
                    'whatsapp_business_account_id' => $wabaId,
                    'phone_number_id'              => $phoneNumberId,
                    'phone_display'                => $phoneDisplay,
                    'verify_token'                 => Str::random(32),
                    'is_active'                    => true,
                ]
            );

            // 6. Inscrever app no WABA para receber webhooks
            if ($wabaId) {
                Http::withToken($accessToken)
                    ->post("https://graph.facebook.com/v21.0/{$wabaId}/subscribed_apps");
            }

            // 7. Alterar provider para meta
            $company = app(\App\Services\CurrentCompany::class)->model();
            $company?->update(['whatsapp_provider' => 'meta']);

            Log::info('MetaSignupCallback: sucesso', [
                'waba_id'         => $wabaId,
                'phone_number_id' => $phoneNumberId,
                'phone_display'   => $phoneDisplay,
            ]);

            return redirect()->route('admin.meta-whatsapp.index')
                ->with('success', 'WhatsApp conectado com sucesso!' . ($phoneDisplay ? " ({$phoneDisplay})" : ''));

        } catch (\Exception $e) {
            Log::error('MetaSignupCallback: erro', ['error' => $e->getMessage()]);
            return redirect()->route('admin.meta-whatsapp.index')
                ->with('error', 'Erro: ' . $e->getMessage());
        }
    }
}
