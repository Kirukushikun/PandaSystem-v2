<div>
  <p class="crumb">Administration</p>
  <div class="htop">
    <div><h2>User Access — {{ $account->name }}</h2>
      <p>Stage permissions, department assignments, and flags are all independent — one person can hold any combination.</p></div>
    <div class="spacer"></div>
    <a class="btn" href="{{ route('admin.users') }}" wire:navigate style="text-decoration:none">← Back to users</a>
  </div>

  <div class="twocol">
    <div class="pane">
      <h3>Stage permissions &amp; flags</h3>
      <div class="pad">
        @foreach ([
          'requestor'      => ['Requestor', null],
          'division_head'  => ['Division Head', null],
          'hr_preparer'    => ['HR Preparer', null],
          'hr_approver'    => ['HR Approver', null],
          'final_approver' => ['Final Approver', null],
          'hr_head'        => ['HR Head', 'Manila PANs at HR Preparation'],
          'dh_head'        => ['DH Head', 'Manila PANs at division stage, all departments'],
          'admin'          => ['Admin', 'administration screens'],
        ] as $key => [$label, $note])
        {{-- DH Head (formerly "Confidentiality Approver"): flips their Division Head queue to ONLY Manila-tagged PANs, all departments --}}
        <div class="permrow" @if ($key === 'hr_head') style="border-top:2px solid var(--line);margin-top:4px;padding-top:12px" @endif>
          <span>{{ $label }} @if ($note)<small style="color:var(--ink-3)">({{ $note }})</small>@endif</span>
          <span class="tog @if ($perms[$key]) on @endif" role="switch" aria-checked="{{ $perms[$key] ? 'true' : 'false' }}" tabindex="0"
                wire:click="togglePerm('{{ $key }}')" wire:keydown.enter="togglePerm('{{ $key }}')"></span>
        </div>
        @endforeach
        <p class="locknote" style="margin:10px 0 0">Changes apply on <b>Save changes</b> — nothing is stored while toggling.</p>
      </div>
    </div>
    <div class="pane">
      <h3>Departments &amp; profile</h3>
      <div class="pad">
        <p style="margin:10px 0 4px;font-size:11.5px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--ink-2)">Requests for</p>
        @foreach ($requestsFor as $deptId)
        <span class="deptchip" wire:key="req-{{ $deptId }}">{{ $departments->firstWhere('id', $deptId)?->name }} <button type="button" title="Remove" wire:click="removeDepartment('requestsFor', {{ $deptId }})">×</button></span>
        @endforeach
        <select style="border:1px solid var(--line);border-radius:7px;background:var(--panel);color:var(--ink);font:inherit;font-size:12.5px;padding:4px 8px"
                wire:change="addDepartment('requestsFor', $event.target.value)">
          <option value="">+ Add…</option>
          @foreach ($departments as $department)
          @unless (in_array($department->id, $requestsFor))<option value="{{ $department->id }}">{{ $department->name }}</option>@endunless
          @endforeach
        </select>

        <p style="margin:14px 0 4px;font-size:11.5px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--ink-2)">Heads department(s)</p>
        @foreach ($heads as $deptId)
        <span class="deptchip" wire:key="head-{{ $deptId }}">{{ $departments->firstWhere('id', $deptId)?->name }} <button type="button" title="Remove" wire:click="removeDepartment('heads', {{ $deptId }})">×</button></span>
        @endforeach
        <select style="border:1px solid var(--line);border-radius:7px;background:var(--panel);color:var(--ink);font:inherit;font-size:12.5px;padding:4px 8px"
                wire:change="addDepartment('heads', $event.target.value)">
          <option value="">+ Add…</option>
          @foreach ($departments as $department)
          @unless (in_array($department->id, $heads))<option value="{{ $department->id }}">{{ $department->name }}</option>@endunless
          @endforeach
        </select>
        <p class="locknote" style="margin:6px 0 0">Independent of requestor departments. Co-heads are supported — a department may have several heads.</p>

        <div class="formgrid" style="padding:14px 0 0;grid-template-columns:1fr 1fr">
          <div class="field"><label>Farm / Site</label>
            <select wire:model="farmId">
              @foreach ($farms as $farm)
              <option value="{{ $farm->id }}">{{ $farm->name }}</option>
              @endforeach
            </select>
            @error('farmId')<span class="hint" style="color:var(--bad)">{{ $message }}</span>@enderror</div>
          <div class="field"><label>Job Position</label><input wire:model="position">
            @error('position')<span class="hint" style="color:var(--bad)">{{ $message }}</span>@enderror</div>
          <div class="field full"><label>E-signature (used on printed PANs)</label>
            @if ($account->esign_path)
            <div class="sig"><img src="{{ route('user.esign', $account) }}" alt="{{ $account->name }}" style="max-height:48px;width:auto;margin:0"></div>
            @else
            <div class="sig">{{ $account->name }}</div>
            @endif
            <label class="hint" style="cursor:pointer"><b>{{ $esign ? $esign->getClientOriginalName().' — ready, press Save' : 'Replace image…' }}</b> PNG with transparent background recommended
              <input type="file" accept="image/png" wire:model="esign" style="display:none"></label>
            @error('esign')<span class="hint" style="color:var(--bad)">{{ $message }}</span>@enderror</div>
        </div>
        <div style="display:flex;justify-content:flex-end;padding-top:12px"><button class="btn primary" type="button" wire:click="save">Save changes</button></div>
      </div>
    </div>
  </div>
</div>
