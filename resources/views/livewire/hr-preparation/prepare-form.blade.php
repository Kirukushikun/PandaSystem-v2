<div>
  <p class="crumb">HR Preparation · signed in as {{ $isHrHead ? 'HR Head Preparer' : 'HR Preparer' }}</p>
  <div class="htop"><div><h2>Prepare PAN — {{ $pan->reference }} · {{ $pan->employee->name }}</h2>
    <p>Fill in the official paperwork for a division-approved PAN, then submit it for Division Head confirmation.</p></div></div>

  <x-stage-tracker :stages="['Submitted','Division approved','HR Preparation','DH Confirmation','HR Approval','Final Approval','Served','Filed']" current="HR Preparation" />

  @if ($pan->status === App\Enums\PanStatus::AwaitingTag)
  <div class="card" style="margin-bottom:14px">
    <div class="sect">Confidentiality tag — required before preparation</div>
    <div class="formgrid" style="padding-top:10px">
      <div class="field"><label>Confidentiality Tag <em>*</em></label>
        <select wire:model.live="tag">
          <option value="">— Untagged —</option>
          <option value="tarlac">Tarlac (routine)</option>
          <option value="manila">Manila (confidential)</option>
        </select>
        @error('tag')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror
        <span class="hint">Any preparer may apply the initial tag; what happens next depends on who tagged what.</span></div>
      <div class="field"><label>&nbsp;</label>
        <div><button class="btn primary" type="button" wire:click="applyTag"
          @if ($tag === 'manila' && ! $isHrHead) wire:confirm="Tag {{ $pan->reference }} as Manila? It becomes confidential — you will be returned to the queue and can no longer open it." @endif
        >Apply tag &amp; start preparation</button></div></div>
    </div>
  </div>
  @endif

  <div class="{{ $noteClass }}"><span class="ic">{{ $noteIcon }}</span><span>{!! $noteMsg !!}</span></div>

  <x-pan.return-history :returns="$pan->returns" />

  <div class="card">
    {{-- Same "Request details" block the viewer sees on the request view — read-only context for the preparer --}}
    <x-pan.request-details sect
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

    {{-- Locked until tagged (a normal preparer never reaches a Manila record — the policy 403s first) --}}
    <div class="lockable @if ($locked) locked @endif">
    <div class="sect">Manage Supporting Documents</div>
    <div class="formgrid" style="padding-top:10px;grid-template-columns:1fr">
      <p class="hint" style="margin:0 0 4px">Missing a document the Requestor has but hasn't attached? Upload it here on
        their behalf (e.g. received by email/Viber) — or use <b>Send back to Requestor…</b> below to have them attach it themselves.</p>

      @if ($pan->attachments->count())
      <div style="display:flex;flex-direction:column;gap:6px">
        @foreach ($pan->attachments as $existing)
        <div class="attachrow" wire:key="prep-existing-{{ $existing->id }}"><span class="pdf">PDF</span> {{ $existing->original_name }}
          <small>· {{ number_format($existing->size / 1024) }} KB</small>
          <span class="spacer"></span>
          <a class="btn ghost" href="{{ route('pan.attachment', [$pan->reference, $existing->id]) }}" style="text-decoration:none">Open</a>
          <button class="btn ghost" type="button" wire:click="removeAttachment({{ $existing->id }})" wire:confirm="Remove {{ $existing->original_name }}?">Remove</button>
        </div>
        @endforeach
      </div>
      @endif

      @foreach ($newAttachments as $i => $file)
      <div class="attachrow" wire:key="prep-new-{{ $i }}"><span class="pdf">PDF</span> {{ $file->getClientOriginalName() }}
        <small>· {{ number_format($file->getSize() / 1024) }} KB</small> <small style="color:var(--accent)">· ready to upload</small>
        <span class="spacer"></span>
        <button class="btn ghost" type="button" wire:click="removeNewAttachment({{ $i }})">Remove</button>
      </div>
      @endforeach

      @if ($pan->attachments->count() + count($newAttachments) < 3)
      <label class="upload" style="cursor:pointer;display:block{{ $errors->has('newAttachments') ? ';border-color:var(--red)' : '' }}">
        <input type="file" accept="application/pdf" wire:model="newAttachments" multiple hidden>
        <b>Choose PDF(s)</b> or drag them here on the Requestor's behalf.
        <small style="display:block;color:var(--ink-3)">{{ 3 - $pan->attachments->count() - count($newAttachments) }} of 3 slots remaining</small>
      </label>
      <x-upload-progress />
      @else
      <small style="color:var(--ink-3)">3 of 3 attached — remove one to add another.</small>
      @endif
      @error('newAttachments')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror
      @error('newAttachments.*')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror

      @if (count($newAttachments))
      <div><button class="btn" type="button" wire:click="uploadAttachments">Upload {{ count($newAttachments) }} document(s) now</button></div>
      @endif
    </div>

    <div class="sect">Employment details</div>
    <div class="formgrid" style="padding-top:10px">
      <div class="field"><label>Date Hired <em>*</em></label><input type="date" wire:model.live="date_hired" @error('date_hired') style="border-color:var(--red)" @enderror>
        @error('date_hired')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      <div class="field"><label>Employment Status</label><input readonly value="{{ $this->employmentStatus }}">
        <span class="hint">Carried from the last approved PAN — Regularization finalizes it at final approval.</span></div>
      <div class="field"><label>Division / Department</label><input readonly value="{{ $pan->employee->department->name }}"></div>
      <div class="field"><label>Effectivity From <em>*</em></label><input type="date" wire:model.live="doe_from" @error('doe_from') style="border-color:var(--red)" @enderror>
        @error('doe_from')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      <div class="field"><label>Effectivity To</label><input type="date" wire:model="doe_to" placeholder="Open-ended">
        @error('doe_to')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror
        <span class="hint">Leave empty for open-ended; allowances with an end date get expiry reminders.</span></div>
      @if ($pan->action_type->requiresWageNumber())
      <div class="field"><label>Wage Order No. <em>*</em></label><input wire:model.blur="wage_no" placeholder="e.g. NCR-26" maxlength="50" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore data-form-type="other" name="wage-no-{{ $pan->id }}" @error('wage_no') style="border-color:var(--red)" @enderror>
        @error('wage_no')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      @endif
    </div>

    <div class="sect">Action Reference — what is changing</div>
    </div>

    {{-- Previous-PAN quick access (rendered only when one exists). Placed OUTSIDE the lockable
         region so it stays clickable even while preparation is locked. --}}
    @if ($previous)
    <div class="prevpan">
      <span>⟲ Pre-generated from:</span>
      <a class="refid" href="{{ route('preparation.show', $previous->reference) }}" wire:navigate title="Open the previous PAN">{{ $previous->reference }}</a>
      <span>— the employee's last approved PAN; its "To" values seeded this form's "From" values.</span>
      <a wire:click="togglePrev" style="cursor:pointer">{{ $showPrev ? 'See less' : 'See more' }}</a>
    </div>
    <div class="prevdetail" @unless ($showPrev) hidden @endunless>
      <div class="pgrid">
        <div><small>Type of Action</small><br><b>{{ $previous->action_type->label() }}</b></div>
        <div><small>Status</small><br><b>{{ $previous->status->label() }}@if ($previous->filed_at) · {{ $previous->filed_at->format('M j, Y') }}@endif</b></div>
        <div><small>Effectivity</small><br><b>{{ $previous->form->doe_from?->format('M j, Y') ?? '—' }}</b></div>
        <div><small>Prepared by</small><br><b>{{ $previous->form->preparedBy->name }}</b></div>
        @php
          $prevBasic = collect($previous->form->action_reference)->firstWhere('field', 'basic');
          $prevPosition = collect($previous->form->action_reference)->firstWhere('field', 'position');
        @endphp
        @if ($prevBasic)
        <div><small>Basic Pay</small><br><b>₱ {{ $prevBasic['from'] }} → ₱ {{ $prevBasic['to'] }}</b></div>
        @endif
        @if ($prevPosition)
        <div><small>Position</small><br><b>{{ $prevPosition['to'] }}@if ($prevPosition['from'] === $prevPosition['to']) (unchanged)@endif</b></div>
        @endif
      </div>
    </div>
    @endif

    <div class="lockable @if ($locked) locked @endif">
    <div class="twrap"><table class="fromto" style="min-width:680px">
      <thead><tr><th style="width:200px">Item</th><th>From</th><th class="arrow"></th><th>To</th><th style="width:40px"></th></tr></thead>
      {{-- Fixed rows: Section, Place of Assignment, Immediate Head, Position, Job Level, Basic Pay
           (+ Leave Credits only when Type of Action is Regularization). Allowance rows are dynamic. --}}
      <tbody>
        @foreach ([
          'section' => 'Section', 'place' => 'Place of Assignment', 'head' => 'Immediate Head',
          'position' => 'Position', 'joblevel' => 'Job Level',
        ] as $field => $label)
        <tr wire:key="fixed-{{ $field }}">
          <td class="lbl">{{ $label }}</td>
          <td>
            @if (is_null($previous))
            <input class="toin" wire:model="fromValues.{{ $field }}" placeholder="—" maxlength="255" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore data-form-type="other" name="from-{{ $field }}-{{ $pan->id }}">
            @else
            {{ ($fromValues[$field] ?? '') !== '' ? $fromValues[$field] : '—' }}
            @endif
          </td>
          <td class="arrow">→</td>
          <td><input class="toin @if (($toValues[$field] ?? '') !== '') filled @endif" wire:model="toValues.{{ $field }}" placeholder="No change" maxlength="255" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore data-form-type="other" name="to-{{ $field }}-{{ $pan->id }}"></td>
          <td></td>
        </tr>
        @endforeach
        {{-- Leave Credits (Regularization only) sits BEFORE Basic Pay / any allowances --}}
        @if ($pan->action_type->includesLeaveCredits())
        <tr wire:key="fixed-leavecredits">
          <td class="lbl">Leave Credits</td>
          <td>
            @if (is_null($previous))
            <input class="toin" wire:model="fromValues.leavecredits" placeholder="—" maxlength="255" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore data-form-type="other" name="from-leavecredits-{{ $pan->id }}">
            @else
            {{ ($fromValues['leavecredits'] ?? '') !== '' ? $fromValues['leavecredits'] : '—' }}
            @endif
          </td>
          <td class="arrow">→</td>
          <td><select class="toin @if (($toValues['leavecredits'] ?? '') !== '') filled @endif" wire:model.live="toValues.leavecredits" style="width:100%">
            <option value="">No change</option>
            @foreach (\App\Livewire\HrPreparation\PrepareForm::LEAVE_CREDIT_OPTIONS as $option)
            <option>{{ $option }}</option>
            @endforeach
          </select>
          @error('toValues.leavecredits')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</td>
          <td></td>
        </tr>
        @endif
        <tr wire:key="fixed-basic">
          <td class="lbl">Basic Pay</td>
          <td class="num">
            @if (is_null($previous))
            <input class="toin numin" wire:model="fromValues.basic" placeholder="₱ 0.00" maxlength="255" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore data-form-type="other" name="from-basic-{{ $pan->id }}">
            @else
            {{ ($fromValues['basic'] ?? '') !== '' ? '₱ '.$fromValues['basic'] : '—' }}
            @endif
          </td>
          <td class="arrow">→</td>
          <td class="num"><input class="toin numin @if (($toValues['basic'] ?? '') !== '') filled @endif" wire:model="toValues.basic" placeholder="₱ 0.00" maxlength="255" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore data-form-type="other" name="to-basic-{{ $pan->id }}"></td>
          <td></td>
        </tr>
        @foreach ($allowances as $i => $allowance)
        <tr wire:key="allowance-{{ $i }}"><td class="lbl">{{ $allowance['label'] }}</td>
          <td class="num">{{ $allowance['from'] }}</td><td class="arrow">→</td>
          <td class="num"><input class="toin numin @if (trim($allowance['to']) !== '') filled @endif" wire:model="allowances.{{ $i }}.to" placeholder="₱ 0.00" maxlength="255" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore data-form-type="other" name="allowance-to-{{ $i }}-{{ $pan->id }}"></td>
          <td><button class="rowdel" type="button" title="Remove allowance row" wire:click="removeAllowance({{ $i }})">×</button></td></tr>
        @endforeach
      </tbody>
    </table></div>
    <div class="addrow" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
      <select wire:model="allowanceType" style="border:1px solid var(--line);border-radius:7px;background:var(--panel);color:var(--ink);font:inherit;font-size:13px;padding:7px 10px">
        @foreach (\App\Livewire\HrPreparation\PrepareForm::ALLOWANCE_TYPES as $type)
        <option>{{ $type }}</option>
        @endforeach
      </select>
      <button class="btn ghost" type="button" wire:click="addAllowance">+ Add allowance line</button>
    </div>

    <div class="sect">Remarks</div>
    <div class="formgrid" style="padding-top:10px">
      @if ($pan->wasProxyApproved())
      <div class="note warn full"><span class="ic">!</span>This PAN's Division Head approval was proxy-approved. Remarks are required here to document why — edit the seeded text below or add your own context.</div>
      @endif
      <div class="field full"><textarea rows="2" wire:model="remarks" maxlength="1000" placeholder="e.g. Per approved 2026 org structure."></textarea>
        @error('remarks')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
    </div>
    <div class="formfoot">
      @can('void', $pan)
      <button class="btn danger" type="button" wire:click="startReason('void')">Delete / Void…</button>
      @endcan
      @can('sendBackToRequestor', $pan)
      <button class="btn" type="button" wire:click="startReason('send_back_to_requestor')">Send back to Requestor…</button>
      @endcan
      <div class="spacer"></div>
      <a class="btn" href="{{ route('preparation.queue') }}" wire:navigate style="text-decoration:none">← Back to queue</a>
      <button class="btn" type="button" wire:click="save" wire:loading.attr="disabled" wire:target="newAttachments,save">Save</button>
      @if ($pan->status === App\Enums\PanStatus::ReturnedToPreparer)
      <button class="btn primary" type="button" wire:click="submit" wire:confirm="Resubmit {{ $pan->reference }} to the HR Approver?" wire:loading.attr="disabled" wire:target="newAttachments,submit">Resubmit to HR Approver</button>
      @else
      <button class="btn primary" type="button" wire:click="submit" wire:confirm="Submit {{ $pan->reference }} for Division Head Confirmation?" wire:loading.attr="disabled" wire:target="newAttachments,submit">Submit for Division Head Confirmation</button>
      @endif
    </div>
    </div>
  </div>

  <x-modal id="prep-reason-modal" :open="$showModal" close="$set('showModal', false)"
    title="{{ $modalAction === 'void' ? 'Void / Delete' : 'Send back to Requestor' }} — {{ $pan->reference }}">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      @if ($modalAction !== 'void')
      <div class="note info" style="margin:0"><span class="ic">i</span>This sends the PAN all the way back to the Requestor — they'll need to resubmit, which goes through Division Head approval again.</div>
      @endif
      <div class="field"><label>Reason <em>*</em></label>
        <select wire:model="reason">
          <option value="">Select a reason…</option>
          @if ($modalAction === 'void')
          <option>Duplicate request</option>
          <option>Raised in error</option>
          <option>Overtaken by events</option>
          @else
          <option>Missing supporting document(s)</option>
          <option>Supporting document unclear or incomplete</option>
          <option>Justification needs more detail</option>
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
      <button class="btn danger" type="button" wire:click="submitReason">{{ $modalAction === 'void' ? 'Void this PAN' : 'Send back to Requestor' }}</button>
    </x-slot:footer>
  </x-modal>
</div>
