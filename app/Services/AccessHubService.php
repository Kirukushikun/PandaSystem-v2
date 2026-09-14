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
 * Wire protocol confirmed at https://accesshub.bfcgroup.ph — no path suffix on
 * the base URL (that's the human /login page, not the API root):
 *   POST /api/v1/enroll  {code, project_key, environment} -> {client_id, client_secret}
 *                         422 bad/expired code, 404 unknown project, 429 rate limited
 *   GET  /api/v1/grants   auth via X-Client-Id / X-Client-Secret headers (not
 *                         x-api-key, not Authorization: Bearer)
 *                         401/403 revoked or invalid, 429 rate limited (60/min)
 */
class AccessHubService
{
    /** 'not_enrolled' | 'unreachable' | 'unauthorized' | 'rate_limited' | null — set by the last fetchGrants() call. */
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

        if ($response->status() === 429) {
            Log::warning('Access Hub rate-limited this request');
            $this->lastFailure = 'rate_limited';

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Access Hub API error', ['status' => $response->status()]);
            $this->lastFailure = 'unreachable';

            return null;
        }

        return $response->json('people') ?? [];
    }

    /**
     * First-time enrollment: exchange a connection code for a client_id/client_secret pair.
     *
     * @return string 'ok' | 'invalid_code' | 'unknown_project' | 'rate_limited' | 'unreachable'
     */
    public function enroll(string $code): string
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

            return 'unreachable';
        }

        $status = $response->status();

        if ($status === 422) {
            Log::warning('Access Hub enrollment code invalid, expired, or already used');

            return 'invalid_code';
        }

        if ($status === 404) {
            Log::warning('Access Hub does not recognize this project key');

            return 'unknown_project';
        }

        if ($status === 429) {
            Log::warning('Access Hub rate-limited enrollment attempts');

            return 'rate_limited';
        }

        if (! $response->successful()) {
            Log::warning('Access Hub enrollment rejected', ['status' => $status]);

            return 'unreachable';
        }

        $clientId = $response->json('client_id');
        $clientSecret = $response->json('client_secret');

        if (! $clientId || ! $clientSecret) {
            Log::warning('Access Hub enrollment response missing credentials');

            return 'unreachable';
        }

        AccessHubConnection::current()->update([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        return 'ok';
    }
}
