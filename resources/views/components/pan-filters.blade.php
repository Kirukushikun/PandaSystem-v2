{{-- Sort + Type of Action + Department, for any PanRequest list/queue using
     FiltersPanRequests. Selects (not chips) — 13 action types and up to 11
     departments are too many for the pill-row pattern used for 2-4 option
     status/tag filters elsewhere. --}}
@props(['departments' => null])
<div class="filters-section-label">Sort and filter</div>
<select class="filter-select" wire:model.live="sort" aria-label="Sort">
  <option value="newest">Newest first</option>
  <option value="oldest">Oldest first</option>
  <option value="employee_az">Employee A–Z</option>
  <option value="employee_za">Employee Z–A</option>
</select>
<div class="filters-grid-2">
  <select class="filter-select" wire:model.live="actionTypeFilter" aria-label="Filter by type of action">
    <option value="">All types</option>
    @foreach (App\Enums\ActionType::cases() as $type)
    <option value="{{ $type->value }}">{{ $type->label() }}</option>
    @endforeach
  </select>
  <select class="filter-select" wire:model.live="departmentFilter" aria-label="Filter by department">
    <option value="">All depts</option>
    @foreach (($departments ?? App\Models\Department::orderBy('name')->get()) as $department)
    <option value="{{ $department->id }}">{{ $department->name }}</option>
    @endforeach
  </select>
</div>
