<?php

namespace App\Http\Middleware;

use App\Models\ZapiConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateZapiWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $request->header('X-Webhook-Token') ?? $request->query('token');

        // Procura uma config Z-API ATIVA cujo webhook_secret bata com o token recebido.
        // Bypass do global scope porque webhook não tem sessão de empresa setada.
        // Se nenhuma config bate, rejeita.
        $configsHaveSecret = ZapiConfig::withoutCompanyScope()
            ->whereNotNull('webhook_secret')
            ->where('is_active', true)
            ->exists();

        // Se nenhuma config tem secret configurado em todo o sistema, mantém comportamento legado: aceita.
        if (!$configsHaveSecret) {
            return $next($request);
        }

        if (!$secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $match = ZapiConfig::withoutCompanyScope()
            ->where('is_active', true)
            ->where('webhook_secret', $secret)
            ->exists();

        if (!$match) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
