<div>
  <p class="crumb">Help</p>
  <div class="htop">
    <div>
      <h2>Glossary</h2>
      <p>Placeholder — real Glossary content lands in scaffold step 8. Below is a temporary
        preview of the shared components built in step 1, so they can be checked against the mockup.</p>
    </div>
  </div>

  {{-- ============ TEMPORARY component preview (delete in step 8) ============ --}}

  <div class="card" style="padding:18px;margin-bottom:18px">
    <p class="crumb">x-status-pill — all statuses</p>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
      <x-status-pill status="draft" />
      <x-status-pill status="with-division-head" />
      <x-status-pill status="returned-to-requestor" />
      <x-status-pill status="awaiting-tag" />
      <x-status-pill status="in-preparation" />
      <x-status-pill status="for-confirmation" />
      <x-status-pill status="returned-to-preparer" />
      <x-status-pill status="for-hr-approval" />
      <x-status-pill status="for-final-approval" />
      <x-status-pill status="approved" />
      <x-status-pill status="served" />
      <x-status-pill status="unserved" />
      <x-status-pill status="filed" />
      <x-status-pill status="withdrawn" />
      <x-status-pill status="voided" />
      <x-status-pill status="with-division-head">Awaiting your decision (label override)</x-status-pill>
      <x-status-pill tone="ret">Failed — bad password (freeform)</x-status-pill>
    </div>
  </div>

  <div class="card" style="padding:18px;margin-bottom:18px">
    <p class="crumb">x-tag-dot</p>
    <p style="margin:0;font-size:13px">
      <x-tag-dot tag="manila" /> Manila (confidential) &nbsp;·&nbsp;
      <x-tag-dot tag="tarlac" /> Tarlac (routine) &nbsp;·&nbsp;
      <x-tag-dot /> Untagged
    </p>
  </div>

  <div class="card" style="padding:18px;margin-bottom:18px">
    <p class="crumb">x-stage-tracker</p>
    <x-stage-tracker :stages="['Submitted','Division Head','HR Preparation','HR Approval','Final Approval']" current="Division Head" />
    <x-stage-tracker :stages="['Submitted','Division approved','HR prepared','DH Confirmation','HR Approval','Final Approval']" current="DH Confirmation" />
    <x-stage-tracker :stages="['Submitted','Division Head','HR Preparation','HR Approval','Final Approval']" current="*" style="margin-bottom:0" />
  </div>

  <div class="card" style="padding:18px;margin-bottom:18px">
    <p class="crumb">x-stat (in a .stats strip)</p>
    <div class="stats" style="margin-bottom:0">
      <x-stat value="8" label="Total requests" />
      <x-stat value="3" label="In progress" tone="warn" />
      <x-stat value="1" label="Returned to you" tone="bad" />
      <x-stat value="4" label="Completed" tone="ok" />
      <x-stat value="₱ 645/day" label="Current basic pay" tone="acc" />
    </div>
  </div>

  <div class="card" style="padding:18px;margin-bottom:18px">
    <p class="crumb">x-search-bar + x-chip (in a .bar)</p>
    <div class="bar" style="margin-bottom:0">
      <x-search-bar placeholder="Search by employee, reference no., or action type…" />
      <x-chip on>All</x-chip><x-chip>In progress</x-chip><x-chip>Completed</x-chip>
    </div>
  </div>

  <div class="card" style="margin-bottom:18px">
    <div class="twrap"><table>
      <thead><tr><th>Reference</th><th>Employee</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <tr>
          <td class="ref">PAN-2026-00358</td>
          <td><div class="who"><b>R. Villanueva</b><small>EMP-10422 · Broiler Operations</small></div></td>
          <td><x-status-pill status="draft" /></td>
          <x-row-actions>
            <a class="btn ghost" href="#">View</a>
            <button class="btn primary" type="button">Edit</button>
            <x-kebab>
              <x-kebab.item danger>Delete draft</x-kebab.item>
            </x-kebab>
          </x-row-actions>
        </tr>
        <tr>
          <td class="ref">PAN-2026-00347</td>
          <td><div class="who"><b>K. Reyes</b><small>EMP-10310 · Hatchery</small></div></td>
          <td><x-status-pill status="with-division-head">Awaiting your decision</x-status-pill></td>
          <x-row-actions>
            <a class="btn ghost" href="#">View</a>
            <button class="btn primary" type="button">Approve</button>
            <x-kebab>
              <x-kebab.item danger data-modal-open="demo-modal">Return to Requestor…</x-kebab.item>
            </x-kebab>
          </x-row-actions>
        </tr>
      </tbody>
    </table></div>
  </div>

  <div class="card" style="padding:18px">
    <p class="crumb">x-modal</p>
    <button class="btn" type="button" data-modal-open="demo-modal">Open demo modal</button>
  </div>

  <x-modal id="demo-modal" title="Return to Requestor — PAN-2026-00347">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="field"><label>Reason <em>*</em></label>
        <select><option>Incomplete supporting document</option><option>Wrong employee selected</option><option>Wrong type of action</option><option>Needs stronger justification</option><option>Custom reason…</option></select></div>
      <div class="field"><label>Details (optional)</label>
        <textarea rows="3"></textarea></div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" data-close>Cancel</button>
      <div class="spacer"></div>
      <button class="btn danger" type="button" data-close>Return to Requestor</button>
    </x-slot:footer>
  </x-modal>

  {{-- ============ end temporary preview ============ --}}
</div>
