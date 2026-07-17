<?php

namespace App\Contracts;

/**
 * The seam to the external company system. PANDA sends credentials there and
 * only ever stores the returned profile — never a password. Swap the fake
 * implementation for the real integration without touching the login flow.
 */
interface ExternalAuthenticator
{
    /**
     * Verify credentials against the company system.
     *
     * @return array{external_id: string, username: string, name: string, position: ?string}|null
     *         The external profile on success, null on failure.
     */
    public function attempt(string $username, string $password): ?array;
}
