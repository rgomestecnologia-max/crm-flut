<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class MetricsCards extends Component
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
        $user      = Auth::user();
        $service   = app(DashboardService::class);
        $isManager = $user->canManageCompany();

        $stats       = $service->conversationStats($user, $this->dateFrom, $this->dateTo);
        $newContacts = $isManager ? $service->newContactsToday($this->dateFrom, $this->dateTo) : null;
        $avgResponse = $service->avgResponseTime($user, $this->dateFrom, $this->dateTo);

        return view('livewire.dashboard.metrics-cards', compact('stats', 'newContacts', 'avgResponse', 'isManager'));
    }
}