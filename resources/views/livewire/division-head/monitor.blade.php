<div>
  <p class="crumb">Division Head · {{ $isDhHead ? 'DH Head — all departments' : ($departments ?: 'no departments assigned') }}</p>
  <div class="htop">
    <div><h2>Monitor Department</h2>
      <p>Every PAN raised under your department, start to finish — view-only, so it never mixes with what actually needs your decision. Act on requests from the Department Queue.</p></div>
  </div>

  <div class="stats">
    <x-stat :value="$stats['total']" label="Total requests" />
    <x-stat :value="$stats['progress']" label="In progress" tone="warn" />
    <x-stat :value="$stats['completed']" label="Completed" tone="ok" />
  </div>

  @if ($isDhHead)
  <div class="note info"><span class="ic">i</span>You are the designated DH Head — this view shows only confidential ("Manila") PANs, across all departments.</div>
  @else
  <div class="note info"><span class="ic">i</span>Requests tagged confidential ("Manila") never appear here — they are routed to the designated DH Head, even for your own department.</div>
  @endif

  <div class="bar">
    <div class="search">⌕<input placeholder="Search this department's requests…" wire:model.live.debounce.300ms="search"></div>
    <button class="chip @if ($filter === 'all') on @endif" type="button" wire:click="$set('filter', 'all')">All</button>
    <button class="chip @if ($filter === 'progress') on @endif" type="button" wire:click="$set('filter', 'progress')">In progress</button>
    <button class="chip @if ($filter === 'completed') on @endif" type="button" wire:click="$set('filter', 'completed')">Completed</button>
  </div>

  @if ($pans->isEmpty())
  <x-empty-state title="Nothing to monitor yet" message="No requests match — clear the search and filters, or check back once one moves past submission." />
  @else
  <div class="card"><div class="twrap"><table>
    <thead><tr><th>Reference</th><th>Employee</th><th>Type of Action</th><th>Requestor</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @foreach ($pans as $pan)
      <tr wire:key="pan-{{ $pan->id }}">
        <td class="ref">{{ $pan->reference }}</td>
        <td><div class="who"><b>{{ $pan->employee->name }}</b><small>{{ $pan->employee->employee_no }}</small></div></td>
        <td>{{ $pan->action_type->label() }}</td>
        <td>{{ $pan->requestedBy->name }}</td>
        <td><x-status-pill :status="$pan->status->value" /></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('division.show', $pan->reference) }}" wire:navigate style="text-decoration:none">View</a>
        </x-row-actions>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>
  {{ $pans->links('components.pagination') }}
  @endif
</div>
