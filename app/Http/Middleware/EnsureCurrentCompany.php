<?php

namespace App\Http\Middleware;

use App\Services\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que toda request autenticada tem uma empresa ativa.
 *
 * Comportamento:
 *  - Agente/supervisor: a empresa do user é setada automaticamente (sem precisar escolher).
 *  - Admin: se não houver empresa na sessão, redireciona para /select-company.
 *  - Em ambos os casos, deixa o `CurrentCompany` populado para o resto da request.
 */
class EnsureCurrentCompany
{
    public function __construct(protected CurrentCompany $currentCompany) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Heartbeat: atualiza last_seen_at (no máximo 1x por minuto)
        $user->touchLastSeen();

        // Agente/supervisor: empresa fixa, vinda do próprio user.
        if (!$user->isAdmin()) {
            if ($user->company_id) {
                $this->currentCompany->set((int) $user->company_id);
            } else {
                // Agente sem empresa atribuída — bloqueia acesso (admin precisa configurar).
                abort(403, 'Sua conta ainda não está vinculada a uma empresa. Contate o administrador.');
            }
            return $next($request);
        }

        // Admin: precisa ter escolhido uma empresa.
        if (!$this->currentCompany->check()) {
            // Permite a própria tela de seleção e o logout sem loop.
            if ($request->routeIs('companies.select') || $request->routeIs('companies.enter') || $request->routeIs('logout')) {
                return $next($request);
            }
            return redirect()->route('companies.select');
        }

        return $next($request);
    }
}
