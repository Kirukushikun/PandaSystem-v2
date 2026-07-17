<?php

namespace App\Services\Fake;

use App\Contracts\ExternalAuthenticator;
use App\Models\User;

/**
 * DEV-ONLY stand-in for the company system so the build never blocks on the
 * real integration: any seeded username signs in with the password "password".
 * Bound to ExternalAuthenticator in AppServiceProvider — replace the binding
 * with the real service when the integration lands.
 */
class FakeExternalAuthService implements ExternalAuthenticator
{
    public function attempt(string $username, string $password): ?array
    {
        if ($password !== 'password') {
            return null;
        }

        $user = User::where('username', $username)->first();

        if (! $user) {
            return null;
        }

        return [
            'external_id' => $user->external_id ?? 'FAKE-'.$user->id,
            'username' => $user->username,
            'name' => $user->name,
            'position' => $user->position,
        ];
    }
}
