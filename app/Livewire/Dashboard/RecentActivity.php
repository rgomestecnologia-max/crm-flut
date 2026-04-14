<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Livewire\Component;

class RecentActivity extends Component
{
    public function render()
    {
        $activities = app(DashboardService::class)->recentActivity();
        return view('livewire.dashboard.recent-activity', compact('activities'));
    }
}
