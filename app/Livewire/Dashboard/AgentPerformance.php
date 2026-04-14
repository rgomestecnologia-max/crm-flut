<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Livewire\Component;

class AgentPerformance extends Component
{
    public function render()
    {
        $agents = app(DashboardService::class)->agentPerformance();
        return view('livewire.dashboard.agent-performance', compact('agents'));
    }
}
