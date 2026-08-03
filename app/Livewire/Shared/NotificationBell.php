<?php

namespace App\Livewire\Shared;

use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Bell + dropdown panel, embedded in the app layout. Open/close stays in global
 * JS (app.js); rows come from the database notifications table (PanActivity:
 * stage handoffs, returns with reasons, filings, expiry reminders). Live updates
 * ride Reverb: app.js subscribes to the user's private channel and, on a new
 * notification, dispatches 'notification-received' — we don't touch the
 * broadcast payload here at all, just re-render, which re-fetches from the DB.
 */
class NotificationBell extends Component
{
    #[On('notification-received')]
    public function refresh(): void
    {
        // Intentionally empty — Livewire re-renders on any dispatched listener hit,
        // and render() below always re-queries fresh from the database.
    }

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
