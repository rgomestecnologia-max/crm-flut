<?php

use App\Http\Middleware\EnsureCurrentCompany;
use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\EnsureIsManager;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\ValidateApiToken;
use App\Http\Middleware\ValidateZapiWebhook;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin'          => EnsureIsAdmin::class,
            'manager'        => EnsureIsManager::class,
            'vendedor'       => \App\Http\Middleware\EnsureIsVendedor::class,
            'company'        => EnsureCurrentCompany::class,
            'module'         => EnsureModuleEnabled::class,
            'zapi.webhook'   => ValidateZapiWebhook::class,
            'api.token'      => ValidateApiToken::class,
        ]);

        // Excluir CSRF para callbacks externos (Meta data deletion)
        $middleware->validateCsrfTokens(except: [
            '/data-deletion',
        ]);

        // CORS: permite requisições de origens externas para /api/*
        $middleware->api(prepend: [\Illuminate\Http\Middleware\HandleCors::class]);

        // Permite hosts externos (ngrok, localtunnel) — necessário para testes locais
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
