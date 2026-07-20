<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The external User Listing API (authentication-implementation-guide.md Part 2):
 * every org user as {id, name, email}, ids arriving encrypted with the shared
 * APP_KEY. Active only when AUTH_FAKE is off AND an endpoint is configured —
 * dev mode keeps the local-users-only listing.
 */
class UserDirectoryService
{
    private const CACHE_KEY = 'user-directory';

    private const CACHE_SECONDS = 60;

    public function enabled(): bool
    {
        return ! config('services.auth_api.fake')
            && config('services.user_api.endpoint') !== '';
    }

    /** @return array<int, array{id: int, name: string, email: string}> */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn () => $this->fetch());
    }

    /** Force the next all() to hit the API again. */
    public function refresh(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function fetch(): array
    {
        try {
            $response = Http::withHeaders(['x-api-key' => config('services.user_api.key')])
                ->withOptions(['verify' => storage_path('cacert.pem')])
                ->timeout(10)->connectTimeout(5)
                ->post(config('services.user_api.endpoint'));
        } catch (ConnectionException $e) {
            Log::warning('User directory API unreachable', ['error' => $e->getMessage()]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('User directory API error', ['status' => $response->status()]);

            return [];
        }

        $users = $response->json('data') ?? $response->json() ?? [];

        $decrypted = [];
        foreach ($users as $user) {
            try {
                $decrypted[] = [
                    'id' => (int) Crypt::decryptString($user['id']),
                    // The live API sends first/middle/last, not a single name field
                    'name' => trim(($user['first_name'] ?? '').' '.($user['last_name'] ?? ''))
                        ?: (string) ($user['name'] ?? ''),
                    'email' => (string) ($user['email'] ?? ''),
                ];
            } catch (\Throwable) {
                // ids encrypted with a different APP_KEY are skipped, per the guide
            }
        }

        return $decrypted;
    }
}
