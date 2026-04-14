<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Services\CurrentCompany;
use Closure;
use Illuminate\Http\Request;

class ValidateApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization', '');
        $plain  = str_starts_with($header, 'Bearer ') ? substr($header, 7) : $request->query('api_token');

        if (!$plain) {
            return response()->json(['error' => 'Token de autenticação ausente.'], 401);
        }

        $token = ApiToken::findByPlain($plain);

        if (!$token) {
            return response()->json(['error' => 'Token inválido ou inativo.'], 401);
        }

        // Define a empresa do token como tenant ativo durante o restante da request.
        // Sem isso, qualquer model com BelongsToCompany retorna vazio (where 1=0).
        if ($token->company_id) {
            app(CurrentCompany::class)->set((int) $token->company_id, persist: false);
        }

        $token->update(['last_used_at' => now()]);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
