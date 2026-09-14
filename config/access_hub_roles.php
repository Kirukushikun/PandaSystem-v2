<?php

/**
 * Hub role -> PANDA permission mapping (project-overview/hub-integration-guide.md §4.2).
 *
 * Keys are the hub's fixed role vocabulary (docs/api.md in the hub repo — renamed
 * from `requestor` to `manager` in Sept 2026; do not key this on `requestor`).
 * Values are App\Livewire\Admin\UserAccess::PERM_COLUMNS keys (NOT users.is_*
 * column names) that should be turned on for a person holding that hub role.
 *
 * A person can hold several hub roles at once. This system stores access as
 * independent booleans, so multiple roles combine by UNION — every permission
 * from every role the person holds. That union logic lives in
 * AccessHubSyncService::mapRoles(), not here. This file stays a flat,
 * logic-free lookup — no conditionals, no callbacks, no computed values.
 *
 * A hub role missing from this array (or an unrecognized string in a person's
 * `roles` array) is a SILENT no-op for that role, not an error — see guide §4.2.
 */
return [
    'manager' => ['requestor'],
    'division_head' => ['division_head'],
    'vp' => ['final_approver'],
    'user' => [],
];
