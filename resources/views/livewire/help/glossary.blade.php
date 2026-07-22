{{-- Definition of terms: every status, tag, and role. Uses the same shared pills/dots
     as the live screens, so this page doubles as a legend for the rest of the UI. --}}
<div>
  <p class="crumb">Help</p>
  <div class="htop">
    <div><h2>Glossary — Statuses, Tags &amp; Roles</h2>
      <p>What each status means, who can act on it, and where the PAN goes next. The pills and colors here are the same ones used across the system.</p></div>
  </div>

  <div class="card" style="margin-bottom:18px">
    <div class="sect">The full journey of a PAN</div>
    <div style="padding:14px 18px 16px">
      <x-stage-tracker style="margin:0 0 10px"
        :stages="['Draft','Division Head','Tag & HR Preparation','DH Confirmation','HR Approval','Final Approval','Served','Filed']"
        current="Filed" />
      <p class="locknote" style="margin:0">Any stage can also branch backwards: the Division Head returns to the Requestor, the HR Approver returns one step to the Preparer, and the Final Approver rejects all the way back to HR Preparation. From HR Preparation, the Preparer can also send a PAN all the way back to the Requestor with a reason — e.g. missing supporting documents — rather than fixing it themselves; the Preparer can also upload documents on the Requestor's behalf instead, if that's simpler. Once filed, a follow-up PAN can start a new cycle for the same employee.</p>
    </div>
  </div>

  <div class="card" style="margin-bottom:18px">
    <div class="sect">Statuses</div>
    <div class="twrap"><table style="min-width:900px">
      <thead><tr><th style="width:210px">Status</th><th>What it means</th><th style="width:170px">Who can act</th><th>What can happen next</th></tr></thead>
      <tbody>
        @foreach ([
          ['draft', 'Draft', 'Saved by the Requestor but not yet submitted. Attachment optional at this point.', 'Requestor', 'Submit to Division Head · edit · delete outright'],
          ['with-division-head', 'With Division Head', 'Formally submitted (PDF required) and awaiting the department head\'s decision. Read-only to the Requestor.', 'Division Head <small>(DH Head if Manila)</small>', 'Approved → HR Preparation · returned to Requestor with a reason'],
          ['returned-to-requestor', 'Returned — for correction', 'Sent back to the Requestor with a preset or custom reason (e.g. incomplete document).', 'Requestor', 'Replace attachment & resubmit · withdraw entirely'],
          ['awaiting-tag', 'Awaiting tag & preparation', 'Division-approved, but paperwork can\'t start until a confidentiality tag (Tarlac or Manila) is applied.', 'Any HR Preparer', 'Tagged Tarlac → normal preparer works it · tagged Manila → HR Head only'],
          ['in-preparation', 'HR Preparation', 'The official paperwork is being filled in: employment details and the Action Reference From/To table.', 'HR Preparer <small>(HR Head if Manila)</small>', 'Submitted for DH confirmation · voided with a reason · sent back to the Requestor (e.g. missing documents) — or the Preparer uploads on the Requestor\'s behalf instead'],
          ['for-confirmation', 'For DH Confirmation', 'HR has prepared the PAN; the Division Head reviews what was actually drafted.', 'Division Head <small>(DH Head if Manila)</small>', 'Confirmed → HR Approval · disputed → back to the Preparer'],
          ['returned-to-preparer', 'Returned by HR Approver', 'Sent one step back to HR Preparation for rework, with a mandatory reason.', 'HR Preparer', 'Revised & resubmitted · voided with a reason · sent back to the Requestor (e.g. missing documents) — or the Preparer uploads on the Requestor\'s behalf instead'],
          ['for-hr-approval', 'For HR Approval', 'Second HR-level check after preparation and confirmation. No confidentiality distinction at this stage.', 'HR Approver', 'Approved → Final Approval · returned one step to the Preparer'],
          ['for-final-approval', 'For Final Approval', 'Awaiting the last sign-off — individually or in bulk (by selection or by action type).', 'Final Approver', 'Approved (Regularization auto-sets status to "Regular") · rejected → all the way back to HR Preparation'],
          ['approved', 'Approved — for serving', 'Fully approved; the action now has to be carried out in the real world.', 'HR Preparer', 'Marked Served · marked Unserved with a reason'],
          ['served', 'Served', 'The action has been carried out and acknowledged.', 'HR Preparer', 'Marked Filed to close the record'],
          ['unserved', 'Unserved', 'Approved but couldn\'t be carried out — AWOL, resigned, terminated, or a custom reason.', '—', 'Terminal — kept on record'],
          ['filed', 'Filed', 'Closed out and archived. The PAN\'s "To" values become the starting "From" values of the employee\'s next PAN.', 'HR Preparer', 'Start a follow-up PAN for the same employee'],
          ['withdrawn', 'Withdrawn', 'The Requestor pulled back a request that had been returned to them, instead of resubmitting.', '—', 'Terminal — kept on record'],
          ['voided', 'Voided', 'Deleted by HR while awaiting preparation or resolution, with a mandatory reason on record.', '—', 'Terminal — reason visible in the Audit Trail'],
        ] as [$status, $label, $means, $who, $next])
        <tr>
          <td><x-status-pill :status="$status">{{ $label }}</x-status-pill></td>
          <td>{{ $means }}</td>
          <td>{!! $who !!}</td>
          <td>{{ $next }}</td>
        </tr>
        @endforeach
      </tbody>
    </table></div>
  </div>

  <div class="twocol">
    <div class="pane">
      <h3>Confidentiality tags</h3>
      <div class="pad">
        <div class="permrow"><span><x-tag-dot tag="manila" />&nbsp; <b>Manila</b> — highly confidential</span></div>
        <p class="locknote" style="margin:4px 0 12px">Prepared only by an HR Head Preparer; a normal preparer who opens or tags it is locked out permanently. At the division stages, only the DH Head — not the department's regular Division Head — can see or act on it.</p>
        <div class="permrow"><span><x-tag-dot tag="tarlac" />&nbsp; <b>Tarlac</b> — routine</span></div>
        <p class="locknote" style="margin:4px 0 12px">The normal path. Worked by ordinary HR Preparers; visible to the department's Division Head as usual.</p>
        <div class="permrow"><span><x-tag-dot />&nbsp; <b>Untagged</b></span></div>
        <p class="locknote" style="margin:4px 0 0">Freshly approved by the Division Head. Preparation stays locked until any HR Preparer applies one of the two tags. Tag dots are visible to HR Head Preparers only.</p>
      </div>
    </div>
    <div class="pane">
      <h3>Roles</h3>
      <div class="pad">
        <div class="permrow"><span><b>Requestor</b></span><small style="color:var(--ink-3)">Raises PANs for their department(s)</small></div>
        <div class="permrow"><span><b>Division Head</b></span><small style="color:var(--ink-3)">Approves/returns, then confirms prepared PANs</small></div>
        <div class="permrow"><span><b>DH Head</b></span><small style="color:var(--ink-3)">Division-stage decisions on Manila PANs, all departments</small></div>
        <div class="permrow"><span><b>HR Preparer</b></span><small style="color:var(--ink-3)">Tags and fills in the official paperwork</small></div>
        <div class="permrow"><span><b>HR Head Preparer</b></span><small style="color:var(--ink-3)">Same, plus exclusive handling of Manila PANs</small></div>
        <div class="permrow"><span><b>HR Approver</b></span><small style="color:var(--ink-3)">Second check before final sign-off</small></div>
        <div class="permrow"><span><b>Final Approver</b></span><small style="color:var(--ink-3)">Last approval, single or bulk</small></div>
        <div class="permrow"><span><b>Admin</b></span><small style="color:var(--ink-3)">Users, access, employees, logs, maintenance</small></div>
        <p class="locknote" style="margin:10px 0 0">One person can hold any combination of these — e.g. Requestor + Division Head. Department assignments for requesting and heading are independent, and a department can have several co-heads.</p>
      </div>
    </div>
  </div>
</div>
