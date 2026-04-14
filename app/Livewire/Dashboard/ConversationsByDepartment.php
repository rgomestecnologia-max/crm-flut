<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Livewire\Component;

class ConversationsByDepartment extends Component
{
    public function render()
    {
        $departments = app(DashboardService::class)->conversationsByDepartment();
        $max = $departments->max('total') ?: 1;
        return view('livewire.dashboard.conversations-by-department', compact('departments', 'max'));
    }
}
