<?php

namespace App\Livewire\Concerns;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sort + Type of Action + Department, composable onto any PanRequest list/queue
 * alongside whatever base scoping the component already does (ownership, status
 * buckets, Manila visibility, division scoping, etc. — all untouched, this only
 * adds the WHERE/ORDER BY on top). department_id lives directly on pan_requests
 * (set from the employee's department at creation), so filtering by it never
 * needs a join.
 */
trait FiltersPanRequests
{
    public string $sort = 'newest'; // newest | oldest | employee_az | employee_za

    public ?string $actionTypeFilter = null; // null = all — ActionType::value otherwise

    public ?int $departmentFilter = null; // null = all — Department id otherwise

    // Collapsed by default — server state (not a client classList toggle), same
    // idiom as every other togglable panel in this app (showUpdateModal, etc.):
    // a client-only toggle would get silently reset by Livewire's DOM morph the
    // moment any wire:model.live select inside it fires a re-render.
    public bool $showFilters = false;

    public function hasActiveFilters(): bool
    {
        return $this->sort !== 'newest' || $this->actionTypeFilter !== null || $this->departmentFilter !== null;
    }

    /** Overridden in HrPreparation\Queue to also reset tagFilter (not part of this trait). */
    public function clearPanFilters(): void
    {
        $this->sort = 'newest';
        $this->actionTypeFilter = null;
        $this->departmentFilter = null;
        $this->resetPageIfPaginated();
    }

    public function updatedSort(): void
    {
        $this->resetPageIfPaginated();
    }

    public function updatedActionTypeFilter(): void
    {
        $this->resetPageIfPaginated();
    }

    public function updatedDepartmentFilter(): void
    {
        $this->resetPageIfPaginated();
    }

    private function resetPageIfPaginated(): void
    {
        // FinalApprover\Queue has no pagination (small always-visible worklist) —
        // everything else pairs WithPagination, which is what defines resetPage().
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * WHERE-only — safe to fold into a query that's also reused for a GROUP BY
     * stats count (e.g. HrPreparation\Queue's scope()), since it never adds an
     * ORDER BY that could conflict with the grouping.
     */
    protected function applyPanFilters(Builder $query): Builder
    {
        return $query
            ->when($this->actionTypeFilter, fn (Builder $q) => $q->where('action_type', $this->actionTypeFilter))
            ->when($this->departmentFilter, fn (Builder $q) => $q->where('department_id', $this->departmentFilter));
    }

    protected function applyPanSort(Builder $query): Builder
    {
        return $query
            ->when($this->sort === 'employee_az', fn (Builder $q) => $q->orderBy(
                Employee::select('name')->whereColumn('employees.id', 'pan_requests.employee_id')
            ))
            ->when($this->sort === 'employee_za', fn (Builder $q) => $q->orderByDesc(
                Employee::select('name')->whereColumn('employees.id', 'pan_requests.employee_id')
            ))
            ->when($this->sort === 'oldest', fn (Builder $q) => $q->orderBy('id'))
            ->when($this->sort === 'newest', fn (Builder $q) => $q->orderByDesc('id'));
    }

    /** Convenience for the common case: one query, no separate GROUP BY reuse. */
    protected function applyPanFiltersAndSort(Builder $query): Builder
    {
        return $this->applyPanSort($this->applyPanFilters($query));
    }
}
