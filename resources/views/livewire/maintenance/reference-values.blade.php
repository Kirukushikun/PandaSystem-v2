{{-- Live lists: add appends a deletable 0-use value; × only works when not in use. --}}
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
        @foreach ($farms as $i => $farm)
        <div class="refrow" wire:key="farm-{{ $farm['name'] }}">
          <span>{{ $farm['name'] }}</span><small>{{ $farm['note'] }}</small>
          @if ($farm['inUse'])
          <button class="rowdel" type="button" disabled title="In use — cannot delete" style="opacity:.35;cursor:not-allowed">×</button>
          @else
          <button class="rowdel" type="button" title="Delete value" wire:click="removeFarm({{ $i }})">×</button>
          @endif
        </div>
        @endforeach
        <div style="display:flex;gap:8px;margin-top:12px">
          <input class="refin" placeholder="New farm / site name" wire:model="newFarm" wire:keydown.enter="addFarm">
          <button class="btn" type="button" wire:click="addFarm">Add</button>
        </div>
      </div>
    </div>
    <div class="pane">
      <h3>Departments</h3>
      <div class="pad">
        @foreach ($depts as $i => $dept)
        <div class="refrow" wire:key="dept-{{ $dept['name'] }}">
          <span>{{ $dept['name'] }}</span><small>{{ $dept['note'] }}</small>
          @if ($dept['inUse'])
          <button class="rowdel" type="button" disabled title="In use — cannot delete" style="opacity:.35;cursor:not-allowed">×</button>
          @else
          <button class="rowdel" type="button" title="Delete value" wire:click="removeDept({{ $i }})">×</button>
          @endif
        </div>
        @endforeach
        <div style="display:flex;gap:8px;margin-top:12px">
          <input class="refin" placeholder="New department name" wire:model="newDept" wire:keydown.enter="addDept">
          <button class="btn" type="button" wire:click="addDept">Add</button>
        </div>
      </div>
    </div>
  </div>
</div>
