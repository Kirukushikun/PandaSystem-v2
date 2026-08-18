<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * The single source of truth for the login-lockout cache keys — used by
 * LoginController (enforces it) and Admin\UserAccess (lets an admin detect
 * and manually clear it, instead of the account waiting out the 15 minutes).
 */
class LoginLockoutService
{
    private const MAX_ATTEMPTS = 3;

    private const LOCKOUT_SECONDS = 900; // 15 minutes

    public function maxAttempts(): int
    {
        return self::MAX_ATTEMPTS;
    }

    public function isLocked(string $email): bool
    {
        return Cache::has($this->lockoutKey($email));
    }

    public function attempts(string $email): int
    {
        return Cache::get($this->attemptsKey($email), 0);
    }

    public function recordFailure(string $email): int
    {
        $attempts = $this->attempts($email) + 1;

        Cache::put($this->attemptsKey($email), $attempts, now()->addMinutes(15));

        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::put($this->lockoutKey($email), true, self::LOCKOUT_SECONDS);
        }

        return $attempts;
    }

    /** Manual admin bypass, or a successful login — both clear the slate. */
    public function clear(string $email): void
    {
        Cache::forget($this->attemptsKey($email));
        Cache::forget($this->lockoutKey($email));
    }

    private function lockoutKey(string $email): string
    {
        return 'login_lockout_'.sha1($email);
    }

    private function attemptsKey(string $email): string
    {
        return 'login_attempts_'.sha1($email);
    }
}
