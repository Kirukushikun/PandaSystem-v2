<?php

namespace App\Livewire\Shared;

use Livewire\Component;

/**
 * Bell + dropdown panel, embedded in the app layout. Open/close stays in global JS
 * (app.js); the unread state lives here. Real build: rows come from the database
 * notifications table (expiry reminders, returns, filings…).
 */
class NotificationBell extends Component
{
    public array $notifications = [
        ['text' => '<b>Allowance expiring soon</b> — C. Mercado\'s Interim Allowance (<span class="ref">PAN-2026-00332</span>) ends Jul 22, 2026.', 'meta' => '2 hours ago · Expiry reminder', 'unread' => true],
        ['text' => '<b>Returned for resolution</b> — <span class="ref">PAN-2026-00338</span> was sent back by the HR Approver: "Wage number mismatch".', 'meta' => 'Yesterday · HR Preparation', 'unread' => true],
        ['text' => '<b>Returned to you</b> — <span class="ref">PAN-2026-00351</span> needs its attachment replaced before resubmitting.', 'meta' => 'Jul 10 · Requestor', 'unread' => true],
        ['text' => '<b>Filed</b> — <span class="ref">PAN-2026-00298</span> (L. Bautista, Wage Order) closed out.', 'meta' => 'Jun 30 · HR Preparation', 'unread' => false],
        ['text' => '<b>Bulk approval</b> — 3 Regularization PANs were approved by V. Salazar.', 'meta' => 'Jun 28 · Final Approver', 'unread' => false],
    ];

    public function markAllRead(): void
    {
        foreach ($this->notifications as &$n) {
            $n['unread'] = false;
        }
    }

    public function render()
    {
        return view('livewire.shared.notification-bell');
    }
}
