{{-- Static S. Lim sample until the real build; the employee number comes from the route. --}}
<div>
  <p class="crumb">HR Preparation · signed in as HR Head Preparer</p>
  <div class="htop">
    <div><h2>PAN History — S. Lim <span class="ref" style="font-size:14px">{{ $employee }}</span></h2>
    <p>Every PAN on record for this employee, newest first. Each PAN's "From" values chain from the previous one's "To" values.</p></div>
    <div class="spacer"></div>
    <button class="btn primary" type="button" data-modal-open="update-modal">Update PAN</button>
  </div>

  <div class="stats">
    <x-stat value="4" label="Total PANs" />
    <x-stat value="3" label="Filed" tone="ok" />
    <x-stat value="1" label="In rework" tone="bad" />
    <x-stat value="₱ 645/day" label="Current basic pay" tone="acc" />
  </div>

  <div class="card"><div class="twrap"><table>
    <thead><tr><th>Reference</th><th>Type of Action</th><th>Effectivity</th><th>Key Change</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <tr><td class="ref">PAN-2026-00338</td><td>Wage Order</td><td>Jul 15, 2026</td>
        <td class="num">₱ 610/day → ₱ 645/day</td><td><x-status-pill status="returned-to-preparer">Returned — rework</x-status-pill></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('preparation.show', 'PAN-2026-00338') }}" wire:navigate style="text-decoration:none">View</a>
          <x-print-btn onclick="showToast('Print view arrives in scaffold step 9.')" />
        </x-row-actions></tr>
      <tr><td class="ref">PAN-2025-01102</td><td>Wage Order</td><td>Jul 1, 2025</td>
        <td class="num">₱ 585/day → ₱ 610/day</td><td><x-status-pill status="filed" /></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('preparation.show', 'PAN-2025-01102') }}" wire:navigate style="text-decoration:none">View</a>
          <x-print-btn onclick="showToast('Print view arrives in scaffold step 9.')" />
        </x-row-actions></tr>
      <tr><td class="ref">PAN-2024-00761</td><td>Promotion</td><td>Mar 16, 2024</td>
        <td>Mill Helper → Mill Operator</td><td><x-status-pill status="filed" /></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('preparation.show', 'PAN-2024-00761') }}" wire:navigate style="text-decoration:none">View</a>
          <x-print-btn onclick="showToast('Print view arrives in scaffold step 9.')" />
        </x-row-actions></tr>
      <tr><td class="ref">PAN-2023-00287</td><td>Regularization</td><td>Feb 1, 2023</td>
        <td>Probationary → Regular</td><td><x-status-pill status="filed" /></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('preparation.show', 'PAN-2023-00287') }}" wire:navigate style="text-decoration:none">View</a>
          <x-print-btn onclick="showToast('Print view arrives in scaffold step 9.')" />
        </x-row-actions></tr>
    </tbody>
  </table></div></div>

  <div style="display:flex;margin-top:14px">
    <a class="btn" href="{{ route('employees.index') }}" wire:navigate style="text-decoration:none">← Back to employees</a>
  </div>

  <x-modal id="update-modal" title="Update PAN — S. Lim ({{ $employee }})">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="note info" style="margin:0"><span class="ic">i</span>This starts a new PAN directly at HR Preparation — no requestor or Division Head approval step. Employee details carry over automatically.</div>
      <div class="field"><label>Type of Action <em>*</em></label>
        <select><option>Wage Order</option><option>Regularization</option><option>Salary Alignment</option><option>Lateral Transfer</option>
        <option>Developmental Assignment</option><option>Interim Allowance</option><option>Promotion</option><option>Training Status</option>
        <option>Confirmation of Appointment</option><option>Change of Position</option><option>Discontinuance of Allowance</option>
        <option>Confirmation of Development Assignment</option><option>Other Allowances</option></select></div>
      <div class="field"><label>Supporting Document (PDF) <em>*</em></label>
        <div class="upload"><b>Choose a PDF</b> or drag it here — wage order issuance, memo, etc.</div></div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" data-close>Cancel</button>
      <div class="spacer"></div>
      <a class="btn primary" href="{{ route('preparation.edit', 'PAN-2026-00341') }}" wire:navigate style="text-decoration:none">Create &amp; Prepare</a>
    </x-slot:footer>
  </x-modal>
</div>
