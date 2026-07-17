<?php

namespace App\Listeners;

use App\Models\AccessLog;
use Illuminate\Auth\Events\Failed;

class RecordFailedLogin
{
    public function handle(Failed $event): void
    {
        AccessLog::create([
            // record the username as typed — failed attempts may name no real account
            'username' => $event->credentials['username'] ?? '(unknown)',
            'user_id' => $event->user?->getAuthIdentifier(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'successful' => false,
        ]);
    }
}
