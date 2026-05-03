<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Livewire\Component;

class BroadcastMetrics extends Component
{
    public function render()
    {
        $stats = app(DashboardService::class)->broadcastStats();
        return view('livewire.dashboard.broadcast-metrics', compact('stats'));
    }
}
