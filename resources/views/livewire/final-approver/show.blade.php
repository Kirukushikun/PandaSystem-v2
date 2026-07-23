<div>
  <p class="crumb">Final Approver</p>
  <div class="htop">
    <div><h2>View Request — <span class="ref" style="font-size:18px">{{ $pan->reference }}</span></h2>
    <p>{{ $form ? 'Fully prepared and HR-approved.' : 'The request as submitted.' }}</p></div>
    <div class="spacer"></div>
    @can('approveFinal', $pan)
      <x-status-pill status="for-final-approval">Awaiting final approval</x-status-pill>
    @else
      <x-status-pill :status="$pan->status->value" />
    @endcan
  </div>

  <x-stage-tracker :stages="['Submitted','Division approved','HR prepared','DH confirmed','HR approved','Final Approval']" current="Final Approval" />

  @foreach ($pan->returns->reverse() as $return)
  <div class="note warn"><span class="ic">!</span><span><b>{{ $return->from_status->label() }} → returned by {{ $return->returnedBy->name }}:</b>&nbsp;{{ $return->reason }}@if ($return->details) — {{ $return->details }}@endif <small style="color:var(--ink-3)">({{ $return->created_at->format('M j, Y') }})</small></span></div>
  @endforeach

  <div class="card">
    {{-- Same "Request details" block shown to earlier stages — identical contents at every stage --}}
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
      :attachments="$pan->attachments"
      :pan-reference="$pan->reference" />

    @if ($form !== null)
    <x-pan.prepared-details
      :prepared-by="$form->preparedBy->name.' · '.$form->updated_at->format('M j, Y')"
      :date-hired="$form->date_hired?->format('M j, Y') ?? '—'"
      :employment-status="$form->employment_status?->label() ?? '—'"
      :effectivity="($form->doe_from?->format('M j, Y') ?? '—').' — '.($form->doe_to?->format('M j, Y') ?? 'open-ended')"
      :wage-no="$pan->action_type->requiresWageNumber() ? ($form->wage_no ?? '—') : null"
      :hr-approved-by="$pan->hrApprover ? $pan->hrApprover->name : null"
      ref-heading="Action Reference — prepared changes"
      :rows="$rows"
      :remarks="$form->remarks" />
    @endif

    @if ($pan->action_type->autoFinalizesToRegular() && $pan->status === App\Enums\PanStatus::ForFinalApproval)
    <div class="note info" style="margin:14px 18px"><span class="ic">i</span>Approving this Regularization finalizes the employee's status as "Regular" automatically.</div>
    @endif
    <div class="formfoot">
      <a class="btn" href="{{ route('final-approval.queue') }}" wire:navigate style="text-decoration:none">← Back to queue</a>
      <div class="spacer"></div>
      @if ($form)
      <x-print-btn href="{{ route('pan.print', $pan->reference) }}" />
      @endif
      @can('approveFinal', $pan)
        <button class="btn danger" type="button" wire:click="$set('showModal', true)">Reject — back to HR Preparation…</button>
        <button class="btn primary" type="button" wire:click="approve" wire:confirm="Give final approval to {{ $pan->reference }}?{{ $pan->action_type->autoFinalizesToRegular() ? ' Employment status auto-finalizes to Regular.' : '' }}">Give Final Approval</button>
      @endcan
    </div>
  </div>

  <x-modal id="reject-modal" :open="$showModal" close="$set('showModal', false)" title="Reject — back to HR Preparation — {{ $pan->reference }}">
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
