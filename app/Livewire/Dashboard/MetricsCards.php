<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MetricsCards extends Component
{
    public function render()
    {
        $user      = Auth::user();
        $service   = app(DashboardService::class);
        $isManager = $user->canManageCompany();

        $stats       = $service->conversationStats($user);
        $newContacts = $isManager ? $service->newContactsToday() : null;
        $avgResponse = $service->avgResponseTime($user);

        return view('livewire.dashboard.metrics-cards', compact('stats', 'newContacts', 'avgResponse', 'isManager'));
    }
}
