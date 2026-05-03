<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Livewire\Component;

class LeadsMetrics extends Component
{
    public function render()
    {
        $stats = app(DashboardService::class)->leadsStats();
        return view('livewire.dashboard.leads-metrics', compact('stats'));
    }
}
