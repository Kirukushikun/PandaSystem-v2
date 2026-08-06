<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Dev-only bridge to v1 (PandaSystem) for the transition period — lets a peek at v1's
 * live data for an employee/PAN without leaving v2. See
 * project-overview/legacy-peek-tool-plan.md for the full design thread and contract.
 * Read-only, on-demand, fails soft (v1 being unreachable must never break the v2
 * screen the dev is actually working on).
 */
class LegacyPeekService
{
    private const CACHE_SECONDS = 30;

    public function enabled(): bool
    {
        return config('services.legacy_v1.base_uri') !== '';
    }

    /**
     * @param  string  $employeeNo  v2's Employee.employee_no, e.g. "EMP-01415"
     * @return array<string, mixed>|null null when disabled, unreachable, non-2xx, or not found in v1
     */
    public function forEmployee(string $employeeNo): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $companyId = $this->toCompanyId($employeeNo);

        return Cache::remember(
            "legacy-peek.{$companyId}",
            self::CACHE_SECONDS,
            fn () => $this->fetch($companyId)
        );
    }

    /**
     * v2's employee_no is "EMP-" + the zero-padded company_id (e.g. "EMP-01415" for
     * v1's "1415") — v1's endpoint expects the bare, unpadded id. Confirmed against
     * the real 501-employee roster: every employee_no in this shape, no exceptions.
     */
    private function toCompanyId(string $employeeNo): string
    {
        return ltrim(preg_replace('/^EMP-/', '', $employeeNo), '0') ?: '0';
    }

    private function fetch(string $companyId): ?array
    {
        try {
            $response = Http::withHeaders(['x-api-key' => config('services.legacy_v1.key')])
                ->timeout(10)->connectTimeout(5)
                ->get(rtrim(config('services.legacy_v1.base_uri'), '/')."/api/legacy-peek/employee/{$companyId}");
        } catch (ConnectionException $e) {
            Log::warning('Legacy peek: v1 unreachable', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Legacy peek: v1 error', ['status' => $response->status()]);

            return null;
        }

        $data = $response->json();

        return ($data['found'] ?? false) ? $data : null;
    }
}
