<?php

namespace App\Http\Controllers;

use App\Models\PricingConfig;
use App\Models\Proposal;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function show()
    {
        PricingConfig::seed();
        $config = PricingConfig::getAll();
        return view('pricing', compact('config'));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'client_name'   => 'required|string|max:255',
            'modules'       => 'required|array',
            'config'        => 'required|array',
            'details'       => 'required|array',
            'total_monthly' => 'required|numeric|min:0',
            'total_setup'   => 'required|numeric|min:0',
        ]);

        $validated['user_id'] = auth()->id();

        $proposal = Proposal::create($validated);

        return response()->json([
            'success' => true,
            'id'      => $proposal->id,
            'message' => 'Proposta salva com sucesso!',
        ]);
    }

    public function edit(string $token)
    {
        $proposal = Proposal::where('token', $token)->firstOrFail();
        PricingConfig::seed();
        $config = PricingConfig::getAll();
        return view('pricing', compact('config', 'proposal'));
    }

    public function update(Request $request, string $token)
    {
        $proposal = Proposal::where('token', $token)->firstOrFail();
        $validated = $request->validate([
            'client_name'   => 'required|string|max:255',
            'modules'       => 'required|array',
            'config'        => 'required|array',
            'details'       => 'required|array',
            'total_monthly' => 'required|numeric|min:0',
            'total_setup'   => 'required|numeric|min:0',
        ]);

        $proposal->update($validated);

        return response()->json([
            'success' => true,
            'id'      => $proposal->id,
            'message' => 'Proposta atualizada com sucesso!',
        ]);
    }
}
