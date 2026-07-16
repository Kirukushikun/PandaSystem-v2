<div>
  <p class="crumb">HR Approver</p>
  <div class="htop">
    <div><h2>HR Approval Queue</h2>
      <p>PANs confirmed by the Division Head and awaiting HR-level approval. No confidentiality distinction applies at this stage.</p></div>
  </div>

  <div class="stats">
    <x-stat value="4" label="Awaiting HR approval" tone="warn" />
    <x-stat value="6" label="With Final Approver" tone="acc" />
    <x-stat value="18" label="Approved this month" tone="ok" />
  </div>

  <div class="bar">
    <x-search-bar placeholder="Search PANs awaiting HR approval…" />
    <x-chip on>Awaiting approval</x-chip><x-chip>Further along</x-chip>
  </div>

  <div class="card"><div class="twrap"><table>
    <thead><tr><th>Reference</th><th>Employee</th><th>Type of Action</th><th>Prepared by</th><th>Basic Pay (From → To)</th><th>Decision</th></tr></thead>
    <tbody>
      <tr><td class="ref">PAN-2026-00339</td>
        <td><div class="who"><b>E. Garcia</b><small>Broiler Operations</small></div></td>
        <td>Promotion</td><td>T. Navarro</td><td class="num">28,100 → 31,600</td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('hr-approval.show', 'PAN-2026-00339') }}" wire:navigate style="text-decoration:none">View</a>
          <button class="btn primary" type="button" onclick="showToast('Approved — forwarded to Final Approver (UI scaffold — nothing is persisted yet).')">Approve</button>
          <x-kebab><x-kebab.item danger data-modal-open="return-prep-modal">Return to HR Preparer…</x-kebab.item></x-kebab>
        </x-row-actions></tr>
      <tr><td class="ref">PAN-2026-00341</td>
        <td><div class="who"><b>N. Fernandez</b><small>Corporate Office</small></div></td>
        <td>Change of Position</td><td>M. Dela Cruz</td><td class="num">28,500 → 34,200</td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('hr-approval.show', 'PAN-2026-00341') }}" wire:navigate style="text-decoration:none">View</a>
          <button class="btn primary" type="button" onclick="showToast('Approved — forwarded to Final Approver (UI scaffold — nothing is persisted yet).')">Approve</button>
          <x-kebab><x-kebab.item danger data-modal-open="return-prep-modal">Return to HR Preparer…</x-kebab.item></x-kebab>
        </x-row-actions></tr>
      <tr><td class="ref">PAN-2026-00335</td>
        <td><div class="who"><b>G. Padilla</b><small>Sales &amp; Distribution</small></div></td>
        <td>Regularization</td><td>T. Navarro</td><td class="num">19,000 → 21,500</td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('hr-approval.show', 'PAN-2026-00335') }}" wire:navigate style="text-decoration:none">View</a>
          <button class="btn primary" type="button" onclick="showToast('Approved — forwarded to Final Approver (UI scaffold — nothing is persisted yet).')">Approve</button>
          <x-kebab><x-kebab.item danger data-modal-open="return-prep-modal">Return to HR Preparer…</x-kebab.item></x-kebab>
        </x-row-actions></tr>
      <tr><td class="ref">PAN-2026-00330</td>
        <td><div class="who"><b>H. Cruz</b><small>Feedmill</small></div></td>
        <td>Discontinuance of Allowance</td><td>M. Dela Cruz</td><td class="num">—</td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('hr-approval.show', 'PAN-2026-00330') }}" wire:navigate style="text-decoration:none">View</a>
          <button class="btn primary" type="button" onclick="showToast('Approved — forwarded to Final Approver (UI scaffold — nothing is persisted yet).')">Approve</button>
          <x-kebab><x-kebab.item danger data-modal-open="return-prep-modal">Return to HR Preparer…</x-kebab.item></x-kebab>
        </x-row-actions></tr>
    </tbody>
  </table></div></div>

  <div class="note info" style="margin-top:14px"><span class="ic">i</span>Returning a PAN here sends it one step back to the HR Preparer with a mandatory reason — not all the way back to the Requestor.</div>

  <x-modal id="return-prep-modal" title="Return to HR Preparer — PAN-2026-00341">
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
