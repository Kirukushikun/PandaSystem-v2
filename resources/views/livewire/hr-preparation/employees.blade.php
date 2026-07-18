{{-- HR-side, read-only lens over the master roster managed in Administration → Employees
     (add/edit/remove happens there, not here). "Update PAN" starts a new PAN directly at HR
     Preparation (origin = 'hr'), skipping the Requestor and Division Head stages. --}}
<div>
  <p class="crumb">HR Preparation · signed in as {{ $isHrHead ? 'HR Head Preparer' : 'HR Preparer' }}</p>
  <div class="htop">
    <div><h2>Employees</h2>
    <p>Every employee on the roster, with their PAN history. "Update PAN" starts a new PAN directly from HR — no requestor needed — typically for Wage Orders.</p></div>
    <div class="spacer"></div>
    <a class="btn" href="{{ route('preparation.queue') }}" wire:navigate style="text-decoration:none">← Back to queue</a>
  </div>

  <div class="bar">
    <div class="search">⌕<input placeholder="Search employees by name, ID, or department…" wire:model.live.debounce.300ms="search"></div>
    <button class="chip @if ($departmentFilter === null) on @endif" type="button" wire:click="$set('departmentFilter', null)">All departments</button>
    @foreach ($departments as $department)
    <button class="chip @if ($departmentFilter === $department->id) on @endif" type="button" wire:click="$set('departmentFilter', {{ $department->id }})">{{ $department->name }}</button>
    @endforeach
  </div>

  @if ($employees->isEmpty())
  <x-empty-state title="No employees found" message="Nothing matches — clear the search or pick another department." />
  @else
  <div class="card"><div class="twrap"><table>
    <thead><tr><th>Employee</th><th>Department</th><th>Position</th><th>Farm / Site</th><th>PAN Records</th><th></th></tr></thead>
    <tbody>
      @foreach ($employees as $employee)
      <tr wire:key="emp-{{ $employee->id }}">
        <td><div class="who"><b>{{ $employee->name }}</b><small>{{ $employee->employee_no }}</small></div></td>
        <td>{{ $employee->department->name }}</td>
        <td>{{ $employee->position }}</td>
        <td>{{ $employee->farm->name }}</td>
        <td class="num">{{ $employee->pan_requests_count }}</td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('employees.history', $employee->employee_no) }}" wire:navigate style="text-decoration:none">View PANs</a>
          <button class="btn primary" type="button" wire:click="startUpdate({{ $employee->id }})">Update PAN</button>
        </x-row-actions>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>
  @endif

  @include('livewire.hr-preparation.partials.update-pan-modal')
</div>
