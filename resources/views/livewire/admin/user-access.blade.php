{{-- Static sample body (K. Reyes) until the real build loads the user by {user}.
     The permission switches ARE live Livewire state ($perms) — flip them and they hold. --}}
<div>
  <p class="crumb">Administration</p>
  <div class="htop">
    <div><h2>User Access — K. Reyes</h2>
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
      </div>
    </div>
    <div class="pane">
      <h3>Departments &amp; profile</h3>
      <div class="pad">
        <p style="margin:10px 0 4px;font-size:11.5px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--ink-2)">Requests for</p>
        <span class="deptchip">Broiler Operations <button type="button" title="Remove" onclick="showToast('Department removed (UI scaffold — nothing is persisted yet).')">×</button></span>
        <span class="deptchip">Hatchery <button type="button" title="Remove" onclick="showToast('Department removed (UI scaffold — nothing is persisted yet).')">×</button></span>
        <button class="btn ghost" type="button" style="padding:4px 8px" onclick="showToast('Department picker arrives with the real build.')">+ Add</button>
        <p style="margin:14px 0 4px;font-size:11.5px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--ink-2)">Heads department(s)</p>
        <span class="deptchip">Broiler Operations <button type="button" title="Remove" onclick="showToast('Department removed (UI scaffold — nothing is persisted yet).')">×</button></span>
        <button class="btn ghost" type="button" style="padding:4px 8px" onclick="showToast('Department picker arrives with the real build.')">+ Add</button>
        <p class="locknote" style="margin:6px 0 0">Independent of requestor departments. Co-heads are supported — a department may have several heads.</p>
        <div class="formgrid" style="padding:14px 0 0;grid-template-columns:1fr 1fr">
          <div class="field"><label>Farm / Site</label><select><option>San Rafael Farm</option><option>Sta. Maria Feedmill</option><option>Main Office</option></select></div>
          <div class="field"><label>Job Position</label><input value="Farm Supervisor II"></div>
          <div class="field full"><label>E-signature (used on printed PANs)</label>
            <div class="sig">K. Reyes</div>
            <span class="hint"><b>Replace image…</b> PNG with transparent background recommended</span></div>
        </div>
        <div style="display:flex;justify-content:flex-end;padding-top:12px"><button class="btn primary" type="button" wire:click="save">Save changes</button></div>
      </div>
    </div>
  </div>
</div>
