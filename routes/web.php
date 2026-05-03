<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\ZapiConfigController;
use App\Livewire\Auth\SelectCompany;
use Illuminate\Support\Facades\Route;

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Tela de seleção de empresa (admin) — fica dentro de auth mas FORA do middleware 'company'
// porque é justamente onde a empresa é escolhida.
Route::middleware('auth')->group(function () {
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
        Route::get('zapi', [ZapiConfigController::class, 'index'])->name('zapi.index');
        Route::post('zapi', [ZapiConfigController::class, 'update'])->name('zapi.update');
        Route::post('zapi/test', [ZapiConfigController::class, 'testConnection'])->name('zapi.test');
        Route::get('evolution', fn() => view('admin.evolution.index'))->name('evolution.index');
    });

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
