<?php

use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Webhook\EvolutionWebhookController;
use App\Http\Controllers\Webhook\MetaWebhookController;
use App\Http\Controllers\Webhook\SendGridWebhookController;
use App\Http\Controllers\Webhook\ZapiWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/evolution', [EvolutionWebhookController::class, 'handle'])
    ->middleware('throttle:500,1')
    ->name('webhook.evolution');

Route::get('/webhook/meta', [MetaWebhookController::class, 'verify'])
    ->name('webhook.meta.verify');
Route::post('/webhook/meta', [MetaWebhookController::class, 'handle'])
    ->middleware('throttle:500,1')
    ->name('webhook.meta');

Route::post('/webhook/zapi', [ZapiWebhookController::class, 'handle'])
    ->middleware('throttle:500,1')
    ->name('webhook.zapi');

Route::post('/webhook/sendgrid', [SendGridWebhookController::class, 'handle'])
    ->middleware('throttle:500,1')
    ->name('webhook.sendgrid');

// ── Flut Chat Widget (público) ────────────────────────────────────────
Route::prefix('flut-chat')->middleware('throttle:60,1')->group(function () {
    Route::get('/{publicId}/config', [\App\Http\Controllers\Api\FlutChatController::class, 'config']);
    Route::post('/{publicId}/lead', [\App\Http\Controllers\Api\FlutChatController::class, 'saveLead']);
    Route::post('/{publicId}/ai', [\App\Http\Controllers\Api\FlutChatController::class, 'aiChat']);
    Route::post('/{publicId}/conversation', [\App\Http\Controllers\Api\FlutChatController::class, 'startConversation']);
    Route::post('/{publicId}/conversation/{conversationId}/message', [\App\Http\Controllers\Api\FlutChatController::class, 'sendMessage']);
    Route::get('/{publicId}/conversation/{conversationId}/messages', [\App\Http\Controllers\Api\FlutChatController::class, 'getMessages']);
    Route::get('/{publicId}/download/{messageId}', [\App\Http\Controllers\Api\FlutChatController::class, 'downloadMedia']);
});

// ── Landing Pages (público) ───────────────────────────────────────────
Route::post('/lp/{pageId}/lead', [\App\Http\Controllers\Api\LandingPageController::class, 'saveLead'])->middleware('throttle:30,1');

// ── Link in Bio: tracking de clicks ──────────────────────────────────
Route::post('/bio/click/{linkId}', [\App\Http\Controllers\LinkInBioViewController::class, 'trackClick']);

// ── Proxy de imagens (evitar CORS do R2 no PDF) ──────────────────────
Route::get('/proxy-image', function (\Illuminate\Http\Request $request) {
    $url = $request->query('url');
    if (!$url || !str_contains($url, 'r2.dev')) abort(400);
    $content = @file_get_contents($url);
    if (!$content) abort(404);
    $mime = 'image/png';
    if (str_ends_with($url, '.jpg') || str_ends_with($url, '.jpeg')) $mime = 'image/jpeg';
    return response($content)->header('Content-Type', $mime)->header('Access-Control-Allow-Origin', '*')->header('Cache-Control', 'public, max-age=3600');
})->middleware('throttle:60,1');

// ── API de Integração CRM ─────────────────────────────────────────────
Route::middleware(['api.token', 'throttle:100,1'])->group(function () {
    Route::post('/leads', [LeadController::class, 'store'])->name('api.leads.store');
});
