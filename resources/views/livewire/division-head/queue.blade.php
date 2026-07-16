<div>
  <p class="crumb">Division Head · Broiler Operations</p>
  <div class="htop">
    <div><h2>Department Queue</h2>
      <p>Every PAN for your department, across all stages. You can only act while a request is awaiting your decision; afterwards it stays visible read-only.</p></div>
  </div>

  <div class="stats">
    <x-stat value="3" label="Awaiting your decision" tone="warn" />
    <x-stat value="7" label="In later stages" tone="acc" />
    <x-stat value="2" label="Completed this month" tone="ok" />
  </div>

  {{-- Regular Division Head sees only their department's non-Manila PANs. A DH Head sees the
       inverse — ONLY Manila-tagged PANs across all departments (enforced by policy later). --}}
  <div class="note info"><span class="ic">i</span>Requests tagged confidential ("Manila") never appear in this queue — they are routed to the designated DH Head, even for your own department.</div>

  <div class="bar">
    <x-search-bar placeholder="Search this department's requests…" />
    <x-chip on>Needs my action</x-chip><x-chip>All stages</x-chip><x-chip>Completed</x-chip>
  </div>

  <div class="card"><div class="twrap"><table>
    <thead><tr><th>Reference</th><th>Employee</th><th>Type of Action</th><th>Requestor</th><th>Status</th><th>Decision</th></tr></thead>
    <tbody>
      <tr><td class="ref">PAN-2026-00347</td>
        <td><div class="who"><b>A. Santos</b><small>EMP-10301</small></div></td>
        <td>Salary Alignment</td><td>M. Dela Cruz</td><td><x-status-pill status="with-division-head">Awaiting your decision</x-status-pill></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('division.show', 'PAN-2026-00347') }}" wire:navigate style="text-decoration:none">View</a>
          <button class="btn primary" type="button" onclick="showToast('Approved and forwarded to HR Preparation (UI scaffold — nothing is persisted yet).')">Approve</button>
          <x-kebab><x-kebab.item danger data-modal-open="return-modal">Return to Requestor…</x-kebab.item></x-kebab>
        </x-row-actions></tr>
      <tr><td class="ref">PAN-2026-00344</td>
        <td><div class="who"><b>P. Aquino</b><small>EMP-10255</small></div></td>
        <td>Lateral Transfer</td><td>K. Reyes</td><td><x-status-pill status="with-division-head">Awaiting your decision</x-status-pill></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('division.show', 'PAN-2026-00344') }}" wire:navigate style="text-decoration:none">View</a>
          <button class="btn primary" type="button" onclick="showToast('Approved and forwarded to HR Preparation (UI scaffold — nothing is persisted yet).')">Approve</button>
          <x-kebab><x-kebab.item danger data-modal-open="return-modal">Return to Requestor…</x-kebab.item></x-kebab>
        </x-row-actions></tr>
      <tr><td class="ref">PAN-2026-00339</td>
        <td><div class="who"><b>E. Garcia</b><small>EMP-10198</small></div></td>
        <td>Promotion</td><td>K. Reyes</td><td><x-status-pill status="for-confirmation">For your confirmation (HR-prepared)</x-status-pill></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('division.show', 'PAN-2026-00339') }}" wire:navigate style="text-decoration:none">View</a>
          <button class="btn primary" type="button" onclick="showToast('Confirmed — forwarded to HR Approver (UI scaffold — nothing is persisted yet).')">Confirm</button>
          <x-kebab><x-kebab.item danger data-modal-open="dispute-modal">Dispute…</x-kebab.item></x-kebab>
        </x-row-actions></tr>
      <tr><td class="ref">PAN-2026-00332</td>
        <td><div class="who"><b>C. Mercado</b><small>EMP-10119</small></div></td>
        <td>Interim Allowance</td><td>M. Dela Cruz</td><td><x-status-pill status="in-preparation" /></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('division.show', 'PAN-2026-00332') }}" wire:navigate style="text-decoration:none">View</a>
        </x-row-actions></tr>
      <tr><td class="ref">PAN-2026-00311</td>
        <td><div class="who"><b>D. Torres</b><small>EMP-10064</small></div></td>
        <td>Regularization</td><td>K. Reyes</td><td><x-status-pill status="approved" /></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('division.show', 'PAN-2026-00311') }}" wire:navigate style="text-decoration:none">View</a>
        </x-row-actions></tr>
    </tbody>
  </table></div></div>

  <x-modal id="return-modal" title="Return to Requestor — PAN-2026-00347">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="field"><label>Reason <em>*</em></label>
        <select><option>Incomplete supporting document</option><option>Wrong employee selected</option><option>Wrong type of action</option><option>Needs stronger justification</option><option>Custom reason…</option></select></div>
      <div class="field"><label>Details (optional)</label>
        <textarea rows="3">Attach the signed evaluation form — the uploaded copy is missing page 2.</textarea></div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" data-close>Cancel</button>
      <div class="spacer"></div>
      <button class="btn danger" type="button" data-close onclick="showToast('Returned to Requestor with reason (UI scaffold — nothing is persisted yet).')">Return to Requestor</button>
    </x-slot:footer>
  </x-modal>

  <x-modal id="dispute-modal" title="Dispute — PAN-2026-00339">
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
</div>
