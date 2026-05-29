<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsVendedor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || (!$user->isAdmin() && !$user->isVendedor())) {
            abort(403, 'Acesso restrito.');
        }
        return $next($request);
    }
}
