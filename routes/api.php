<?php

use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Webhook\EvolutionWebhookController;
use App\Http\Controllers\Webhook\MetaWebhookController;
use App\Http\Controllers\Webhook\SendGridWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/evolution', [EvolutionWebhookController::class, 'handle'])
    ->middleware('throttle:500,1')
    ->name('webhook.evolution');

Route::get('/webhook/meta', [MetaWebhookController::class, 'verify'])
    ->name('webhook.meta.verify');
Route::post('/webhook/meta', [MetaWebhookController::class, 'handle'])
    ->middleware('throttle:500,1')
    ->name('webhook.meta');

Route::post('/webhook/sendgrid', [SendGridWebhookController::class, 'handle'])
    ->middleware('throttle:500,1')
    ->name('webhook.sendgrid');

// ── Flut Chat Widget (público) ────────────────────────────────────────
Route::prefix('flut-chat')->middleware('throttle:60,1')->group(function () {
    Route::get('/{publicId}/config', [\App\Http\Controllers\Api\FlutChatController::class, 'config']);
    Route::post('/{publicId}/lead', [\App\Http\Controllers\Api\FlutChatController::class, 'saveLead']);
    Route::post('/{publicId}/ai', [\App\Http\Controllers\Api\FlutChatController::class, 'aiChat']);
});

// ── API de Integração CRM ─────────────────────────────────────────────
Route::middleware(['api.token', 'throttle:100,1'])->group(function () {
    Route::post('/leads', [LeadController::class, 'store'])->name('api.leads.store');
});
