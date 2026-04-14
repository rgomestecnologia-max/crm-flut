<?php

use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Webhook\ZapiWebhookController;
use App\Http\Controllers\Webhook\EvolutionWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/zapi', [ZapiWebhookController::class, 'handle'])
    ->middleware('zapi.webhook')
    ->name('webhook.zapi');

Route::post('/webhook/evolution', [EvolutionWebhookController::class, 'handle'])
    ->name('webhook.evolution');

// ── API de Integração CRM ─────────────────────────────────────────────
Route::middleware('api.token')->group(function () {
    Route::post('/leads', [LeadController::class, 'store'])->name('api.leads.store');
});
