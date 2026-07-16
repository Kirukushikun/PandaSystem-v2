<div>
  <p class="crumb">HR Preparation · signed in as HR Head Preparer</p>
  <div class="htop"><div><h2>Prepare PAN — {{ $pan }} · N. Fernandez</h2>
    <p>Fill in the official paperwork for a division-approved PAN, then submit it for Division Head confirmation.</p></div></div>

  <x-stage-tracker :stages="['Submitted','Division approved','HR Preparation','DH Confirmation','HR Approval','Final Approval','Served','Filed']" current="HR Preparation" />

  {{-- SIMULATION ONLY (per the mockup): in the real build the role comes from the signed-in
       account. Livewire state drives the lock/unlock of the form sections below. --}}
  <div class="card" style="margin-bottom:14px">
    <div class="sect">Simulation — try the tagging rules</div>
    <div class="formgrid" style="padding-top:10px">
      <div class="field"><label>Signed-in preparer</label>
        <div style="display:flex;gap:6px">
          <x-chip :on="$role === 'normal'" wire:click="setRole('normal')">Normal HR Preparer</x-chip>
          <x-chip :on="$role === 'head'" wire:click="setRole('head')">HR Head Preparer</x-chip>
        </div></div>
      <div class="field"><label>Confidentiality Tag</label>
        <select wire:model.live="tag">
          <option value="none">— Untagged —</option>
          <option value="tarlac">Tarlac (routine)</option>
          <option value="manila">Manila (confidential)</option>
        </select>
        <span class="hint">Any preparer may apply the initial tag; what happens next depends on who tagged what.</span></div>
    </div>
  </div>

  <div class="{{ $noteClass }}"><span class="ic">{{ $noteIcon }}</span><span>{!! $noteMsg !!}</span></div>

  <div class="card">
    {{-- Same "Request details" block the viewer sees on the request view — read-only context for the preparer --}}
    <x-pan.request-details sect
      employee="N. Fernandez" employee-id="EMP-10490" department="Corporate Office"
      action="Change of Position" requested-by="L. Madrigal" submitted="Jul 6, 2026 · 10:23"
      justification="Role elevated to Senior HR Officer per the approved 2026 organizational structure."
      :justification-rows="2"
      document="org_structure_memo_fernandez.pdf" document-size="512 KB" />

    {{-- Locked until tagged; stays locked for a normal preparer when the tag is Manila --}}
    <div class="lockable @if ($locked) locked @endif">
    <div class="sect">Employment details</div>
    <div class="formgrid" style="padding-top:10px">
      <div class="field"><label>Date Hired</label><input value="Mar 16, 2024"></div>
      <div class="field"><label>Employment Status</label><input readonly value="Regular"></div>
      <div class="field"><label>Division / Department</label><input readonly value="Corporate Office"></div>
      <div class="field"><label>Effectivity From</label><input value="Aug 1, 2026"></div>
      <div class="field"><label>Effectivity To</label><input value="—" placeholder="Open-ended"></div>
    </div>

    <div class="sect">Action Reference — what is changing</div>
    </div>

    {{-- Previous-PAN quick access (rendered only when one exists). Placed OUTSIDE the lockable
         region so it stays clickable even while preparation is locked. In the real build the
         reference opens that PAN's own view; "See more" expands the inline summary. --}}
    <div class="prevpan">
      <span>⟲ Pre-generated from:</span>
      <a class="refid" wire:click="togglePrev" title="Open the previous PAN">PAN-BFC-2026-0813</a>
      <span>— the employee's last filed PAN; its "To" values seeded this form's "From" values.</span>
      <a wire:click="togglePrev">{{ $showPrev ? 'See less' : 'See more' }}</a>
    </div>
    <div class="prevdetail" @unless ($showPrev) hidden @endunless>
      <div class="pgrid">
        <div><small>Type of Action</small><br><b>Salary Alignment</b></div>
        <div><small>Status</small><br><b>Filed · Mar 28, 2026</b></div>
        <div><small>Effectivity</small><br><b>Jan 1, 2026</b></div>
        <div><small>Prepared by</small><br><b>T. Navarro</b></div>
        <div><small>Basic Pay</small><br><b>₱ 26,000.00 → ₱ 28,500.00</b></div>
        <div><small>Position</small><br><b>HR Generalist (unchanged)</b></div>
      </div>
    </div>

    <div class="lockable @if ($locked) locked @endif">
    <div class="twrap"><table class="fromto" style="min-width:680px">
      <thead><tr><th style="width:200px">Item</th><th>From</th><th class="arrow"></th><th>To</th><th style="width:40px"></th></tr></thead>
      {{-- Fixed rows: Section, Place of Assignment, Immediate Head, Position, Job Level, Basic Pay
           (+ Leave Credits only when Type of Action is Regularization). Allowance rows are dynamic. --}}
      <tbody>
        <tr><td class="lbl">Section</td><td>HR Operations</td><td class="arrow">→</td><td><input class="toin" placeholder="No change"></td><td></td></tr>
        <tr><td class="lbl">Place of Assignment</td><td>Main Office</td><td class="arrow">→</td><td><input class="toin" placeholder="No change"></td><td></td></tr>
        <tr><td class="lbl">Immediate Head</td><td>R. Ocampo</td><td class="arrow">→</td><td><input class="toin" placeholder="No change"></td><td></td></tr>
        <tr><td class="lbl">Position</td><td>HR Generalist</td><td class="arrow">→</td><td><input class="toin" placeholder="No change"></td><td></td></tr>
        <tr><td class="lbl">Job Level</td><td>JL-6</td><td class="arrow">→</td><td><input class="toin" placeholder="No change"></td><td></td></tr>
        <tr><td class="lbl">Basic Pay</td><td class="num">₱ 28,500.00</td><td class="arrow">→</td><td class="num"><input class="toin numin" placeholder="₱ 0.00"></td><td></td></tr>
        @foreach ($allowances as $i => $allowance)
        <tr wire:key="allowance-{{ $i }}"><td class="lbl">{{ $allowance }}</td><td class="num">—</td><td class="arrow">→</td>
          <td class="num"><input class="toin numin" placeholder="₱ 0.00"></td>
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
      <div class="field full"><textarea rows="2">Per approved 2026 org structure. Transportation allowance effective same date.</textarea></div>
    </div>
    <div class="formfoot">
      <button class="btn danger" type="button" onclick="showToast('Void flow arrives with the Maintenance-style confirm modal (UI scaffold).')">Delete / Void…</button>
      <div class="spacer"></div>
      <a class="btn" href="{{ route('preparation.queue') }}" wire:navigate style="text-decoration:none">← Back to queue</a>
      <button class="btn" type="button" onclick="showToast('Saved (UI scaffold — nothing is persisted yet).')">Save</button>
      <button class="btn primary" type="button" onclick="showToast('Submitted for Division Head Confirmation (UI scaffold — nothing is persisted yet).')">Submit for Division Head Confirmation</button>
    </div>
    </div>
  </div>
</div>
