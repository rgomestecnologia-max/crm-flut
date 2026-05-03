<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Livewire\Component;

class CrmMetrics extends Component
{
    public function render()
    {
        $stats = app(DashboardService::class)->crmStats();
        return view('livewire.dashboard.crm-metrics', compact('stats'));
    }
}
