{{-- ONE view, two render states (per the mockup note): without a prepared PAN it shows the
     request details only; with one, the PAN details are appended below as an extension.
     Static sample bodies (A. Santos / E. Garcia) until the real build. --}}
<div>
  <p class="crumb">Division Head · Broiler Operations</p>

  @if (! $hasPreparedPan)

  <div class="htop">
    <div><h2>View Request — <span class="ref" style="font-size:18px">{{ $pan }}</span></h2>
    <p>The request exactly as submitted, read-only. No PAN has been prepared yet, so only the request details are shown.</p></div>
    <div class="spacer"></div>
    <x-status-pill status="with-division-head">Awaiting your decision</x-status-pill>
  </div>

  <x-stage-tracker :stages="['Submitted','Division Head','HR Preparation','HR Approval','Final Approval']" current="Division Head" />

  <div class="card">
    <x-pan.request-details
      employee="A. Santos" employee-id="EMP-10301" department="Broiler Operations"
      action="Salary Alignment" requested-by="M. Dela Cruz" submitted="Jul 8, 2026 · 09:41"
      justification="Current pay is below the approved 2026 salary structure for Farm Technician II. Requesting alignment to the job-level minimum."
      document="salary_structure_alignment_santos.pdf" document-size="268 KB" />
    <div class="formfoot">
      <a class="btn" href="{{ route('division.queue') }}" wire:navigate style="text-decoration:none">← Back to queue</a>
      <div class="spacer"></div>
      <button class="btn danger" type="button" data-modal-open="return-modal">Return…</button>
      <button class="btn primary" type="button" onclick="showToast('Approved and forwarded to HR Preparation (UI scaffold — nothing is persisted yet).')">Approve</button>
    </div>
  </div>

  <x-modal id="return-modal" title="Return to Requestor — {{ $pan }}">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="field"><label>Reason <em>*</em></label>
        <select><option>Incomplete supporting document</option><option>Wrong employee selected</option><option>Wrong type of action</option><option>Needs stronger justification</option><option>Custom reason…</option></select></div>
      <div class="field"><label>Details (optional)</label>
        <textarea rows="3"></textarea></div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" data-close>Cancel</button>
      <div class="spacer"></div>
      <button class="btn danger" type="button" data-close onclick="showToast('Returned to Requestor with reason (UI scaffold — nothing is persisted yet).')">Return to Requestor</button>
    </x-slot:footer>
  </x-modal>

  @else

  <div class="htop">
    <div><h2>View Request — <span class="ref" style="font-size:18px">{{ $pan }}</span></h2>
    <p>This request already has a prepared PAN, so its details are shown below the request as an extension.</p></div>
    <div class="spacer"></div>
    <x-status-pill status="for-confirmation">For your confirmation</x-status-pill>
  </div>

  <x-stage-tracker :stages="['Submitted','Division approved','HR prepared','DH Confirmation','HR Approval','Final Approval']" current="DH Confirmation" />

  <div class="card">
    <x-pan.request-details sect
      employee="E. Garcia" employee-id="EMP-10198" department="Broiler Operations"
      action="Promotion" requested-by="K. Reyes" submitted="Jul 3, 2026 · 14:07"
      justification="Consistently exceeded targets for four consecutive quarters; recommended for promotion to Senior Farm Technician."
      :justification-rows="2"
      document="promotion_recommendation_garcia.pdf" document-size="341 KB" />

    {{-- extension: appears only when a prepared PAN exists --}}
    <x-pan.prepared-details
      prepared-by="T. Navarro · Jul 11, 2026" date-hired="Feb 2, 2023"
      employment-status="Regular" effectivity="Aug 16, 2026 — open-ended"
      :rows="[
        ['label' => 'Section',             'from' => 'Broiler Production A', 'to' => 'Broiler Production A'],
        ['label' => 'Place of Assignment', 'from' => 'San Rafael Farm',      'to' => 'San Rafael Farm'],
        ['label' => 'Immediate Head',      'from' => 'K. Reyes',             'to' => 'K. Reyes'],
        ['label' => 'Position',            'from' => 'Farm Technician II',   'to' => 'Senior Farm Technician', 'chg' => true],
        ['label' => 'Job Level',           'from' => 'JL-4',                 'to' => 'JL-5', 'chg' => true],
        ['label' => 'Basic Pay',           'from' => '₱ 28,100.00',          'to' => '₱ 31,600.00', 'chg' => true, 'num' => true],
      ]"
      remarks="Per Q2 2026 performance review cycle. Effectivity aligned with payroll cutoff." />

    <div class="formfoot">
      <a class="btn" href="{{ route('division.queue') }}" wire:navigate style="text-decoration:none">← Back to queue</a>
      <div class="spacer"></div>
      <button class="btn danger" type="button" data-modal-open="dispute-modal">Dispute…</button>
      <button class="btn primary" type="button" onclick="showToast('Confirmed — forwarded to HR Approver (UI scaffold — nothing is persisted yet).')">Confirm</button>
    </div>
  </div>

  <x-modal id="dispute-modal" title="Dispute — {{ $pan }}">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="field"><label>Reason <em>*</em></label>
        <select><option>Prepared values are incorrect</option><option>Wrong effectivity date</option><option>Employment status is wrong</option><option>Needs revised action reference</option><option>Custom reason…</option></select></div>
      <div class="field"><label>Details (optional)</label>
        <textarea rows="3"></textarea></div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" data-close>Cancel</button>
      <div class="spacer"></div>
      <button class="btn danger" type="button" data-close onclick="showToast('Disputed — sent back to HR Preparer (UI scaffold — nothing is persisted yet).')">Send back to HR Preparer</button>
    </x-slot:footer>
  </x-modal>

  @endif
</div>
