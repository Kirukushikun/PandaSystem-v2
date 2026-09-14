<?php

namespace App\Services;

use App\Models\AccessHubConnection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The Access Hub API (project-overview/hub-integration-guide.md): one central
 * list of people and their org role(s). Optional feature — blank base_uri or no
 * enrollment yet means "no sync available," never a broken page (guide §1).
 *
 * TODO: confirm the exact auth header shape for /api/v1/grants and the enroll
 * response shape for /api/v1/enroll against the hub repo's docs/api.md before
 * this goes live — that document wasn't available while wiring this up. The
 * shape below mirrors UserDirectoryService's x-api-key convention as a
 * placeholder; nothing else in this feature depends on it being exactly right
 * yet (see hub-integration-guide.md §5 — "real fetch" is the last build step).
 */
class AccessHubService
{
    /** 'not_enrolled' | 'unreachable' | 'unauthorized' | null — set by the last fetchGrants() call. */
    private ?string $lastFailure = null;

    public function enabled(): bool
    {
        return config('services.access_hub.base_uri') !== '';
    }

    public function isEnrolled(): bool
    {
        return AccessHubConnection::current()->isEnrolled();
    }

    /** Why the last fetchGrants() returned null — check immediately after a null result. */
    public function lastFailureReason(): ?string
    {
        return $this->lastFailure;
    }

    /**
     * @return array<int, array>|null null on any failure (see lastFailureReason()).
     *                                 [] is a valid "hub has zero people" result — not a failure.
     */
    public function fetchGrants(): ?array
    {
        $this->lastFailure = null;

        if (! $this->enabled() || ! $this->isEnrolled()) {
            $this->lastFailure = 'not_enrolled';

            return null;
        }

        $connection = AccessHubConnection::current();

        try {
            $response = Http::withHeaders([
                'X-Client-Id' => $connection->client_id,
                'X-Client-Secret' => $connection->client_secret,
            ])
                ->withOptions(['verify' => storage_path('cacert.pem')])
                ->timeout(10)->connectTimeout(5)
                ->get(config('services.access_hub.base_uri').'/api/v1/grants');
        } catch (ConnectionException $e) {
            Log::warning('Access Hub unreachable', ['error' => $e->getMessage()]);
            $this->lastFailure = 'unreachable';

            return null;
        }

        if ($response->status() === 401 || $response->status() === 403) {
            Log::warning('Access Hub rejected credentials', ['status' => $response->status()]);
            $this->lastFailure = 'unauthorized';

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Access Hub API error', ['status' => $response->status()]);
            $this->lastFailure = 'unreachable';

            return null;
        }

        return $response->json('people') ?? [];
    }

    /** First-time enrollment: exchange a connection code for a client_id/client_secret pair. */
    public function enroll(string $code): bool
    {
        try {
            $response = Http::withOptions(['verify' => storage_path('cacert.pem')])
                ->timeout(10)->connectTimeout(5)
                ->post(config('services.access_hub.base_uri').'/api/v1/enroll', [
                    'code' => $code,
                    'project_key' => config('services.access_hub.project_key'),
                    'environment' => app()->environment(),
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Access Hub enrollment unreachable', ['error' => $e->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('Access Hub enrollment rejected', ['status' => $response->status()]);

            return false;
        }

        $clientId = $response->json('client_id');
        $clientSecret = $response->json('client_secret');

        if (! $clientId || ! $clientSecret) {
            Log::warning('Access Hub enrollment response missing credentials');

            return false;
        }

        AccessHubConnection::current()->update([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        return true;
    }
}
