{{-- Master employee roster: the single source the HR Preparation "Employees" lens reads from.
     Admin owns the records; HR Preparation only works with PANs raised against them. --}}
<div>
  <p class="crumb">Administration</p>
  <div class="htop">
    <div><h2>Employee Directory</h2>
      <p>The master roster every PAN is raised against — and the source of the HR Preparation "Employees" tab. Add, edit, or remove records here.</p></div>
    <div class="spacer"></div>
    <button class="btn" type="button" onclick="showToast('Spreadsheet import is planned (maatwebsite/excel) — not yet installed.')">Import from spreadsheet…</button>
    <button class="btn" type="button" onclick="showToast('Export is planned alongside the spreadsheet import.')">Export</button>
    <button class="btn primary" type="button" wire:click="startCreate">+ Add Employee</button>
  </div>

  <div class="stats">
    <x-stat :value="$stats['total']" label="Employees on roster" />
    <x-stat :value="$stats['departments']" label="Departments" tone="acc" />
    <x-stat :value="$stats['farms']" label="Farms / sites" />
    <x-stat :value="$stats['added']" label="Added this month" tone="warn" />
  </div>

  <div class="bar">
    <div class="search">⌕<input placeholder="Search by name, employee ID, department, or position…" wire:model.live.debounce.300ms="search"></div>
    <button class="chip @if ($farmFilter === null) on @endif" type="button" wire:click="$set('farmFilter', null)">All</button>
    @foreach ($farms as $farm)
    <button class="chip @if ($farmFilter === $farm->id) on @endif" type="button" wire:click="$set('farmFilter', {{ $farm->id }})">{{ $farm->name }}</button>
    @endforeach
  </div>

  @if ($employees->isEmpty())
  <x-empty-state title="No employees found" message="Nothing matches — clear the search, or add the first employee." />
  @else
  <div class="card"><div class="twrap"><table>
    {{-- "Ongoing PAN" = a PAN anywhere in the workflow, submitted and not yet filed/withdrawn/voided
         (drafts don't count). Remove stays visible but blocked with the reason — enforced in
         EmployeePolicy, never just here. --}}
    <thead><tr><th>Employee</th><th>Employee ID</th><th>Department</th><th>Position</th><th>Farm / Site</th><th>Ongoing PAN</th><th></th></tr></thead>
    <tbody>
      @foreach ($employees as $employee)
      @php [$pill, $pillLabel] = App\Livewire\Admin\Employees::ongoingPill($employee); @endphp
      <tr wire:key="emp-{{ $employee->id }}">
        <td><div class="who"><b>{{ $employee->name }}</b></div></td><td class="ref">{{ $employee->employee_no }}</td>
        <td>{{ $employee->department->name }}</td><td>{{ $employee->position }}</td><td>{{ $employee->farm->name }}</td>
        <td><span class="pill {{ $pill }}">{{ $pillLabel }}</span></td>
        <x-row-actions>
          <button class="btn ghost" type="button" wire:click="startEdit({{ $employee->id }})">Edit</button>
          <x-kebab>
            @can('delete', $employee)
            <x-kebab.item danger wire:click="remove({{ $employee->id }})" wire:confirm="Remove {{ $employee->name }} from the roster? PAN history is kept; they can no longer be selected for new PANs.">Remove employee</x-kebab.item>
            @else
            <x-kebab.item disabled title="Cannot remove — this employee has an ongoing PAN">Remove employee<small>Blocked — ongoing PAN</small></x-kebab.item>
            @endcan
          </x-kebab>
        </x-row-actions>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>
  {{ $employees->links('components.pagination') }}
  @endif

  <div class="note info" style="margin-top:14px"><span class="ic">i</span><span><b>Remove is disabled while an employee has an ongoing PAN</b>&nbsp;— anything submitted and not yet filed, withdrawn, or voided blocks removal (drafts don't count). Removing an employee never deletes their PAN history; they simply can no longer be selected for new PANs.</span></div>

  {{-- One modal for Add and Edit (state decides the title) --}}
  <x-modal id="emp-modal" :open="$showModal" close="$set('showModal', false)" title="{{ $editingId ? 'Edit Employee' : 'Add Employee' }}">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr 1fr">
      <div class="field full"><label>Full Name <em>*</em></label><input wire:model="name" maxlength="120" placeholder="Surname, First Name M.I.">
        @error('name')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      <div class="field"><label>Employee ID <em>*</em></label><input wire:model="employee_no" maxlength="20" placeholder="EMP-00000">
        @error('employee_no')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      <div class="field"><label>Department <em>*</em></label>
        <select wire:model="department_id">
          <option value="">Select…</option>
          @foreach ($departments as $department)
          <option value="{{ $department->id }}">{{ $department->name }}</option>
          @endforeach
        </select>
        @error('department_id')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      <div class="field"><label>Position <em>*</em></label><input wire:model="position" maxlength="120" placeholder="e.g. Farm Technician I">
        @error('position')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      <div class="field"><label>Farm / Site <em>*</em></label>
        <select wire:model="farm_id">
          <option value="">Select…</option>
          @foreach ($farms as $farm)
          <option value="{{ $farm->id }}">{{ $farm->name }}</option>
          @endforeach
        </select>
        @error('farm_id')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" wire:click="$set('showModal', false)">Cancel</button>
      <div class="spacer"></div>
      <button class="btn primary" type="button" wire:click="save">Save Employee</button>
    </x-slot:footer>
  </x-modal>
</div>
