<?php

namespace App\Http\Middleware;

use App\Services\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica se o módulo está habilitado para a empresa atual.
 * Admin do sistema bypassa (vê tudo sempre).
 *
 * Uso: Route::get(...)->middleware('module:chat')
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        // Admin do sistema sempre tem acesso
        if ($user?->isAdmin()) {
            return $next($request);
        }

        $company = app(CurrentCompany::class)->model();

        if (!$company || !$company->hasModule($module)) {
            abort(403, 'Este módulo não está habilitado para sua empresa.');
        }

        return $next($request);
    }
}
