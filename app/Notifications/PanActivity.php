<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * One bell entry. Everything renders from the data payload — the bell view
 * never needs to know which event produced it.
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
        return ['database'];
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
}
