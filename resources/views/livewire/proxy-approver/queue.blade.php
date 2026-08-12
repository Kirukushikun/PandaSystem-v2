<div>
  <p class="crumb">Proxy Approver</p>
  <div class="htop">
    <div><h2>Proxy Approver — Division Head</h2>
      <p>PANs stuck at a Division Head stage for longer than {{ $thresholdDays }} day(s) with no decision. Approving here forwards the PAN on the Division Head's behalf — the real head is notified, and your reason is permanently attached to the record.</p></div>
  </div>

  <div class="note info"><span class="ic">i</span>This is a temporary override, not a replacement for the Division Head — confidential ("Manila") PANs never appear here, and this feature can be switched off from Maintenance once backlogs clear.</div>

  <div class="stats">
    <x-stat :value="$stats['awaiting']" label="Awaiting proxy action" tone="warn" />
    <x-stat :value="$stats['dh']" label="Stalled — first approval" tone="acc" />
    <x-stat :value="$stats['confirmation']" label="Stalled — DH confirmation" tone="acc" />
  </div>

  <div class="bar">
    <div class="search">⌕<input placeholder="Search stalled PANs…" wire:model.live.debounce.300ms="search"></div>
    <x-filters-menu :open="$showFilters" :active="$this->hasActiveFilters()" clear="clearPanFilters">
      <x-pan-filters />
    </x-filters-menu>
  </div>

  @if ($pans->isEmpty())
  <x-empty-state title="Nothing stuck right now" message="No Division-Head-gated PAN has gone past the threshold — check back later, or adjust the threshold in Maintenance." />
  @else
  <div class="card"><div class="twrap"><table>
    <thead><tr><th>Reference</th><th>Employee</th><th>Department</th><th>Type of Action</th><th>Stage</th><th>Submitted</th><th></th></tr></thead>
    <tbody>
      @foreach ($pans as $pan)
      @php $stageIsDh = $pan->status === App\Enums\PanStatus::WithDivisionHead; @endphp
      <tr wire:key="pan-{{ $pan->id }}">
        <td class="ref">{{ $pan->reference }}</td>
        <td><div class="who"><b>{{ $pan->employee->name }}</b><small>{{ $pan->requestedByName() }}</small></div></td>
        <td>{{ $pan->department->name }}</td>
        <td>{{ $pan->action_type->label() }}</td>
        <td><x-status-pill :status="$pan->status->value" /></td>
        <td class="num">{{ $pan->submitted_at?->diffForHumans() ?? '—' }}</td>
        <x-row-actions>
          @if ($pan->wasProxyApproved())
          <span class="chip" title="Already proxy-approved once — eligible without waiting again">Prior proxy action</span>
          @endif
          @if ($stageIsDh)
          <button class="btn primary" type="button" wire:click="startReason({{ $pan->id }}, 'proxy_approve_dh')">Proxy-approve</button>
          @else
          <button class="btn primary" type="button" wire:click="startReason({{ $pan->id }}, 'proxy_approve_confirmation')">Proxy-confirm</button>
          @endif
        </x-row-actions>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>
  @endif

  <x-modal id="proxy-reason-modal" :open="$showModal" close="$set('showModal', false)" title="Proxy-approve on the Division Head's behalf{{ $modalPan ? ' — '.$modalPan->reference : '' }}">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="field"><label>Reason <em>*</em></label>
        <select wire:model="reason">
          <option value="">Select a reason…</option>
          <option>The approval waiting period took too long</option>
          <option>Division Head is on leave / unreachable</option>
          <option>Backlog clearance — HR-initiated</option>
          <option>Custom reason…</option>
        </select>
        @error('reason')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      <div class="field"><label>Details @if ($reason === 'Custom reason…')<em>*</em>@else (optional)@endif</label>
        <textarea rows="3" wire:model="details" maxlength="1000"></textarea>
        @error('details')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" wire:click="$set('showModal', false)">Cancel</button>
      <div class="spacer"></div>
      <button class="btn primary" type="button" wire:click="submitReason">Proxy-approve</button>
    </x-slot:footer>
  </x-modal>
</div>
