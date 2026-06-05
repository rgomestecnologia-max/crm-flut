<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Livewire\Attributes\On;
use Livewire\Component;

class AgentPerformance extends Component
{
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    #[On('dashboard-date-changed')]
    public function onDateChanged(?string $from = null, ?string $to = null): void
    {
        $this->dateFrom = $from;
        $this->dateTo = $to;
    }

    public function render()
    {
        $agents = app(DashboardService::class)->agentPerformance($this->dateFrom, $this->dateTo);
        return view('livewire.dashboard.agent-performance', compact('agents'));
    }
}