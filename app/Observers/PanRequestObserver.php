<?php

namespace App\Observers;

use App\Enums\PanStatus;
use App\Models\PanRequest;
use App\Services\PanNotifier;

class PanRequestObserver
{
    /** Every status change fans out its notifications — no component opts in or out. */
    public function updated(PanRequest $pan): void
    {
        if (! $pan->wasChanged('status')) {
            return;
        }

        app(PanNotifier::class)->statusChanged(
            $pan,
            PanStatus::from($pan->getOriginal('status') instanceof PanStatus
                ? $pan->getOriginal('status')->value
                : $pan->getOriginal('status')),
            $pan->status,
        );
    }
}
