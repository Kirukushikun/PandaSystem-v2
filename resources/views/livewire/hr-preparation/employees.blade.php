{{-- HR-side, read-only lens over the master roster managed in Administration → Employees
     (add/edit/remove happens there, not here). "Update PAN" starts a new PAN directly at HR
     Preparation (origin = 'hr'), skipping the Requestor and Division Head stages. --}}
<div>
  <p class="crumb">HR Preparation · signed in as HR Head Preparer</p>
  <div class="htop">
    <div><h2>Employees</h2>
    <p>Every employee on the roster, with their PAN history. "Update PAN" starts a new PAN directly from HR — no requestor needed — typically for Wage Orders.</p></div>
    <div class="spacer"></div>
    <a class="btn" href="{{ route('preparation.queue') }}" wire:navigate style="text-decoration:none">← Back to queue</a>
  </div>

  <div class="bar">
    <x-search-bar placeholder="Search employees by name, ID, or department…" />
    <x-chip on>All departments</x-chip><x-chip>Broiler Operations</x-chip><x-chip>Feedmill</x-chip><x-chip>Hatchery</x-chip>
  </div>

  <div class="card"><div class="twrap"><table>
    <thead><tr><th>Employee</th><th>Department</th><th>Position</th><th>Farm / Site</th><th>PAN Records</th><th></th></tr></thead>
    <tbody>
      <tr><td><div class="who"><b>S. Lim</b><small>EMP-10233</small></div></td>
        <td>Feedmill</td><td>Mill Operator</td><td>Sta. Maria Feedmill</td><td class="num">4</td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('employees.history', 'EMP-10233') }}" wire:navigate style="text-decoration:none">View PANs</a>
          <button class="btn primary" type="button" data-modal-open="update-modal">Update PAN</button>
        </x-row-actions></tr>
      <tr><td><div class="who"><b>N. Fernandez</b><small>EMP-10490</small></div></td>
        <td>Corporate Office</td><td>HR Generalist</td><td>Main Office</td><td class="num">3</td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('employees.history', 'EMP-10490') }}" wire:navigate style="text-decoration:none">View PANs</a>
          <button class="btn primary" type="button" data-modal-open="update-modal">Update PAN</button>
        </x-row-actions></tr>
      <tr><td><div class="who"><b>D. Torres</b><small>EMP-10064</small></div></td>
        <td>Broiler Operations</td><td>Poultry Caretaker</td><td>San Rafael Farm</td><td class="num">2</td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('employees.history', 'EMP-10064') }}" wire:navigate style="text-decoration:none">View PANs</a>
          <button class="btn primary" type="button" data-modal-open="update-modal">Update PAN</button>
        </x-row-actions></tr>
      <tr><td><div class="who"><b>J. Ramos</b><small>EMP-10387</small></div></td>
        <td>Hatchery</td><td>Hatchery Aide</td><td>San Rafael Farm</td><td class="num">1</td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('employees.history', 'EMP-10387') }}" wire:navigate style="text-decoration:none">View PANs</a>
          <button class="btn primary" type="button" data-modal-open="update-modal">Update PAN</button>
        </x-row-actions></tr>
      <tr><td><div class="who"><b>L. Bautista</b><small>EMP-10077</small></div></td>
        <td>Broiler Operations</td><td>Farm Technician I</td><td>San Rafael Farm</td><td class="num">2</td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('employees.history', 'EMP-10077') }}" wire:navigate style="text-decoration:none">View PANs</a>
          <button class="btn primary" type="button" data-modal-open="update-modal">Update PAN</button>
        </x-row-actions></tr>
    </tbody>
  </table></div></div>

  <x-modal id="update-modal" title="Update PAN — S. Lim (EMP-10233)">
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
