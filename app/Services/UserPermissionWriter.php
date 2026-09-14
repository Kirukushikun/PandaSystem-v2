<?php

namespace App\Services;

use App\Livewire\Admin\UserAccess;
use App\Models\User;

/**
 * The one path that writes UserAccess::PERM_COLUMNS booleans onto a User row.
 * Used by the hand-editing panel (UserAccess::save()) and Access Hub sync's
 * apply step (AccessHubSyncService) — per hub-integration-guide.md §4.6,
 * "reuse the assign and revoke logic the panel already has... do not write a
 * second path into users."
 */
class UserPermissionWriter
{
    /** @param array<string,bool> $perms keyed by UserAccess::PERM_COLUMNS keys */
    public static function write(User $user, array $perms): void
    {
        $columns = [];
        foreach (UserAccess::PERM_COLUMNS as $key => $column) {
            if (array_key_exists($key, $perms)) {
                $columns[$column] = (bool) $perms[$key];
            }
        }

        $user->update($columns);
    }
}
