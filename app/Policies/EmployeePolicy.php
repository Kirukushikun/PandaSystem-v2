<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

/**
 * Roster management: admins and HR Preparers may add/edit (the route gate says
 * who gets in; this says what they may do per record) — HR Preparers are the
 * ones who actually know when someone's hired. Removal stays admin-only: the
 * v1 lesson lives here too — deletion is blocked by POLICY while an ongoing
 * PAN exists, not by hiding the button.
 */
class EmployeePolicy
{
    public function create(User $user): bool
    {
        return $user->is_admin || $user->is_hr_preparer;
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->is_admin || $user->is_hr_preparer;
    }

    public function delete(User $user, Employee $employee): bool
    {
        // Submitted-and-unfinished PANs block removal; drafts don't.
        return $user->is_admin && ! $employee->hasOngoingPan();
    }
}
