<?php

namespace App\Services;

use App\Models\PanRequest;
use App\Models\ProxyApproverSetting;

/**
 * The single place that decides whether a PAN may be proxy-approved right now —
 * consulted by both PanRequestPolicy (gates the action) and ProxyApprover\Queue
 * (filters the list), so the kill switch and staleness rule can't drift apart.
 * Same rule at both DH-gated stages, so it isn't parameterized by status.
 */
class ProxyApprovalEligibility
{
    public function eligible(PanRequest $pan): bool
    {
        $settings = ProxyApproverSetting::current();

        if (! $settings->enabled) {
            return false;
        }

        // Once a PAN has been proxy-approved at any stage, later DH-gated stages
        // don't make it wait out a second full threshold — the same DH already
        // proved unresponsive once.
        if ($pan->wasProxyApproved()) {
            return true;
        }

        return $pan->updated_at !== null
            && $pan->updated_at->lte(now()->subDays($settings->threshold_days));
    }
}
