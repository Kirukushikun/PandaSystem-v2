<div>
  <p class="crumb">HR Preparation · signed in as {{ $isHrHead ? 'HR Head Preparer' : 'HR Preparer' }}</p>
  <div class="htop">
    <div><h2>View Request — <span class="ref" style="font-size:18px">{{ $pan->reference }}</span></h2>
    <p>{{ $form ? 'The request with its prepared PAN details.' : 'The request as submitted — no PAN has been prepared yet.' }}</p></div>
    <div class="spacer"></div>
    @php
      $bounced = $pan->status === App\Enums\PanStatus::InPreparation
          && $pan->latestReturn
          && $pan->latestReturn->to_status === App\Enums\PanStatus::InPreparation
          ? $pan->latestReturn->action
          : null;
    @endphp
    @if ($bounced === 'dispute')
      <x-status-pill tone="ret">Disputed — by Division Head</x-status-pill>
    @elseif ($bounced === 'reject_final')
      <x-status-pill tone="ret">Rejected — by Final Approver</x-status-pill>
    @else
      <x-status-pill :status="$pan->status->value" />
    @endif
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
      :rows="$rows"
      :remarks="$form->remarks" />

    @can('patchMissingPrintDetails', $pan)
      @if (count($emptyFromFields) > 0 || $needsDivisionHead)
      <div class="sect" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <span>Missing print detail(s) <small style="font-weight:400;color:var(--ink-3)">— temporary fix for PANs created before this was corrected</small></span>
        @if (! $showQuickFix)
        <button class="btn ghost" type="button" wire:click="startQuickFix" style="text-transform:none;letter-spacing:normal">Fill in missing values</button>
        @endif
      </div>
      @if ($showQuickFix)
      <div class="formgrid" style="padding-top:10px">
        @foreach ($emptyFromFields as $field => $label)
        <div class="field"><label>{{ $label }}</label>
          <input wire:model="emptyFromValues.{{ $field }}" placeholder="—" maxlength="255">
          @error('emptyFromValues.'.$field)<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror
        </div>
        @endforeach
        @if ($needsDivisionHead)
        <div class="field"><label>Recommended By — Division Head</label>
          <select wire:model="selectedDivisionHead">
            <option value="">— Select —</option>
            @foreach ($divisionHeadCandidates as $candidate)
            <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
            @endforeach
          </select>
          <span class="hint">Never recorded for this PAN — print's "Recommended By" line otherwise stays blank.</span>
          @error('selectedDivisionHead')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror
        </div>
        @endif
      </div>
      @endif
      @endif
    @endcan
    @endif

    <div class="formfoot">
      <a class="btn" href="{{ route('preparation.queue') }}" wire:navigate style="text-decoration:none">← Back to queue</a>
      @if ($showQuickFix)
      <button class="btn" type="button" wire:click="$set('showQuickFix', false)">Cancel</button>
      @endif
      <div class="spacer"></div>
      @if ($form)
      <x-print-btn href="{{ route('pan.print', $pan->reference) }}" />
      @endif
      @if ($showQuickFix)
      <button class="btn primary" type="button" wire:click="saveQuickFix">Save missing detail(s)</button>
      @endif
      @can('tag', $pan)
      <a class="btn primary" href="{{ route('preparation.edit', $pan->reference) }}" wire:navigate style="text-decoration:none">Tag &amp; Prepare</a>
      @endcan
      @can('prepare', $pan)
      <a class="btn primary" href="{{ route('preparation.edit', $pan->reference) }}" wire:navigate style="text-decoration:none">{{ $pan->status === App\Enums\PanStatus::ReturnedToPreparer ? 'Revise in Preparation Form' : 'Open in Preparation Form' }}</a>
      @endcan
    </div>
  </div>
</div>
