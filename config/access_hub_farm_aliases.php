<?php

/**
 * Hub farm name -> PANDA Farm.name, for the cases where the two don't already
 * match exactly (case-insensitively). Checked before falling back to a direct
 * name match — see AccessHubSyncService::resolveFarmId().
 *
 * The hub's 7 farms vs. PANDA's 5 (BDL/BFC/BRD/PFC/RH — see ReferenceDataSeeder):
 *   BFC        -> BFC        exact match, no alias needed
 *   PFC        -> PFC        exact match, no alias needed
 *   BROOKDALE  -> BDL        the hub doesn't distinguish PANDA's two Brookdale
 *                            codes (BDL/BRD, both print as "Brookdale Farms
 *                            Corporation") — BDL chosen deliberately, not BRD;
 *                            confirmed 2026-09-15, see hub-integration-guide.md §8.3
 *   RH/BBGC    -> RH         same farm, different string
 *   BFC-IRAQ, FEEDMILL, HATCHERY -> deliberately absent. No PANDA Farm row for
 *                            these yet (confirmed 2026-09-15: leave unmapped for
 *                            now rather than auto-creating farms) — people at
 *                            these sites resolve with farm_id left blank, logged
 *                            as unmatched each sync until this is revisited.
 */
return [
    'BROOKDALE' => 'BDL',
    'RH/BBGC' => 'RH',
];
