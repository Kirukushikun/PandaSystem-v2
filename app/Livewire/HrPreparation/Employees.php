<?php

namespace App\Livewire\HrPreparation;

use App\Livewire\HrPreparation\Concerns\StartsHrPan;
use App\Models\Department;
use App\Models\Employee;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithPerPage;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * HR-side, read-only lens over the master roster managed in Administration →
 * Employees. "Update PAN" starts a new PAN directly at HR (origin='hr').
 */
#[Layout('layouts.app')]
#[Title('Employees — PANDA')]
class Employees extends Component
{
    use StartsHrPan;
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

        return view('livewire.hr-preparation.employees', [
            'employees' => $employees,
            'departments' => Department::whereHas('employees')->orderBy('name')->get(),
            'updateEmployee' => $this->updateEmployeeId ? Employee::find($this->updateEmployeeId) : null,
            'isHrHead' => auth()->user()->is_hr_head,
        ]);
    }
}
