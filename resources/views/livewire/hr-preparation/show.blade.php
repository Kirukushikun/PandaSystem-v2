{{-- Static sample body (S. Lim / Wage Order, returned by HR Approver) until the real build. --}}
<div>
  <p class="crumb">HR Preparation · signed in as HR Head Preparer</p>
  <div class="htop">
    <div><h2>View Request — <span class="ref" style="font-size:18px">{{ $pan }}</span></h2>
    <p>The request with its prepared PAN details, currently sent back by the HR Approver for resolution.</p></div>
    <div class="spacer"></div>
    <x-status-pill status="returned-to-preparer" />
  </div>

  <x-stage-tracker :stages="['Submitted','Division approved','HR Preparation — rework','DH Confirmation','HR Approval','Final Approval']" current="HR Preparation — rework" />

  <div class="note warn"><span class="ic">!</span>Returned by R. Ocampo (HR Approver), Jul 13: "Wage number mismatch — verify against Wage Order NCR-26."</div>

  <div class="card">
    <x-pan.request-details sect
      employee="S. Lim" employee-id="EMP-10233" department="Feedmill"
      action="Wage Order" requested-by="P. Enriquez" submitted="Jun 30, 2026 · 08:15"
      justification="Application of Wage Order NCR-26 minimum daily rate adjustment, effective July 15, 2026."
      :justification-rows="2"
      document="wage_order_ncr26_lim.pdf" document-size="187 KB" />

    <x-pan.prepared-details
      prepared-by="T. Navarro · Jul 9, 2026" date-hired="Nov 5, 2021"
      employment-status="Regular" effectivity="Jul 15, 2026 — open-ended"
      wage-no="NCR-26"
      :rows="[
        ['label' => 'Section',             'from' => 'Milling Line B',      'to' => 'Milling Line B'],
        ['label' => 'Place of Assignment', 'from' => 'Sta. Maria Feedmill', 'to' => 'Sta. Maria Feedmill'],
        ['label' => 'Immediate Head',      'from' => 'P. Enriquez',         'to' => 'P. Enriquez'],
        ['label' => 'Position',            'from' => 'Mill Operator',       'to' => 'Mill Operator'],
        ['label' => 'Job Level',           'from' => 'JL-3',                'to' => 'JL-3'],
        ['label' => 'Basic Pay',           'from' => '₱ 610.00 / day',      'to' => '₱ 645.00 / day', 'chg' => true, 'num' => true],
      ]" />

    <div class="formfoot">
      <a class="btn" href="{{ route('preparation.queue') }}" wire:navigate style="text-decoration:none">← Back to queue</a>
      <div class="spacer"></div>
      <button class="btn danger" type="button" onclick="showToast('Void flow arrives with the Maintenance-style confirm modal (UI scaffold).')">Void…</button>
      <a class="btn primary" href="{{ route('preparation.edit', $pan) }}" wire:navigate style="text-decoration:none">Revise in Preparation Form</a>
    </div>
  </div>
</div>
