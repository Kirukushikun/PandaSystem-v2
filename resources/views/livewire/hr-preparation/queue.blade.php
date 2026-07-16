<div>
  <p class="crumb">HR Preparation · signed in as HR Head Preparer</p>
  <div class="htop">
    <div><h2>Preparation Queue</h2>
      <p>Division-approved PANs awaiting paperwork. Each must be tagged for confidentiality before preparation can begin.</p></div>
    <div class="spacer"></div>
    <a class="btn" href="{{ route('employees.index') }}" wire:navigate style="text-decoration:none">Employees</a>
  </div>

  <div class="stats">
    <x-stat value="5" label="Awaiting preparation" tone="warn" />
    <x-stat value="2" label="Returned for resolution" tone="bad" />
    <x-stat value="2" label="Approved — to serve / file" tone="acc" />
  </div>

  <div class="bar">
    <x-search-bar placeholder="Search PANs awaiting HR paperwork…" />
    <x-chip on>To prepare</x-chip><x-chip>In approval</x-chip><x-chip>To serve / file</x-chip>
  </div>

  <div class="card"><div class="twrap"><table>
    <thead><tr><th>Tag</th><th>Reference</th><th>Employee</th><th>Type of Action</th><th>Department</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <tr><td><x-tag-dot /></td><td class="ref">PAN-2026-00347</td>
        <td><div class="who"><b>A. Santos</b><small>EMP-10301</small></div></td>
        <td>Salary Alignment</td><td>Broiler Operations</td><td><x-status-pill status="in-preparation">Tag &amp; prepare</x-status-pill></td>
        {{-- No print icon: nothing to print until a PAN is prepared (no disabled buttons — the pill explains the state). --}}
        <x-row-actions>
          <a class="btn ghost" href="{{ route('preparation.show', 'PAN-2026-00347') }}" wire:navigate style="text-decoration:none">View</a>
          <a class="btn primary" href="{{ route('preparation.edit', 'PAN-2026-00347') }}" wire:navigate style="text-decoration:none">Open</a>
        </x-row-actions></tr>
      <tr><td><x-tag-dot tag="manila" /></td><td class="ref">PAN-2026-00341</td>
        <td><div class="who"><b>N. Fernandez</b><small>EMP-10490</small></div></td>
        <td>Change of Position</td><td>Corporate Office</td><td><x-status-pill status="in-preparation">Preparing — confidential</x-status-pill></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('preparation.show', 'PAN-2026-00341') }}" wire:navigate style="text-decoration:none">View</a>
          <a class="btn primary" href="{{ route('preparation.edit', 'PAN-2026-00341') }}" wire:navigate style="text-decoration:none">Continue</a>
          <x-print-btn onclick="showToast('Print view arrives in scaffold step 9.')" />
        </x-row-actions></tr>
      <tr><td><x-tag-dot tag="tarlac" /></td><td class="ref">PAN-2026-00338</td>
        <td><div class="who"><b>S. Lim</b><small>EMP-10233</small></div></td>
        <td>Wage Order</td><td>Feedmill</td><td><x-status-pill status="returned-to-preparer" /></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('preparation.show', 'PAN-2026-00338') }}" wire:navigate style="text-decoration:none">View</a>
          <a class="btn primary" href="{{ route('preparation.edit', 'PAN-2026-00338') }}" wire:navigate style="text-decoration:none">Revise</a>
          <x-print-btn onclick="showToast('Print view arrives in scaffold step 9.')" />
          <x-kebab><x-kebab.item danger>Void / Delete…</x-kebab.item></x-kebab>
        </x-row-actions></tr>
      <tr><td><x-tag-dot tag="tarlac" /></td><td class="ref">PAN-2026-00311</td>
        <td><div class="who"><b>D. Torres</b><small>EMP-10064</small></div></td>
        <td>Regularization</td><td>Broiler Operations</td><td><x-status-pill status="approved">Approved</x-status-pill></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('preparation.show', 'PAN-2026-00311') }}" wire:navigate style="text-decoration:none">View</a>
          <button class="btn primary" type="button" onclick="showToast('Marked as Served (UI scaffold — nothing is persisted yet).')">Mark Served</button>
          <x-print-btn onclick="showToast('Print view arrives in scaffold step 9.')" />
          <x-kebab><x-kebab.item>Mark Unserved…<small>AWOL, resigned, terminated, or custom</small></x-kebab.item></x-kebab>
        </x-row-actions></tr>
      <tr><td><x-tag-dot tag="tarlac" /></td><td class="ref">PAN-2026-00298</td>
        <td><div class="who"><b>L. Bautista</b><small>EMP-10077</small></div></td>
        <td>Wage Order</td><td>Broiler Operations</td><td><x-status-pill status="filed" /></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('preparation.show', 'PAN-2026-00298') }}" wire:navigate style="text-decoration:none">View</a>
          <x-print-btn onclick="showToast('Print view arrives in scaffold step 9.')" />
          <x-kebab><x-kebab.item>Start Follow-up PAN<small>New cycle for this employee</small></x-kebab.item></x-kebab>
        </x-row-actions></tr>
    </tbody>
  </table></div></div>
  <p class="locknote" style="margin:8px 2px 0">Tag colors — <x-tag-dot tag="manila" /> Manila (confidential) · <x-tag-dot tag="tarlac" /> Tarlac (routine) · <x-tag-dot /> untagged. Visible to HR Head Preparers only. Once tagged Manila, ordinary preparers can no longer open the record.</p>
</div>
