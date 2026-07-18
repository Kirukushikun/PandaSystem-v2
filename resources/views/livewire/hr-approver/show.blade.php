<div>
  <p class="crumb">HR Approver</p>
  <div class="htop">
    <div><h2>View Request — <span class="ref" style="font-size:18px">{{ $pan->reference }}</span></h2>
    <p>{{ $form ? 'The request together with its HR-prepared details.' : 'The request as submitted.' }}</p></div>
    <div class="spacer"></div>
    @can('approveHr', $pan)
      <x-status-pill status="for-hr-approval">Awaiting HR approval</x-status-pill>
    @else
      <x-status-pill :status="$pan->status->value" />
    @endcan
  </div>

  <x-stage-tracker :stages="['Submitted','Division approved','HR prepared','DH confirmed','HR Approval','Final Approval']" current="HR Approval" />

  @foreach ($pan->returns->reverse() as $return)
  <div class="note warn"><span class="ic">!</span><span><b>{{ $return->from_status->label() }} → returned by {{ $return->returnedBy->name }}:</b>&nbsp;{{ $return->reason }}@if ($return->details) — {{ $return->details }}@endif <small style="color:var(--ink-3)">({{ $return->created_at->format('M j, Y') }})</small></span></div>
  @endforeach

  <div class="card">
    {{-- Same "Request details" block shown to the HR Preparer — identical contents on both ends --}}
    <x-pan.request-details
      :sect="$form !== null"
      :employee="$pan->employee->name"
      :employee-id="$pan->employee->employee_no"
      :department="$pan->employee->department->name"
      :action="$pan->action_type->label()"
      :requested-by="$pan->requestedBy?->name ?? 'HR-originated'"
      :submitted="$pan->submitted_at?->format('M j, Y · H:i') ?? '—'"
      :justification="$pan->justification ?? '—'"
      :justification-rows="2"
      :document="$pan->attachment_path ? basename($pan->attachment_path) : null"
      :document-url="$pan->attachment_path ? route('pan.attachment', $pan->reference) : null" />

    @if ($form !== null)
    <x-pan.prepared-details
      :prepared-by="$form->preparedBy->name.' · '.$form->updated_at->format('M j, Y')"
      :date-hired="$form->date_hired?->format('M j, Y') ?? '—'"
      :employment-status="$form->employment_status?->label() ?? '—'"
      :effectivity="($form->doe_from?->format('M j, Y') ?? '—').' — '.($form->doe_to?->format('M j, Y') ?? 'open-ended')"
      :wage-no="$pan->action_type->requiresWageNumber() ? ($form->wage_no ?? '—') : null"
      ref-heading="Action Reference — prepared changes"
      :rows="$rows"
      :remarks="$form->remarks" />
    @endif

    <div class="formfoot">
      <a class="btn" href="{{ route('hr-approval.queue') }}" wire:navigate style="text-decoration:none">← Back to queue</a>
      <div class="spacer"></div>
      @if ($form)
      <x-print-btn href="{{ route('pan.print', $pan->reference) }}" />
      @endif
      @can('approveHr', $pan)
        <button class="btn danger" type="button" data-modal-open="reason-modal">Return to HR Preparer…</button>
        <button class="btn primary" type="button" wire:click="approve" wire:confirm="Approve {{ $pan->reference }} and forward it to the Final Approver?">Approve</button>
      @endcan
    </div>
  </div>

  <x-modal id="reason-modal" title="Return to HR Preparer — {{ $pan->reference }}">
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
      <button class="btn" type="button" data-close>Cancel</button>
      <div class="spacer"></div>
      <button class="btn danger" type="button" wire:click="submitReason">Return to HR Preparer</button>
    </x-slot:footer>
  </x-modal>
</div>
