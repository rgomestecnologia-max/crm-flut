<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite acesso a admin E supervisor.
 * Usado nas rotas de gestão da empresa (departamentos, agentes, pipelines, chatbot, IA, automação).
 * Para rotas exclusivas do admin do sistema (empresas, config globais, evolution, z-api),
 * continua usando o middleware 'admin'.
 */
class EnsureIsManager
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->canManageCompany()) {
            abort(403, 'Acesso restrito a administradores e supervisores.');
        }
        return $next($request);
    }
}
