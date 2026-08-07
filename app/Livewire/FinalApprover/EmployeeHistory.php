<?php

namespace App\Livewire\FinalApprover;

use App\Enums\PanStatus;
use App\Livewire\Concerns\ManagesLegacyRecords;
use App\Models\Employee;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Read-only clone of HrPreparation\EmployeeHistory: every PAN on record for
 * one employee, plus (new for this role) their Legacy Records — Final
 * Approver never has a confidentiality distinction (see PanRequestPolicy),
 * so unlike the HR Prep version this always shows every PAN, Manila included.
 * ManagesLegacyRecords is only pulled in for the shared listing/relation —
 * its mutating actions all abort_unless(is_hr_head), so nothing here is
 * actually reachable by a Final Approver; $canManage is hardcoded false.
 */
#[Layout('layouts.app')]
#[Title('PAN History — PANDA')]
class EmployeeHistory extends Component
{
    use ManagesLegacyRecords;

    public Employee $employee;

    public function mount(string $employeeNo): void
    {
        $this->employee = Employee::where('employee_no', $employeeNo)->firstOrFail();
    }

    public function render()
    {
        $pans = $this->employee->panRequests()
            ->with('form')
            ->orderByDesc('id')
            ->get();

        $lastFiledForm = $pans->firstWhere('status', PanStatus::Filed)?->form;
        $currentBasic = collect($lastFiledForm?->action_reference ?? [])->firstWhere('field', 'basic')['to'] ?? null;

        return view('livewire.final-approver.employee-history', [
            'pans' => $pans,
            'stats' => [
                'total' => $pans->count(),
                'filed' => $pans->where('status', PanStatus::Filed)->count(),
                'rework' => $pans->where('status', PanStatus::ReturnedToPreparer)->count(),
                'basic' => $currentBasic ? '₱ '.$currentBasic : '—',
            ],
            'legacyRecords' => $this->employee->employeeAttachments()->with('uploadedBy')->orderByDesc('id')->get(),
        ]);
    }
}
