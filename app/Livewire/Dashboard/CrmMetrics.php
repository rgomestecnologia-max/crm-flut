<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Livewire\Attributes\On;
use Livewire\Component;

class CrmMetrics extends Component
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
        $stats = app(DashboardService::class)->crmStats($this->dateFrom, $this->dateTo);
        return view('livewire.dashboard.crm-metrics', compact('stats'));
    }
}