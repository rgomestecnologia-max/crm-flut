<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MetricsCards extends Component
{
    public function render()
    {
        $user    = Auth::user();
        $service = app(DashboardService::class);

        $stats       = $service->conversationStats($user);
        $newContacts = $service->newContactsToday();
        $avgResponse = $service->avgResponseTime($user);

        return view('livewire.dashboard.metrics-cards', compact('stats', 'newContacts', 'avgResponse'));
    }
}
