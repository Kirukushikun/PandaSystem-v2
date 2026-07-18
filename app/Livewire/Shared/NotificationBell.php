<?php

namespace App\Livewire\Shared;

use Livewire\Component;

/**
 * Bell + dropdown panel, embedded in the app layout. Open/close stays in global
 * JS (app.js); rows come from the database notifications table (PanActivity:
 * stage handoffs, returns with reasons, filings, expiry reminders).
 */
class NotificationBell extends Component
{
    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        return view('livewire.shared.notification-bell', [
            'notifications' => auth()->user()->notifications()->latest()->limit(15)->get(),
            'unread' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
