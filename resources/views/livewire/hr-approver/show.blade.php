{{-- Static sample body (N. Fernandez / Change of Position) until the real build. --}}
<div>
  <p class="crumb">HR Approver</p>
  <div class="htop">
    <div><h2>View Request — <span class="ref" style="font-size:18px">{{ $pan }}</span></h2>
    <p>The request together with its HR-prepared details, awaiting your approval.</p></div>
    <div class="spacer"></div>
    <x-status-pill status="for-hr-approval">Awaiting HR approval</x-status-pill>
  </div>

  <x-stage-tracker :stages="['Submitted','Division approved','HR prepared','DH confirmed','HR Approval','Final Approval']" current="HR Approval" />

  <div class="card">
    {{-- Same "Request details" block shown to the HR Preparer — identical contents on both ends --}}
    <x-pan.request-details sect
      employee="N. Fernandez" employee-id="EMP-10490" department="Corporate Office"
      action="Change of Position" requested-by="L. Madrigal" submitted="Jul 6, 2026 · 10:23"
      justification="Role elevated to Senior HR Officer per the approved 2026 organizational structure."
      :justification-rows="2"
      document="org_structure_memo_fernandez.pdf" document-size="512 KB" />

    <x-pan.prepared-details
      prepared-by="M. Dela Cruz · Jul 14, 2026" date-hired="Mar 16, 2024"
      employment-status="Regular" effectivity="Aug 1, 2026 — open-ended"
      ref-heading="Action Reference — prepared changes"
      :rows="[
        ['label' => 'Section',                  'from' => 'HR Operations', 'to' => 'HR Operations'],
        ['label' => 'Place of Assignment',      'from' => 'Main Office',   'to' => 'Main Office'],
        ['label' => 'Immediate Head',           'from' => 'R. Ocampo',     'to' => 'V. Salazar', 'chg' => true],
        ['label' => 'Position',                 'from' => 'HR Generalist', 'to' => 'Senior HR Officer', 'chg' => true],
        ['label' => 'Job Level',                'from' => 'JL-6',          'to' => 'JL-8', 'chg' => true],
        ['label' => 'Basic Pay',                'from' => '₱ 28,500.00',   'to' => '₱ 34,200.00', 'chg' => true, 'num' => true],
        ['label' => 'Transportation Allowance', 'from' => '—',             'to' => '₱ 2,000.00', 'chg' => true, 'num' => true],
      ]" />

    <div class="formfoot">
      <a class="btn" href="{{ route('hr-approval.queue') }}" wire:navigate style="text-decoration:none">← Back to queue</a>
      <div class="spacer"></div>
      <button class="btn danger" type="button" data-modal-open="return-prep-modal">Return to HR Preparer…</button>
      <button class="btn primary" type="button" onclick="showToast('Approved — forwarded to Final Approver (UI scaffold — nothing is persisted yet).')">Approve</button>
    </div>
  </div>

  <x-modal id="return-prep-modal" title="Return to HR Preparer — {{ $pan }}">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="field"><label>Reason <em>*</em></label>
        <select><option>Prepared values are incorrect</option><option>Wage number mismatch</option><option>Wrong effectivity date</option><option>Missing allowance line</option><option>Custom reason…</option></select></div>
      <div class="field"><label>Details (optional)</label>
        <textarea rows="3"></textarea></div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" data-close>Cancel</button>
      <div class="spacer"></div>
      <button class="btn danger" type="button" data-close onclick="showToast('Returned to HR Preparer with reason (UI scaffold — nothing is persisted yet).')">Return to HR Preparer</button>
    </x-slot:footer>
  </x-modal>
</div>
