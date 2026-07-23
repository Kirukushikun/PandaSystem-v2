<div>
  <p class="crumb">Final Approver</p>
  <div class="htop">
    <div><h2>Final Sign-off</h2>
      <p>Act on requests individually or in bulk — select rows, or target all requests of one action type at once. Rejection returns a PAN to HR Preparation with a mandatory reason.</p></div>
  </div>

  <div class="stats">
    <x-stat :value="$stats['awaiting']" label="Awaiting final approval" tone="warn" />
    <x-stat :value="$stats['approved']" label="Approved this quarter" tone="ok" />
    <x-stat :value="$stats['rejected']" label="Rejected this quarter" tone="bad" />
  </div>

  @if ($pans->isEmpty())
  <x-empty-state title="All caught up" message="Nothing is waiting for your final sign-off. Approved PANs move on to HR Preparation for serving." />
  @else
  <div class="bulk">
    {{ count($selected) }} selected
    <div class="spacer"></div>
    <select wire:change="selectType($event.target.value)" style="border:1px solid var(--line);border-radius:7px;padding:6px 10px;font:inherit;background:var(--panel);color:var(--ink)">
      <option value="">Select all of type…</option>
      @foreach ($typeCounts as $type => $count)
      <option value="{{ $type }}">{{ App\Enums\ActionType::from($type)->label() }} ({{ $count }})</option>
      @endforeach
    </select>
    <button class="btn primary" type="button" wire:click="approveSelected" wire:confirm="Give final approval to the selected PAN(s)? Regularizations auto-finalize to Regular.">Approve selected</button>
    <button class="btn danger" type="button" wire:click="startReject">Reject selected…</button>
  </div>

  <div class="card"><div class="twrap"><table>
    <thead><tr><th style="width:36px"><input type="checkbox" wire:click="toggleAll" @checked(count($selected) === $pans->count()) aria-label="Select all"></th>
      <th>Reference</th><th>Employee</th><th>Type of Action</th><th>Effectivity</th><th>Basic Pay (From → To)</th><th></th></tr></thead>
    <tbody>
      @foreach ($pans as $pan)
      @php $basic = collect($pan->form?->action_reference ?? [])->firstWhere('field', 'basic'); @endphp
      <tr wire:key="pan-{{ $pan->id }}">
        <td><input type="checkbox" wire:model.live="selected" value="{{ $pan->id }}" aria-label="Select"></td>
        <td class="ref">{{ $pan->reference }}</td>
        <td><div class="who"><b>{{ $pan->employee->name }}</b><small>{{ $pan->employee->department->name }}</small></div></td>
        <td>{{ $pan->action_type->label() }}</td>
        <td>{{ $pan->form?->doe_from?->format('M j, Y') ?? '—' }}</td>
        <td class="num">{{ $basic && $basic['from'] !== $basic['to'] ? $basic['from'].' → '.$basic['to'] : '—' }}</td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('final-approval.show', $pan->reference) }}" wire:navigate style="text-decoration:none">View</a>
          <button class="btn primary" type="button" wire:click="approveOne({{ $pan->id }})" wire:confirm="Give final approval to {{ $pan->reference }}?">Approve</button>
          <x-kebab><x-kebab.item danger wire:click="startReject({{ $pan->id }})">Reject — back to HR Prep…</x-kebab.item></x-kebab>
        </x-row-actions>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>
  @endif

  <div class="note info" style="margin-top:14px"><span class="ic">i</span>Approving a <b>&nbsp;Regularization&nbsp;</b> automatically finalizes the employee's status as "Regular", overriding any tentative status set earlier.</div>

  <x-modal id="reject-modal" :open="$showModal" close="$set('showModal', false)" title="Reject — back to HR Preparation{{ count($rejectTargets) === 1 ? '' : ' ('.count($rejectTargets).' selected)' }}">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="field"><label>Reason <em>*</em></label>
        <select wire:model="reason">
          <option value="">Select a reason…</option>
          <option>Values need revision</option>
          <option>Wrong effectivity date</option>
          <option>Incorrect approver routing</option>
          <option>Needs supporting document</option>
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
      <button class="btn danger" type="button" wire:click="submitReject">Reject to HR Preparation</button>
    </x-slot:footer>
  </x-modal>
</div>
