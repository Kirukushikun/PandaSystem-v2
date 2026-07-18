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
        @error('tag')<span class="hint" style="color:var(--bad)">{{ $message }}</span>@enderror
        <span class="hint">Any preparer may apply the initial tag; what happens next depends on who tagged what.</span></div>
      <div class="field"><label>&nbsp;</label>
        <div><button class="btn primary" type="button" wire:click="applyTag"
          @if ($tag === 'manila' && ! $isHrHead) wire:confirm="Tag {{ $pan->reference }} as Manila? It becomes confidential — you will be returned to the queue and can no longer open it." @endif
        >Apply tag &amp; start preparation</button></div></div>
    </div>
  </div>
  @endif

  <div class="{{ $noteClass }}"><span class="ic">{{ $noteIcon }}</span><span>{!! $noteMsg !!}</span></div>

  @foreach ($pan->returns->reverse() as $return)
  <div class="note warn"><span class="ic">!</span><span><b>{{ $return->from_status->label() }} → returned:</b>&nbsp;{{ $return->reason }}@if ($return->details) — {{ $return->details }}@endif <small style="color:var(--ink-3)">({{ $return->created_at->format('M j, Y') }})</small></span></div>
  @endforeach

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
      :document="$pan->attachment_path ? basename($pan->attachment_path) : null"
      :document-url="$pan->attachment_path ? route('pan.attachment', $pan->reference) : null" />

    {{-- Locked until tagged (a normal preparer never reaches a Manila record — the policy 403s first) --}}
    <div class="lockable @if ($locked) locked @endif">
    <div class="sect">Employment details</div>
    <div class="formgrid" style="padding-top:10px">
      <div class="field"><label>Date Hired <em>*</em></label><input type="date" wire:model="date_hired">
        @error('date_hired')<span class="hint" style="color:var(--bad)">{{ $message }}</span>@enderror</div>
      <div class="field"><label>Employment Status</label><input readonly value="{{ $this->employmentStatus }}">
        <span class="hint">Carried from the last filed PAN — Regularization finalizes it at final approval.</span></div>
      <div class="field"><label>Division / Department</label><input readonly value="{{ $pan->employee->department->name }}"></div>
      <div class="field"><label>Effectivity From <em>*</em></label><input type="date" wire:model="doe_from">
        @error('doe_from')<span class="hint" style="color:var(--bad)">{{ $message }}</span>@enderror</div>
      <div class="field"><label>Effectivity To</label><input type="date" wire:model="doe_to" placeholder="Open-ended">
        @error('doe_to')<span class="hint" style="color:var(--bad)">{{ $message }}</span>@enderror
        <span class="hint">Leave empty for open-ended; allowances with an end date get expiry reminders.</span></div>
      @if ($pan->action_type->requiresWageNumber())
      <div class="field"><label>Wage Order No. <em>*</em></label><input wire:model="wage_no" placeholder="e.g. NCR-26">
        @error('wage_no')<span class="hint" style="color:var(--bad)">{{ $message }}</span>@enderror</div>
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
      <span>— the employee's last filed PAN; its "To" values seeded this form's "From" values.</span>
      <a wire:click="togglePrev" style="cursor:pointer">{{ $showPrev ? 'See less' : 'See more' }}</a>
    </div>
    <div class="prevdetail" @unless ($showPrev) hidden @endunless>
      <div class="pgrid">
        <div><small>Type of Action</small><br><b>{{ $previous->action_type->label() }}</b></div>
        <div><small>Status</small><br><b>Filed @if ($previous->filed_at)· {{ $previous->filed_at->format('M j, Y') }}@endif</b></div>
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
          'position' => 'Position', 'joblevel' => 'Job Level', 'basic' => 'Basic Pay',
        ] as $field => $label)
        <tr wire:key="fixed-{{ $field }}">
          <td class="lbl">{{ $label }}</td>
          <td @if ($field === 'basic') class="num" @endif>{{ ($fromValues[$field] ?? '') !== '' ? ($field === 'basic' ? '₱ ' : '').$fromValues[$field] : '—' }}</td>
          <td class="arrow">→</td>
          <td @if ($field === 'basic') class="num" @endif><input class="toin @if (($toValues[$field] ?? '') !== '') filled @endif @if ($field === 'basic') numin @endif" wire:model="toValues.{{ $field }}" placeholder="{{ $field === 'basic' ? '₱ 0.00' : 'No change' }}"></td>
          <td></td>
        </tr>
        @endforeach
        @if ($pan->action_type->includesLeaveCredits())
        <tr wire:key="fixed-leavecredits">
          <td class="lbl">Leave Credits</td>
          <td>{{ ($fromValues['leavecredits'] ?? '') !== '' ? $fromValues['leavecredits'] : '—' }}</td>
          <td class="arrow">→</td>
          <td><input class="toin @if (($toValues['leavecredits'] ?? '') !== '') filled @endif" wire:model="toValues.leavecredits" placeholder="e.g. 5 VL / 5 SL"></td>
          <td></td>
        </tr>
        @endif
        @foreach ($allowances as $i => $allowance)
        <tr wire:key="allowance-{{ $i }}"><td class="lbl">{{ $allowance['label'] }}</td>
          <td class="num">{{ $allowance['from'] }}</td><td class="arrow">→</td>
          <td class="num"><input class="toin numin @if (trim($allowance['to']) !== '') filled @endif" wire:model="allowances.{{ $i }}.to" placeholder="₱ 0.00"></td>
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
      <div class="field full"><textarea rows="2" wire:model="remarks" placeholder="e.g. Per approved 2026 org structure."></textarea>
        @error('remarks')<span class="hint" style="color:var(--bad)">{{ $message }}</span>@enderror</div>
    </div>
    <div class="formfoot">
      @can('void', $pan)
      <button class="btn danger" type="button" data-modal-open="void-modal">Delete / Void…</button>
      @endcan
      <div class="spacer"></div>
      <a class="btn" href="{{ route('preparation.queue') }}" wire:navigate style="text-decoration:none">← Back to queue</a>
      <button class="btn" type="button" wire:click="save">Save</button>
      @if ($pan->status === App\Enums\PanStatus::ReturnedToPreparer)
      <button class="btn primary" type="button" wire:click="submit" wire:confirm="Resubmit {{ $pan->reference }} to the HR Approver?">Resubmit to HR Approver</button>
      @else
      <button class="btn primary" type="button" wire:click="submit" wire:confirm="Submit {{ $pan->reference }} for Division Head Confirmation?">Submit for Division Head Confirmation</button>
      @endif
    </div>
    </div>
  </div>

  <x-modal id="void-modal" title="Void / Delete — {{ $pan->reference }}">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="field"><label>Reason <em>*</em></label>
        <select wire:model="reason">
          <option value="">Select a reason…</option>
          <option>Duplicate request</option>
          <option>Raised in error</option>
          <option>Overtaken by events</option>
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
      <button class="btn danger" type="button" wire:click="void">Void this PAN</button>
    </x-slot:footer>
  </x-modal>
</div>
