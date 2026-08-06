<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Enums\PanStatus;
use App\Models\Employee;
use App\Models\PanRequest;

/**
 * The previous_pan_id chain: an employee's last FILED PAN seeds the next one —
 * its "To" values become the new form's "From" values, and its employment
 * status carries forward (the employment-status lock).
 */
class CarryOverService
{
    /** The employee's most recently filed PAN that has prepared paperwork. */
    public function previousPanFor(Employee $employee, ?PanRequest $ignore = null): ?PanRequest
    {
        return PanRequest::where('employee_id', $employee->id)
            ->where('status', PanStatus::Filed->value)
            ->whereHas('form')
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))
            ->orderByDesc('filed_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * "From" values for a new Action Reference, keyed by field. Previous PAN's
     * "To" values win; the employee record fills the gaps a fresh hire has.
     *
     * @return array<string, string>
     */
    public function fromValuesFor(PanRequest $pan): array
    {
        return $this->fromValuesForEmployee($pan->employee, $pan);
    }

    /**
     * Same computation as fromValuesFor(), but for an employee with no PAN of their
     * own yet to anchor it to — e.g. simulating "what would carry-over produce right
     * now" without an actual in-progress PAN (see the v1 Peek dev tool).
     *
     * @return array<string, string>
     */
    public function fromValuesForEmployee(Employee $employee, ?PanRequest $ignore = null): array
    {
        $from = [
            'section' => '',
            'place' => $employee->farm->name,
            'head' => '',
            'position' => $employee->position,
            'joblevel' => '',
            'basic' => '',
        ];

        $previous = $this->previousPanFor($employee, $ignore);
        foreach ($previous?->form->action_reference ?? [] as $row) {
            if (array_key_exists($row['field'], $from)) {
                $from[$row['field']] = $row['to'];
            }
        }

        return $from;
    }

    /** Carried employment status — locked to the last filed PAN's value. */
    public function employmentStatusFor(PanRequest $pan): EmploymentStatus
    {
        return $this->employmentStatusForEmployee($pan->employee, $pan);
    }

    /** Same as employmentStatusFor(), for an employee with no PAN of their own yet. */
    public function employmentStatusForEmployee(Employee $employee, ?PanRequest $ignore = null): EmploymentStatus
    {
        return $this->previousPanFor($employee, $ignore)?->form->employment_status
            ?? EmploymentStatus::Probationary;
    }
}
