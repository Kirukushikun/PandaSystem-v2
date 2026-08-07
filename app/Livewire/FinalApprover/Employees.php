<?php

namespace App\Livewire\FinalApprover;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Department;
use App\Models\Employee;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Read-only clone of HrPreparation\Employees — same roster lens, but no
 * "Update PAN" action. Final Approver never originates or edits a PAN, only
 * views it (and, new here, an employee's Legacy Records — see EmployeeHistory).
 */
#[Layout('layouts.app')]
#[Title('Employees — PANDA')]
class Employees extends Component
{
    use WithPagination;
    use WithPerPage;

    public string $search = '';

    public ?int $departmentFilter = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDepartmentFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $employees = Employee::query()
            ->with(['department', 'farm'])
            ->withCount('panRequests')
            ->when($this->search !== '', function ($q) {
                $q->where(fn ($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('employee_no', 'like', "%{$this->search}%")
                    ->orWhereHas('department', fn ($q) => $q->where('name', 'like', "%{$this->search}%")));
            })
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.final-approver.employees', [
            'employees' => $employees,
            'departments' => Department::whereHas('employees')->orderBy('name')->get(),
        ]);
    }
}
