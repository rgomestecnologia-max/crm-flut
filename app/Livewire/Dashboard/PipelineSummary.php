<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Livewire\Component;

class PipelineSummary extends Component
{
    public function render()
    {
        $pipelines = app(DashboardService::class)->pipelineSummary();
        return view('livewire.dashboard.pipeline-summary', compact('pipelines'));
    }
}
