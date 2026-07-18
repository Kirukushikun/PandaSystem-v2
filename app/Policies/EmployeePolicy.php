<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

/**
 * Roster management is admin-only (the route gate says who gets in; this says
 * what they may do per record). The v1 lesson lives here: deletion is blocked
 * by POLICY while an ongoing PAN exists — not by hiding the button.
 */
class EmployeePolicy
{
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Employee $employee): bool
    {
        // Submitted-and-unfinished PANs block removal; drafts don't.
        return $user->is_admin && ! $employee->hasOngoingPan();
    }
}
