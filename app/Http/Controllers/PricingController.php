<?php

namespace App\Http\Controllers;

use App\Models\PricingConfig;

class PricingController extends Controller
{
    public function show()
    {
        PricingConfig::seed();
        $config = PricingConfig::getAll();
        return view('pricing', compact('config'));
    }
}
