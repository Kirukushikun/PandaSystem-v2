<?php

namespace App\Listeners;

use App\Models\AccessLog;
use Illuminate\Auth\Events\Login;

class RecordSuccessfulLogin
{
    public function handle(Login $event): void
    {
        AccessLog::create([
            'username' => $event->user->username,
            'user_id' => $event->user->getAuthIdentifier(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'successful' => true,
        ]);
    }
}
