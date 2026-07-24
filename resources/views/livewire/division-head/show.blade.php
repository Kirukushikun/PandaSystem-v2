{{-- ONE view, two render states (per the mockup note): without a prepared PAN it shows the
     request details only; with one, the PAN details are appended below as an extension. --}}
<div>
  <p class="crumb">Division Head · {{ $departments ?: 'no departments assigned' }}</p>

  <div class="htop">
    <div><h2>View Request — <span class="ref" style="font-size:18px">{{ $pan->reference }}</span></h2>
    @can('approveDivision', $pan)
    <p>The request exactly as submitted, read-only. No PAN has been prepared yet, so only the request details are shown.</p>
    @elsecan('confirm', $pan)
    <p>This request already has a prepared PAN, so its details are shown below the request as an extension.</p>
    @else
    <p>The request exactly as submitted, read-only. The status pill shows where it currently is in the workflow.</p>
    @endcan</div>
    <div class="spacer"></div>
    @can('approveDivision', $pan)
      <x-status-pill status="with-division-head">Awaiting your decision</x-status-pill>
    @elsecan('confirm', $pan)
      <x-status-pill status="for-confirmation">For your confirmation</x-status-pill>
    @else
      <x-status-pill :status="$pan->status->value" />
    @endcan
  </div>

  <x-stage-tracker :stages="$stages" :current="$current" />

  <x-pan.return-history :returns="$pan->returns" />

  <div class="card">
    <x-pan.request-details
      :sect="$form !== null"
      :employee="$pan->employee->name"
      :employee-id="$pan->employee->employee_no"
      :department="$pan->employee->department->name"
      :action="$pan->action_type->label()"
      :requested-by="$pan->requestedBy->name"
      :submitted="$pan->submitted_at?->format('M j, Y · H:i') ?? '—'"
      :justification="$pan->justification ?? '—'"
      :justification-rows="$form !== null ? 2 : 3"
      :attachments="$pan->attachments"
      :pan-reference="$pan->reference" />

    @if ($form !== null)
    {{-- extension: appears only once HR has prepared the PAN --}}
    <x-pan.prepared-details
      :prepared-by="$form->preparedBy->name.' · '.$form->updated_at->format('M j, Y')"
      :date-hired="$form->date_hired?->format('M j, Y') ?? '—'"
      :employment-status="$form->employment_status?->label() ?? '—'"
      :effectivity="($form->doe_from?->format('M j, Y') ?? '—').' — '.($form->doe_to?->format('M j, Y') ?? 'open-ended')"
      :wage-no="$pan->action_type->requiresWageNumber() ? ($form->wage_no ?? '—') : null"
      :rows="$rows"
      :remarks="$form->remarks" />
    @endif

    <div class="formfoot">
      <a class="btn" href="{{ route('division.queue') }}" wire:navigate style="text-decoration:none">← Back to queue</a>
      <div class="spacer"></div>
      @can('approveDivision', $pan)
        <button class="btn danger" type="button" wire:click="$set('showModal', true)">Return…</button>
        <button class="btn primary" type="button" wire:click="approve" wire:confirm="Approve {{ $pan->reference }} and forward it to HR Preparation?">Approve</button>
      @endcan
      @can('confirm', $pan)
        <button class="btn danger" type="button" wire:click="$set('showModal', true)">Dispute…</button>
        <button class="btn primary" type="button" wire:click="confirmPrepared" wire:confirm="Confirm the prepared PAN {{ $pan->reference }} and forward it to the HR Approver?">Confirm</button>
      @endcan
    </div>
  </div>

  @php $disputing = $pan->status === App\Enums\PanStatus::ForConfirmation; @endphp
  <x-modal id="reason-modal" :open="$showModal" close="$set('showModal', false)" title="{{ $disputing ? 'Dispute' : 'Return to Requestor' }} — {{ $pan->reference }}">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="field"><label>Reason <em>*</em></label>
        <select wire:model="reason">
          <option value="">Select a reason…</option>
          @if ($disputing)
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
        <textarea rows="3" wire:model="details" maxlength="1000"></textarea>
        @error('details')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" wire:click="$set('showModal', false)">Cancel</button>
      <div class="spacer"></div>
      <button class="btn danger" type="button" wire:click="submitReason">
        {{ $disputing ? 'Send back to HR Preparer' : 'Return to Requestor' }}
      </button>
    </x-slot:footer>
  </x-modal>
</div>
