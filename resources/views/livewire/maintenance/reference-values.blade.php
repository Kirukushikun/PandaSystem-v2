{{-- Real lists: × deletes only when the model's isInUse() guard allows it. --}}
<div>
  <p class="crumb">Administration · Maintenance</p>
  <x-maintenance-tabs />

  <div class="htop">
    <div><h2>Reference Values</h2>
      <p>The option lists the rest of the system draws from. Values in use by employees or PANs can't be deleted.</p></div>
  </div>

  <div class="twocol">
    <div class="pane">
      <h3>Farms / Sites</h3>
      <div class="pad">
        @foreach ($farms as $farm)
        <div class="refrow" wire:key="farm-{{ $farm->id }}">
          <span>{{ $farm->name }}</span><small>{{ $farm->employees_count }} {{ Str::plural('employee', $farm->employees_count) }}</small>
          @if ($farm->isInUse())
          <button class="rowdel" type="button" disabled title="In use — cannot delete" style="opacity:.35;cursor:not-allowed">×</button>
          @else
          <button class="rowdel" type="button" title="Delete value" wire:click="removeFarm({{ $farm->id }})" wire:confirm="Delete {{ $farm->name }}?">×</button>
          @endif
        </div>
        @endforeach
        <div style="display:flex;gap:8px;margin-top:12px">
          <input class="refin" placeholder="New farm / site name" wire:model="newFarm" wire:keydown.enter="addFarm">
          <button class="btn" type="button" wire:click="addFarm">Add</button>
        </div>
        @error('newFarm')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror
      </div>
    </div>
    <div class="pane">
      <h3>Departments</h3>
      <div class="pad">
        @foreach ($departments as $department)
        <div class="refrow" wire:key="dept-{{ $department->id }}">
          <span>{{ $department->name }}</span><small>{{ $department->heads_count }} {{ Str::plural('head', $department->heads_count) }} · {{ $department->employees_count }} {{ Str::plural('employee', $department->employees_count) }}</small>
          @if ($department->isInUse())
          <button class="rowdel" type="button" disabled title="In use — cannot delete" style="opacity:.35;cursor:not-allowed">×</button>
          @else
          <button class="rowdel" type="button" title="Delete value" wire:click="removeDept({{ $department->id }})" wire:confirm="Delete {{ $department->name }}?">×</button>
          @endif
        </div>
        @endforeach
        <div style="display:flex;gap:8px;margin-top:12px">
          <input class="refin" placeholder="New department name" wire:model="newDept" wire:keydown.enter="addDept">
          <button class="btn" type="button" wire:click="addDept">Add</button>
        </div>
        @error('newDept')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror
      </div>
    </div>
  </div>
</div>
