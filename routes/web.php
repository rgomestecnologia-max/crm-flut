<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\UnsubscribeController;
use App\Livewire\Auth\SelectCompany;
use Illuminate\Support\Facades\Route;

// Pricing simulator (público, sem auth)
Route::get('/pricing', [PricingController::class, 'show'])->name('pricing');
Route::post('/pricing/save', [PricingController::class, 'save'])->name('pricing.save');
Route::get('/pricing/{token}/editar', [PricingController::class, 'edit'])->name('pricing.edit');
Route::put('/pricing/{token}', [PricingController::class, 'update'])->name('pricing.update');

// Legal (público, sem auth — Meta App Verification)
Route::get('/privacy', fn() => view('legal.privacy'))->name('privacy');
Route::get('/terms', fn() => view('legal.terms'))->name('terms');
Route::get('/data-deletion', fn() => view('legal.data-deletion'))->name('data-deletion');

// Meta Data Deletion Callback (POST recebido automaticamente pelo Meta)
Route::post('/data-deletion', function (\Illuminate\Http\Request $request) {
    $signedRequest = $request->input('signed_request');
    $appSecret = config('services.meta.app_secret');
    $userId = null;

    if ($signedRequest && $appSecret) {
        $parts = explode('.', $signedRequest, 2);
        if (count($parts) === 2) {
            [$encodedSig, $payload] = $parts;
            $sig = base64_decode(strtr($encodedSig, '-_', '+/'));
            $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
            $expectedSig = hash_hmac('sha256', $payload, $appSecret, true);

            if (hash_equals($sig, $expectedSig) && $data) {
                $userId = $data['user_id'] ?? null;
            }
        }
    }

    $confirmationCode = 'DEL-' . strtoupper(bin2hex(random_bytes(8)));

    \Illuminate\Support\Facades\Log::info('Meta data deletion request', [
        'user_id' => $userId,
        'confirmation_code' => $confirmationCode,
        'signature_valid' => $userId !== null,
    ]);

    return response()->json([
        'url' => url('/data-deletion') . '?code=' . $confirmationCode,
        'confirmation_code' => $confirmationCode,
    ]);
})->name('data-deletion.callback');

// Onboarding (público, sem auth)
Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
Route::post('/onboarding', [OnboardingController::class, 'submit']);

// Unsubscribe (público, sem auth)
Route::get('/unsubscribe/{token}', [UnsubscribeController::class, 'show'])->name('unsubscribe');
Route::post('/unsubscribe/{token}', [UnsubscribeController::class, 'process']);

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Tela de seleção de empresa (admin) — fica dentro de auth mas FORA do middleware 'company'
// porque é justamente onde a empresa é escolhida.
Route::middleware('auth')->group(function () {
    // Push subscriptions
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribe']);
    Route::delete('/push/unsubscribe', [PushSubscriptionController::class, 'unsubscribe']);

    Route::get('/select-company', SelectCompany::class)->name('companies.select');
    Route::post('/companies/clear', function () {
        app(\App\Services\CurrentCompany::class)->clear();
        return redirect()->route('companies.select');
    })->name('companies.clear');

    Route::post('/companies/{company}/enter', function (\App\Models\Company $company) {
        if (!$company->is_active) abort(404);
        app(\App\Services\CurrentCompany::class)->set($company);
        return redirect()->route('dashboard');
    })->name('companies.enter');
});

// App — agora exige uma empresa atual via middleware 'company'
Route::middleware(['auth', 'company'])->group(function () {
    // Dashboard é sempre acessível — é a página de entrada após login.
    // O módulo "dashboard" controla apenas se o link aparece na sidebar.
    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Chat
    Route::middleware('module:chat')->group(function () {
        Route::get('/chat', ChatController::class)->name('chat');
        Route::get('/chat/{conversation}', ChatController::class)->name('chat.show');
    });

    // CRM
    Route::get('/crm', fn() => view('crm.index'))->middleware('module:crm')->name('crm.index');

    // Leads
    Route::get('/leads', fn() => view('leads.index'))->middleware('module:leads')->name('leads.index');

    // Disparos (Broadcasts)
    Route::get('/broadcasts', fn() => view('broadcasts.index'))->middleware('module:broadcasts')->name('broadcasts.index');

    // Admin — somente admin do sistema (empresas, config globais, integrações WhatsApp)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('companies', fn() => view('admin.companies.index'))->name('companies.index');
        Route::get('global-settings', fn() => view('admin.global-settings.index'))->name('global-settings.index');
        Route::get('evolution', fn() => view('admin.evolution.index'))->name('evolution.index');
        Route::get('meta-whatsapp', fn() => view('admin.meta-whatsapp.index'))->name('meta-whatsapp.index');
        Route::get('meta-whatsapp/callback', [\App\Http\Controllers\Admin\MetaSignupCallbackController::class, 'handle'])->name('meta-whatsapp.callback');
        Route::get('onboardings', fn() => view('admin.onboardings.index'))->name('onboardings.index');
        Route::get('pricing', fn() => view('admin.pricing.index'))->name('pricing.index');
        Route::get('proposals', fn() => view('admin.proposals.index'))->name('proposals.index');
        Route::get('templates', fn() => view('admin.templates.index'))->name('templates.index');
    });

    // Download de mídia (proxy para evitar CORS em URLs externas)
    Route::get('/media/download/{messageId}', function (int $messageId) {
        $msg = \App\Models\Message::findOrFail($messageId);
        if (!$msg->media_url) abort(404);

        $filename = $msg->media_filename ?? basename(parse_url($msg->media_url, PHP_URL_PATH));
        $content  = file_get_contents($msg->media_url);
        if (!$content) abort(404);

        $mime = match(true) {
            str_ends_with($filename, '.pdf')  => 'application/pdf',
            str_ends_with($filename, '.doc')  => 'application/msword',
            str_ends_with($filename, '.docx') => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            str_ends_with($filename, '.xls')  => 'application/vnd.ms-excel',
            str_ends_with($filename, '.xlsx') => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };

        return response($content)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    })->name('media.download');

    // Exportação CRM
    Route::get('/crm/export', function (\Illuminate\Http\Request $request) {
        $pipelineId = $request->query('pipeline_id');
        $dateFrom   = $request->query('date_from');
        $dateTo     = $request->query('date_to');
        $filename   = 'crm-cards-' . now()->format('Y-m-d-His') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CrmCardsExport($pipelineId, $dateFrom, $dateTo),
            $filename
        );
    })->middleware('module:crm')->name('crm.export');

    // Manager — admin + supervisor, protegido por módulo
    Route::middleware('manager')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('departments', DepartmentController::class)->except('show', 'create', 'edit')->middleware('module:admin.departments');
        Route::resource('agents', AgentController::class)->except('show', 'create', 'edit')->middleware('module:admin.agents');
        Route::get('crm', fn() => view('admin.crm.index'))->name('crm.index')->middleware('module:admin.crm');
        Route::get('chatbot', fn() => view('admin.chatbot.index'))->name('chatbot.index')->middleware('module:admin.chatbot');
        Route::get('ia', fn() => view('admin.ia.index'))->name('ia.index')->middleware('module:admin.ia');
        Route::get('api', fn() => view('admin.api.index'))->name('api.index')->middleware('module:admin.automation');
        Route::get('audit', fn() => view('admin.audit.index'))->name('audit.index')->middleware('module:admin.audit');
        Route::get('bot', fn() => redirect()->route('admin.chatbot.index'))->name('bot.index');
    });
});
