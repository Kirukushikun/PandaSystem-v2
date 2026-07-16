<div>
  <p class="crumb">Requestor · Broiler Operations, Hatchery</p>
  <div class="htop">
    <div>
      <h2>My PAN Requests</h2>
      <p>Requests you've raised for your registered departments. Submitted requests are read-only until returned for correction.</p>
    </div>
    <div class="spacer"></div>
    <a class="btn primary" href="{{ route('requests.create') }}" wire:navigate style="text-decoration:none">+ New PAN Request</a>
  </div>

  <div class="stats">
    <x-stat value="8" label="Total requests" />
    <x-stat value="3" label="In progress" tone="warn" />
    <x-stat value="1" label="Returned to you" tone="bad" />
    <x-stat value="4" label="Completed" tone="ok" />
  </div>

  <div class="bar">
    <x-search-bar placeholder="Search by employee, reference no., or action type…" />
    <x-chip on>All</x-chip><x-chip>In progress</x-chip><x-chip>Completed</x-chip>
  </div>

  <div class="card"><div class="twrap"><table>
    <thead><tr><th>Reference</th><th>Employee</th><th>Type of Action</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <tr><td class="ref">PAN-2026-00358</td>
        <td><div class="who"><b>R. Villanueva</b><small>EMP-10422 · Broiler Operations</small></div></td>
        <td>Regularization</td><td>—</td><td><x-status-pill status="draft" /></td>
        <x-row-actions>
          <a class="btn primary" href="{{ route('requests.create') }}" wire:navigate style="text-decoration:none">Edit</a>
          <x-kebab><x-kebab.item danger>Delete draft</x-kebab.item></x-kebab>
        </x-row-actions></tr>
      <tr><td class="ref">PAN-2026-00351</td>
        <td><div class="who"><b>J. Ramos</b><small>EMP-10387 · Hatchery</small></div></td>
        <td>Promotion</td><td>Jul 10, 2026</td><td><x-status-pill status="returned-to-requestor">Returned — replace attachment</x-status-pill></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('requests.show', 'PAN-2026-00351') }}" wire:navigate style="text-decoration:none">View</a>
          <a class="btn primary" href="{{ route('requests.create') }}" wire:navigate style="text-decoration:none">Resubmit</a>
          <x-kebab><x-kebab.item danger>Withdraw request</x-kebab.item></x-kebab>
        </x-row-actions></tr>
      <tr><td class="ref">PAN-2026-00347</td>
        <td><div class="who"><b>A. Santos</b><small>EMP-10301 · Broiler Operations</small></div></td>
        <td>Salary Alignment</td><td>Jul 8, 2026</td><td><x-status-pill status="with-division-head" /></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('requests.show', 'PAN-2026-00347') }}" wire:navigate style="text-decoration:none">View</a>
        </x-row-actions></tr>
      <tr><td class="ref">PAN-2026-00332</td>
        <td><div class="who"><b>C. Mercado</b><small>EMP-10119 · Hatchery</small></div></td>
        <td>Interim Allowance</td><td>Jul 2, 2026</td><td><x-status-pill status="in-preparation" /></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('requests.show', 'PAN-2026-00332') }}" wire:navigate style="text-decoration:none">View</a>
        </x-row-actions></tr>
      <tr><td class="ref">PAN-2026-00298</td>
        <td><div class="who"><b>L. Bautista</b><small>EMP-10077 · Broiler Operations</small></div></td>
        <td>Wage Order</td><td>Jun 19, 2026</td><td><x-status-pill status="filed" /></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('requests.show', 'PAN-2026-00298') }}" wire:navigate style="text-decoration:none">View</a>
        </x-row-actions></tr>
    </tbody>
  </table></div></div>
</div>
