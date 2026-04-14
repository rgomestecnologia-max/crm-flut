<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZapiConfig;
use App\Services\ZapiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ZapiConfigController extends Controller
{
    public function index(): View
    {
        $config = ZapiConfig::first();
        return view('admin.zapi.index', compact('config'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'instance_id'    => ['required', 'string'],
            'token'          => ['required', 'string'],
            'client_token'   => ['nullable', 'string'],
            'webhook_secret' => ['nullable', 'string'],
        ]);

        ZapiConfig::updateOrCreate(['id' => 1], array_merge($validated, ['is_active' => true]));
        return back()->with('success', 'Configuração Z-API salva.');
    }

    public function testConnection(): JsonResponse
    {
        $service = app(ZapiService::class);
        $result  = $service->getConnectionStatus();

        if ($result['success'] ?? false) {
            $status = $result['connected'] ?? false ? 'connected' : 'disconnected';
            ZapiConfig::where('id', 1)->update(['connection_status' => $status]);
        }

        return response()->json($result);
    }
}
