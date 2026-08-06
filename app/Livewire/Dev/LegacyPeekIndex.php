<?php

namespace App\Livewire\Dev;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Department;
use App\Models\Employee;
use App\Services\LegacyPeekService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Dev-only, hard-pinned to user id 61 — a personal cheat code for the v1/v2
 * transition period, not a role/permission. See project-overview/legacy-peek-tool-plan.md.
 * Browses v2's own roster and checks the current page against v1 on demand.
 */
#[Layout('layouts.app')]
#[Title('v1 Peek — PANDA')]
class LegacyPeekIndex extends Component
{
    use WithPagination;
    use WithPerPage;

    public string $search = '';

    public ?int $departmentFilter = null;

    /** @var array<string, array|null> employee_no => v1 payload, or null = checked & not found/unreachable */
    public array $v1Status = [];

    public function mount(): void
    {
        abort_unless(auth()->id() === 61, 404);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDepartmentFilter(): void
    {
        $this->resetPage();
    }

    /** Checks every employee on the current page against v1 in one go — bounded by page size. */
    public function checkPage(): void
    {
        $service = app(LegacyPeekService::class);

        foreach ($this->currentPageEmployees() as $employee) {
            $this->v1Status[$employee->employee_no] = $service->forEmployee($employee->employee_no);
        }
    }

    private function currentPageEmployees()
    {
        return $this->query()->paginate($this->perPage);
    }

    private function query()
    {
        return Employee::query()
            ->with(['department', 'farm'])
            ->withCount('panRequests')
            ->when($this->search !== '', function ($q) {
                $q->where(fn ($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('employee_no', 'like', "%{$this->search}%")
                    ->orWhereHas('department', fn ($q) => $q->where('name', 'like', "%{$this->search}%")));
            })
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            ->orderBy('name');
    }

    public function render()
    {
        return view('livewire.dev.legacy-peek-index', [
            'employees' => $this->query()->paginate($this->perPage),
            'departments' => Department::whereHas('employees')->orderBy('name')->get(),
            'v1Enabled' => app(LegacyPeekService::class)->enabled(),
        ]);
    }
}
