{{-- Static sample body (G. Padilla / Regularization) until the real build. --}}
<div>
  <p class="crumb">Final Approver</p>
  <div class="htop">
    <div><h2>View Request — <span class="ref" style="font-size:18px">{{ $pan }}</span></h2>
    <p>Fully prepared and HR-approved, awaiting your final sign-off.</p></div>
    <div class="spacer"></div>
    <x-status-pill status="for-final-approval">Awaiting final approval</x-status-pill>
  </div>

  <x-stage-tracker :stages="['Submitted','Division approved','HR prepared','DH confirmed','HR approved','Final Approval']" current="Final Approval" />

  <div class="card">
    {{-- Same "Request details" block shown to earlier stages — identical contents at every stage --}}
    <x-pan.request-details sect
      employee="G. Padilla" employee-id="EMP-10512" department="Sales &amp; Distribution"
      action="Regularization" requested-by="J. Villegas" submitted="Jun 26, 2026 · 15:02"
      justification="Completed 6-month probationary period with a satisfactory performance rating; endorsed for regularization by the depot supervisor."
      :justification-rows="2"
      document="evaluation_padilla_jun2026.pdf" document-size="224 KB" />

    <x-pan.prepared-details
      prepared-by="T. Navarro · Jul 10, 2026" date-hired="Jan 26, 2026"
      employment-status="Probationary" effectivity="Aug 1, 2026"
      hr-approved-by="R. Ocampo · Jul 13, 2026"
      ref-heading="Action Reference — prepared changes"
      :rows="[
        ['label' => 'Section',             'from' => 'Sales — North Luzon', 'to' => 'Sales — North Luzon'],
        ['label' => 'Place of Assignment', 'from' => 'San Fernando Depot',  'to' => 'San Fernando Depot'],
        ['label' => 'Immediate Head',      'from' => 'J. Villegas',         'to' => 'J. Villegas'],
        ['label' => 'Position',            'from' => 'Sales Clerk',         'to' => 'Sales Clerk'],
        ['label' => 'Job Level',           'from' => 'JL-2',                'to' => 'JL-2'],
        ['label' => 'Basic Pay',           'from' => '₱ 19,000.00',         'to' => '₱ 21,500.00', 'chg' => true, 'num' => true],
        ['label' => 'Leave Credits',       'from' => '0',                   'to' => '15 / yr', 'chg' => true, 'num' => true],
      ]" />

    <div class="note info" style="margin:14px 18px"><span class="ic">i</span>Approving this Regularization finalizes the employee's status as "Regular" automatically.</div>
    <div class="formfoot">
      <a class="btn" href="{{ route('final-approval.queue') }}" wire:navigate style="text-decoration:none">← Back to queue</a>
      <div class="spacer"></div>
      <button class="btn danger" type="button" data-modal-open="reject-modal">Reject — back to HR Preparation…</button>
      <button class="btn primary" type="button" onclick="showToast('Final approval given — status auto-finalized to Regular (UI scaffold — nothing is persisted yet).')">Give Final Approval</button>
    </div>
  </div>

  <x-modal id="reject-modal" title="Reject — back to HR Preparation — {{ $pan }}">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="field"><label>Reason <em>*</em></label>
        <select><option>Values need revision</option><option>Wrong effectivity date</option><option>Incorrect approver routing</option><option>Needs supporting document</option><option>Custom reason…</option></select></div>
      <div class="field"><label>Details (optional)</label>
        <textarea rows="3"></textarea></div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" data-close>Cancel</button>
      <div class="spacer"></div>
      <button class="btn danger" type="button" data-close onclick="showToast('Rejected — returned to HR Preparation with reason (UI scaffold — nothing is persisted yet).')">Reject to HR Preparation</button>
    </x-slot:footer>
  </x-modal>
</div>
