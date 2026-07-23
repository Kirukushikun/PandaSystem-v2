<div>
  <p class="crumb">Division Head · {{ $isDhHead ? 'DH Head — all departments' : ($departments ?: 'no departments assigned') }}</p>
  <div class="htop">
    <div><h2>Department Queue</h2>
      <p>Every PAN for your department, across all stages. You can only act while a request is awaiting your decision; afterwards it stays visible read-only — switch to "All stages" to keep monitoring it.</p></div>
  </div>

  <div class="stats">
    <x-stat :value="$stats['awaiting']" label="Awaiting your decision" tone="warn" />
    <x-stat :value="$stats['later']" label="In later stages" tone="acc" />
    <x-stat :value="$stats['completed']" label="Completed this month" tone="ok" />
  </div>

  @if ($isDhHead)
  <div class="note info"><span class="ic">i</span>You are the designated DH Head — this queue shows only confidential ("Manila") PANs, across all departments. Routine requests go to each department's own head.</div>
  @else
  <div class="note info"><span class="ic">i</span>Requests tagged confidential ("Manila") never appear in this queue — they are routed to the designated DH Head, even for your own department.</div>
  @endif

  <div class="bar">
    <div class="search">⌕<input placeholder="Search this department's requests…" wire:model.live.debounce.300ms="search"></div>
    <button class="chip @if ($filter === 'action') on @endif" type="button" wire:click="$set('filter', 'action')">Needs my action</button>
    <button class="chip @if ($filter === 'all') on @endif" type="button" wire:click="$set('filter', 'all')">All stages</button>
    <button class="chip @if ($filter === 'completed') on @endif" type="button" wire:click="$set('filter', 'completed')">Completed</button>
  </div>

  @if ($pans->isEmpty())
  <x-empty-state title="Nothing in this queue" message="No requests match — clear the search and filters, or check back once a requestor submits." />
  @else
  <div class="card"><div class="twrap"><table>
    <thead><tr><th>Reference</th><th>Employee</th><th>Type of Action</th><th>Requestor</th><th>Status</th><th>Decision</th></tr></thead>
    <tbody>
      @foreach ($pans as $pan)
      <tr wire:key="pan-{{ $pan->id }}">
        <td class="ref">{{ $pan->reference }}</td>
        <td><div class="who"><b>{{ $pan->employee->name }}</b><small>{{ $pan->employee->employee_no }}</small></div></td>
        <td>{{ $pan->action_type->label() }}</td>
        <td>{{ $pan->requestedBy->name }}</td>
        <td>
          @can('approveDivision', $pan)
            <x-status-pill status="with-division-head">Awaiting your decision</x-status-pill>
          @elsecan('confirm', $pan)
            <x-status-pill status="for-confirmation">For your confirmation (HR-prepared)</x-status-pill>
          @else
            <x-status-pill :status="$pan->status->value" />
          @endcan
        </td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('division.show', $pan->reference) }}" wire:navigate style="text-decoration:none">View</a>
          @can('approveDivision', $pan)
            <button class="btn primary" type="button" wire:click="approve({{ $pan->id }})" wire:confirm="Approve {{ $pan->reference }} and forward it to HR Preparation?">Approve</button>
            <x-kebab><x-kebab.item danger wire:click="startReason({{ $pan->id }}, 'return_to_requestor')">Return to Requestor…</x-kebab.item></x-kebab>
          @endcan
          @can('confirm', $pan)
            <button class="btn primary" type="button" wire:click="confirmPrepared({{ $pan->id }})" wire:confirm="Confirm the prepared PAN {{ $pan->reference }} and forward it to the HR Approver?">Confirm</button>
            <x-kebab><x-kebab.item danger wire:click="startReason({{ $pan->id }}, 'dispute')">Dispute…</x-kebab.item></x-kebab>
          @endcan
        </x-row-actions>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>
  {{ $pans->links('components.pagination') }}
  @endif

  <x-modal id="reason-modal" :open="$showModal" close="$set('showModal', false)" title="{{ $modalAction === 'dispute' ? 'Dispute' : 'Return to Requestor' }}{{ $modalPan ? ' — '.$modalPan->reference : '' }}">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="field"><label>Reason <em>*</em></label>
        <select wire:model="reason">
          <option value="">Select a reason…</option>
          @if ($modalAction === 'dispute')
          <option>Prepared values are incorrect</option>
          <option>Wrong effectivity date</option>
          <option>Employment status is wrong</option>
          <option>Needs revised action reference</option>
          @else
          <option>Incomplete supporting document</option>
          <option>Wrong employee selected</option>
          <option>Wrong type of action</option>
          <option>Needs stronger justification</option>
          @endif
          <option>Custom reason…</option>
        </select>
        @error('reason')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      <div class="field"><label>Details @if ($reason === 'Custom reason…')<em>*</em>@else (optional)@endif</label>
        <textarea rows="3" wire:model="details"></textarea>
        @error('details')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" wire:click="$set('showModal', false)">Cancel</button>
      <div class="spacer"></div>
      <button class="btn danger" type="button" wire:click="submitReason">
        {{ $modalAction === 'dispute' ? 'Send back to HR Preparer' : 'Return to Requestor' }}
      </button>
    </x-slot:footer>
  </x-modal>
</div>
