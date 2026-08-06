<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Enums\PanStatus;
use App\Models\Employee;
use App\Models\PanRequest;

/**
 * The previous_pan_id chain: an employee's most recently APPROVED PAN seeds the
 * next one — its "To" values become the new form's "From" values, and its
 * employment status carries forward (the employment-status lock).
 *
 * Approved, not Filed: per PanWorkflow, Approved has no reject/void path — the
 * only moves from there are mark_served/mark_unserved, so the values are final
 * the moment a PAN lands on Approved. Served and Filed are just paperwork
 * tracking after that fact, not further approval gates. Unserved is excluded
 * deliberately — PanWorkflow has no transition out of it, so a PAN marked
 * Unserved never reaches Filed under any code path; trusting its values risks
 * carrying forward a change that may never have actually reached the employee
 * (resigned, AWOL, refused to sign — see project history for the incident that
 * prompted this).
 */
class CarryOverService
{
    private const CARRY_OVER_STATUSES = [PanStatus::Approved, PanStatus::Served, PanStatus::Filed];

    /** The six fixed Action Reference fields, plus Leave Credits — everything else is a dynamic allowance row. */
    private const KNOWN_FIELDS = ['section', 'place', 'head', 'position', 'joblevel', 'basic', 'leavecredits'];

    /** The employee's most recently approved PAN that has prepared paperwork. */
    public function previousPanFor(Employee $employee, ?PanRequest $ignore = null): ?PanRequest
    {
        return PanRequest::where('employee_id', $employee->id)
            ->whereIn('status', array_map(fn (PanStatus $s) => $s->value, self::CARRY_OVER_STATUSES))
            ->whereHas('form')
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))
            ->orderByDesc('approved_at')
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

    /**
     * Dynamic allowance rows (Communication Allowance, Meal Allowance, etc.) carried
     * from the same previous PAN — the six fixed fields and Leave Credits get their
     * own carry-over above; everything else in the previous PAN's action_reference
     * is a "from" seed for a matching allowance row here. "To" starts blank, same as
     * a freshly-added allowance line, for HR to fill in or remove.
     *
     * @return array<int, array{label: string, from: string, to: string}>
     */
    public function allowancesFor(PanRequest $pan): array
    {
        return $this->allowancesForEmployee($pan->employee, $pan);
    }

    /** Same as allowancesFor(), for an employee with no PAN of their own yet. */
    public function allowancesForEmployee(Employee $employee, ?PanRequest $ignore = null): array
    {
        $previous = $this->previousPanFor($employee, $ignore);

        return collect($previous?->form->action_reference ?? [])
            ->reject(fn (array $row) => in_array($row['field'], self::KNOWN_FIELDS, true))
            ->map(fn (array $row) => ['label' => $row['field'], 'from' => $row['to'], 'to' => ''])
            ->values()
            ->all();
    }

    /** Carried employment status — locked to the last approved PAN's value. */
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
