<?php

use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Webhook\EvolutionWebhookController;
use App\Http\Controllers\Webhook\MetaWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/evolution', [EvolutionWebhookController::class, 'handle'])
    ->middleware('throttle:500,1')
    ->name('webhook.evolution');

Route::get('/webhook/meta', [MetaWebhookController::class, 'verify'])
    ->name('webhook.meta.verify');
Route::post('/webhook/meta', [MetaWebhookController::class, 'handle'])
    ->middleware('throttle:500,1')
    ->name('webhook.meta');

// ── API de Integração CRM ─────────────────────────────────────────────
Route::middleware(['api.token', 'throttle:100,1'])->group(function () {
    Route::post('/leads', [LeadController::class, 'store'])->name('api.leads.store');
});
