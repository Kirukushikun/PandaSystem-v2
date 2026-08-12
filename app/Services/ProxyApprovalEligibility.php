<?php

namespace App\Services;

use App\Models\PanRequest;
use App\Models\ProxyApproverSetting;

/**
 * The single place that decides whether a PAN may be proxy-approved right now —
 * consulted by both PanRequestPolicy (gates the action) and ProxyApprover\Queue
 * (filters the list), so the kill switch and staleness rule can't drift apart.
 * Same rule at both DH-gated stages, so it isn't parameterized by status.
 *
 * Staleness is measured from pan_requests.submitted_at — the ORIGINAL submission
 * (set once, by the Requestor or HR-origin creation; never touched again). At the
 * WithDivisionHead stage this is exactly "time waiting on this DH." At
 * ForConfirmation it also folds in however long HR Preparation took in between —
 * accepted deliberately (reuses the one field already shown on the PAN's detail
 * card, rather than a second per-stage timestamp) — so a PAN can already read as
 * "stale" the moment it reaches confirmation if preparation alone took a while.
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

        return $pan->submitted_at !== null
            && $pan->submitted_at->lte(now()->subDays($settings->threshold_days));
    }
}
