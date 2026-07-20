<div>
  <p class="crumb">HR Approver</p>
  <div class="htop">
    <div><h2>HR Approval Queue</h2>
      <p>PANs confirmed by the Division Head and awaiting HR-level approval. No confidentiality distinction applies at this stage.</p></div>
  </div>

  <div class="stats">
    <x-stat :value="$stats['awaiting']" label="Awaiting HR approval" tone="warn" />
    <x-stat :value="$stats['withFinal']" label="With Final Approver" tone="acc" />
    <x-stat :value="$stats['approved']" label="Approved this month" tone="ok" />
  </div>

  <div class="bar">
    <div class="search">⌕<input placeholder="Search PANs awaiting HR approval…" wire:model.live.debounce.300ms="search"></div>
    <button class="chip @if ($filter === 'awaiting') on @endif" type="button" wire:click="$set('filter', 'awaiting')">Awaiting approval</button>
    <button class="chip @if ($filter === 'later') on @endif" type="button" wire:click="$set('filter', 'later')">Further along</button>
  </div>

  @if ($pans->isEmpty())
  <x-empty-state title="Nothing awaiting HR approval" message="No PANs match — clear the search, or wait for the next Division Head confirmation." />
  @else
  <div class="card"><div class="twrap"><table>
    <thead><tr><th>Reference</th><th>Employee</th><th>Type of Action</th><th>Prepared by</th><th>Basic Pay (From → To)</th><th>Decision</th></tr></thead>
    <tbody>
      @foreach ($pans as $pan)
      @php $basic = collect($pan->form?->action_reference ?? [])->firstWhere('field', 'basic'); @endphp
      <tr wire:key="pan-{{ $pan->id }}">
        <td class="ref">{{ $pan->reference }}</td>
        <td><div class="who"><b>{{ $pan->employee->name }}</b><small>{{ $pan->employee->department->name }}</small></div></td>
        <td>{{ $pan->action_type->label() }}</td>
        <td>{{ $pan->form?->preparedBy->name ?? '—' }}</td>
        <td class="num">{{ $basic && $basic['from'] !== $basic['to'] ? $basic['from'].' → '.$basic['to'] : '—' }}</td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('hr-approval.show', $pan->reference) }}" wire:navigate style="text-decoration:none">View</a>
          @can('approveHr', $pan)
            <button class="btn primary" type="button" wire:click="approve({{ $pan->id }})" wire:confirm="Approve {{ $pan->reference }} and forward it to the Final Approver?">Approve</button>
            <x-kebab><x-kebab.item danger wire:click="startReason({{ $pan->id }})">Return to HR Preparer…</x-kebab.item></x-kebab>
          @endcan
          @if ($pan->form)
            <x-print-btn href="{{ route('pan.print', $pan->reference) }}" />
          @endif
        </x-row-actions>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>
  {{ $pans->links('components.pagination') }}
  @endif

  <div class="note info" style="margin-top:14px"><span class="ic">i</span>Returning a PAN here sends it one step back to the HR Preparer with a mandatory reason — not all the way back to the Requestor.</div>

  <x-modal id="reason-modal" :open="$showModal" close="$set('showModal', false)" title="Return to HR Preparer{{ $modalPan ? ' — '.$modalPan->reference : '' }}">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="field"><label>Reason <em>*</em></label>
        <select wire:model="reason">
          <option value="">Select a reason…</option>
          <option>Prepared values are incorrect</option>
          <option>Wage number mismatch</option>
          <option>Wrong effectivity date</option>
          <option>Missing allowance line</option>
          <option>Custom reason…</option>
        </select>
        @error('reason')<span class="hint" style="color:var(--bad)">{{ $message }}</span>@enderror</div>
      <div class="field"><label>Details @if ($reason === 'Custom reason…')<em>*</em>@else (optional)@endif</label>
        <textarea rows="3" wire:model="details"></textarea>
        @error('details')<span class="hint" style="color:var(--bad)">{{ $message }}</span>@enderror</div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" wire:click="$set('showModal', false)">Cancel</button>
      <div class="spacer"></div>
      <button class="btn danger" type="button" wire:click="submitReason">Return to HR Preparer</button>
    </x-slot:footer>
  </x-modal>
</div>
