<div>
  <p class="crumb">Requestor · {{ $departments ?: 'no departments assigned' }}</p>
  <div class="htop">
    <div>
      <h2>My PAN Requests</h2>
      <p>Requests you've raised for your registered departments. Submitted requests are read-only until returned for correction.</p>
    </div>
    <div class="spacer"></div>
    <a class="btn primary" href="{{ route('requests.create') }}" wire:navigate style="text-decoration:none">+ New PAN Request</a>
  </div>

  <div class="stats">
    <x-stat :value="$stats['total']" label="Total requests" />
    <x-stat :value="$stats['progress']" label="In progress" tone="warn" />
    <x-stat :value="$stats['returned']" label="Returned to you" tone="bad" />
    <x-stat :value="$stats['completed']" label="Completed" tone="ok" />
  </div>

  <div class="bar">
    <div class="search">⌕<input placeholder="Search by employee, reference no., or action type…" wire:model.live.debounce.300ms="search"></div>
    <button class="chip @if ($filter === 'all') on @endif" type="button" wire:click="$set('filter', 'all')">All</button>
    <button class="chip @if ($filter === 'progress') on @endif" type="button" wire:click="$set('filter', 'progress')">In progress</button>
    <button class="chip @if ($filter === 'completed') on @endif" type="button" wire:click="$set('filter', 'completed')">Completed</button>
  </div>

  @if ($pans->isEmpty())
  <x-empty-state title="No requests here" message="Nothing matches — raise a new PAN request or clear the search and filters." />
  @else
  <div class="card"><div class="twrap"><table>
    <thead><tr><th>Reference</th><th>Employee</th><th>Type of Action</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @foreach ($pans as $pan)
      <tr wire:key="pan-{{ $pan->id }}">
        <td class="ref">{{ $pan->reference }}</td>
        <td><div class="who"><b>{{ $pan->employee->name }}</b><small>{{ $pan->employee->employee_no }} · {{ $pan->employee->department->name }}</small></div></td>
        <td>{{ $pan->action_type->label() }}</td>
        <td>{{ $pan->submitted_at?->format('M j, Y') ?? '—' }}</td>
        <td><x-status-pill :status="$pan->status->value" /></td>
        <x-row-actions>
          @if ($pan->status === App\Enums\PanStatus::Draft)
            <a class="btn primary" href="{{ route('requests.edit', $pan->reference) }}" wire:navigate style="text-decoration:none">Edit</a>
            <x-kebab><x-kebab.item danger wire:click="deleteDraft({{ $pan->id }})" wire:confirm="Delete draft {{ $pan->reference }}? This cannot be undone.">Delete draft</x-kebab.item></x-kebab>
          @elseif ($pan->status === App\Enums\PanStatus::ReturnedToRequestor)
            <a class="btn ghost" href="{{ route('requests.show', $pan->reference) }}" wire:navigate style="text-decoration:none">View</a>
            <a class="btn primary" href="{{ route('requests.edit', $pan->reference) }}" wire:navigate style="text-decoration:none">Resubmit</a>
            <x-kebab><x-kebab.item danger wire:click="withdraw({{ $pan->id }})" wire:confirm="Withdraw {{ $pan->reference }}? It stays on record but leaves the workflow.">Withdraw request</x-kebab.item></x-kebab>
          @else
            <a class="btn ghost" href="{{ route('requests.show', $pan->reference) }}" wire:navigate style="text-decoration:none">View</a>
          @endif
        </x-row-actions>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>
  {{ $pans->links('components.pagination') }}
  @endif
</div>
