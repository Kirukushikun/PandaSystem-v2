<?php

namespace App\Livewire\Admin;

use App\Enums\PanStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Farm;
use App\Models\PanRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithPerPage;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Master roster (Admin owns it; the HR Preparation lens only reads it).
 * One shared modal adds and edits; removal is soft — history stays — and is
 * policy-blocked while an ongoing PAN exists.
 */
#[Layout('layouts.app')]
#[Title('Employee Directory — PANDA')]
class Employees extends Component
{
    use WithPagination;
    use WithPerPage;

    public string $search = '';

    public ?int $farmFilter = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFarmFilter(): void
    {
        $this->resetPage();
    }

    // Shared Add/Edit modal state (server-driven open flag — re-renders can't shut it)
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $employee_no = '';

    public ?int $department_id = null;

    public string $position = '';

    public ?int $farm_id = null;

    public function startCreate(): void
    {
        $this->authorize('create', Employee::class);
        $this->reset('editingId', 'name', 'employee_no', 'department_id', 'position', 'farm_id');
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function startEdit(int $id): void
    {
        $employee = Employee::findOrFail($id);
        $this->authorize('update', $employee);

        $this->editingId = $employee->id;
        $this->name = $employee->name;
        $this->employee_no = $employee->employee_no;
        $this->department_id = $employee->department_id;
        $this->position = $employee->position;
        $this->farm_id = $employee->farm_id;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:120',
            'employee_no' => ['required', 'string', 'max:20',
                Rule::unique(Employee::class)->ignore($this->editingId)->withoutTrashed()],
            'department_id' => 'required|exists:departments,id',
            'position' => 'required|string|max:120',
            'farm_id' => 'required|exists:farms,id',
        ], [], ['employee_no' => 'employee ID', 'department_id' => 'department', 'farm_id' => 'farm / site']);

        $data = [
            'name' => $this->name,
            'employee_no' => strtoupper($this->employee_no),
            'department_id' => $this->department_id,
            'position' => $this->position,
            'farm_id' => $this->farm_id,
        ];

        if ($this->editingId !== null) {
            $employee = Employee::findOrFail($this->editingId);
            $this->authorize('update', $employee);
            $employee->update($data);
        } else {
            $this->authorize('create', Employee::class);
            $employee = Employee::create($data);
        }

        $this->js("showToast('{$employee->name} saved to the roster.')");
        $this->reset('showModal', 'editingId', 'name', 'employee_no', 'department_id', 'position', 'farm_id');
    }

    public function remove(int $id): void
    {
        $employee = Employee::findOrFail($id);
        $this->authorize('delete', $employee); // blocked while an ongoing PAN exists

        $employee->delete(); // soft — PAN history stays
        $this->js("showToast('{$employee->name} removed — PAN history is kept.')");
    }

    /** [pill class, label] for the Ongoing PAN column. */
    public static function ongoingPill(Employee $employee): array
    {
        $ongoing = $employee->panRequests->first(fn (PanRequest $pan) => $pan->status->isOngoing());

        if ($ongoing !== null) {
            return [match ($ongoing->status) {
                PanStatus::ReturnedToRequestor, PanStatus::ReturnedToPreparer => 'p-ret',
                PanStatus::WithDivisionHead => 'p-dh',
                PanStatus::AwaitingTag, PanStatus::InPreparation, PanStatus::ForConfirmation => 'p-prep',
                default => 'p-appr',
            }, 'Yes — '.$ongoing->status->label()];
        }

        $hasDraft = $employee->panRequests->contains(fn (PanRequest $pan) => $pan->status === PanStatus::Draft);

        return ['p-draft', $hasDraft ? 'None — draft only' : 'None'];
    }

    /** All four header stats in one aggregate pass (distinct counts included). */
    private function stats(): array
    {
        $row = Employee::query()->selectRaw('
            count(*) as total,
            count(distinct department_id) as departments,
            count(distinct farm_id) as farms,
            sum(case when created_at between ? and ? then 1 else 0 end) as added
        ', [now()->startOfMonth(), now()->endOfMonth()])->toBase()->first();

        return [
            'total' => (int) $row->total,
            'departments' => (int) $row->departments,
            'farms' => (int) $row->farms,
            'added' => (int) $row->added,
        ];
    }

    public function render()
    {
        $employees = Employee::query()
            ->with(['department', 'farm', 'panRequests'])
            ->when($this->search !== '', function (Builder $q) {
                $q->where(fn (Builder $q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('employee_no', 'like', "%{$this->search}%")
                    ->orWhere('position', 'like', "%{$this->search}%")
                    ->orWhereHas('department', fn (Builder $q) => $q->where('name', 'like', "%{$this->search}%")));
            })
            ->when($this->farmFilter, fn (Builder $q) => $q->where('farm_id', $this->farmFilter))
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.admin.employees', [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->get(),
            'farms' => Farm::orderBy('name')->get(),
            'stats' => $this->stats(),
        ]);
    }
}
