<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * One bell entry. Everything renders from the data payload — the bell view
 * never needs to know which event produced it. Broadcasts over the user's
 * private Reverb channel so the bell updates live; the database row is what
 * actually renders (the broadcast payload just triggers NotificationBell to
 * re-fetch), so the two never need to carry identical shapes.
 */
class PanActivity extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public ?string $reference = null,
        public string $context = '',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'reference' => $this->reference,
            'context' => $this->context,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => $this->title,
            'body' => $this->body,
            'reference' => $this->reference,
            'context' => $this->context,
        ]);
    }
}
