<?php

namespace App\Livewire\HrPreparation;

use App\Enums\ConfidentialityTag;
use App\Enums\PanStatus;
use App\Livewire\HrPreparation\Concerns\StartsHrPan;
use App\Livewire\Concerns\ManagesLegacyRecords;
use App\Models\Employee;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Every PAN on record for one employee, newest first. Each PAN's "From" values
 * chain from the previous one's "To" values (previous_pan_id).
 */
#[Layout('layouts.app')]
#[Title('PAN History — PANDA')]
class EmployeeHistory extends Component
{
    use StartsHrPan;
    use ManagesLegacyRecords;

    public Employee $employee;

    public function mount(string $employeeNo): void
    {
        $this->employee = Employee::where('employee_no', $employeeNo)->firstOrFail();
        $this->updateEmployeeId = $this->employee->id;
    }

    public function render()
    {
        $pans = $this->employee->panRequests()
            ->with('form')
            // Manila history rows exist only for HR Head preparers — same rule as the queue
            ->when(! auth()->user()->is_hr_head, fn ($q) => $q
                ->where('confidentiality_tag', '!=', ConfidentialityTag::Manila->value))
            ->orderByDesc('id')
            ->get();

        // "Current basic pay" = the last filed PAN's Basic Pay "To" value
        $lastFiledForm = $pans->firstWhere('status', PanStatus::Filed)?->form;
        $currentBasic = collect($lastFiledForm?->action_reference ?? [])->firstWhere('field', 'basic')['to'] ?? null;

        return view('livewire.hr-preparation.employee-history', [
            'pans' => $pans,
            'stats' => [
                'total' => $pans->count(),
                'filed' => $pans->where('status', PanStatus::Filed)->count(),
                'rework' => $pans->where('status', PanStatus::ReturnedToPreparer)->count(),
                'basic' => $currentBasic ? '₱ '.$currentBasic : '—',
            ],
            'updateEmployee' => $this->employee,
            'isHrHead' => auth()->user()->is_hr_head,
            // Legacy Records is HR Head-only, full stop — a plain HR Preparer
            // doesn't get a read-only view of it either, per the feature's scope.
            'canViewLegacyRecords' => auth()->user()->is_hr_head,
            'canManageLegacyRecords' => auth()->user()->is_hr_head,
            'legacyRecords' => auth()->user()->is_hr_head
                ? $this->employee->employeeAttachments()->with('uploadedBy')->orderByDesc('id')->get()
                : collect(),
        ]);
    }
}
