<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Livewire\Attributes\On;
use Livewire\Component;

class ConversationsByDepartment extends Component
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
        $departments = app(DashboardService::class)->conversationsByDepartment($this->dateFrom, $this->dateTo);
        $max = $departments->max('total') ?: 1;
        return view('livewire.dashboard.conversations-by-department', compact('departments', 'max'));
    }
}