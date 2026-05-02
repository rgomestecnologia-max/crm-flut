<?php

namespace App\Livewire;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $showDropdown = false;

    public function toggleDropdown(): void
    {
        $this->showDropdown = !$this->showDropdown;
    }

    public function markAsRead(int $id): void
    {
        Notification::where('id', $id)->update(['is_read' => true]);
    }

    public function markAllAsRead(): void
    {
        Notification::forUser(Auth::user())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->dispatch('toast', type: 'success', message: 'Todas as notificações marcadas como lidas.');
    }

    public function render()
    {
        $user = Auth::user();
        $unreadCount = Notification::forUser($user)->where('is_read', false)->count();
        $notifications = Notification::forUser($user)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('livewire.notification-bell', compact('unreadCount', 'notifications'));
    }
}
