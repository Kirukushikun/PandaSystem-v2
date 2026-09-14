<?php

namespace App\Enums;

/**
 * Where a users row came from. Sync only ever touches rows it owns (source = Hub) —
 * a manually-added account (the seeded admin, a one-off special-access grant, anyone
 * added by hand through Admin → Access) is never modified or deleted by Access Hub
 * sync, no matter what the hub reports for that id. See project-overview/
 * hub-integration-guide.md §4.1.
 */
enum UserSource: string
{
    case Manual = 'manual';
    case Hub = 'hub';
}
