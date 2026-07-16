{{-- Static sample body (A. Santos / Salary Alignment) until the real build; the reference
     in the heading comes from the route so row links feel real. --}}
<div>
  <p class="crumb">Requestor · Broiler Operations, Hatchery</p>
  <div class="htop">
    <div><h2>View Request — <span class="ref" style="font-size:18px">{{ $pan }}</span></h2>
    <p>Your request as submitted. It stays read-only unless the Division Head returns it for correction.</p></div>
    <div class="spacer"></div>
    <x-status-pill status="with-division-head" />
  </div>

  <x-stage-tracker :stages="['Submitted','Division Head','HR Preparation','HR Approval','Final Approval']" current="Division Head" />

  <div class="card">
    <div class="formgrid">
      <div class="field"><label>Employee</label><input readonly value="A. Santos"></div>
      <div class="field"><label>Employee ID</label><input readonly value="EMP-10301"></div>
      <div class="field"><label>Department</label><input readonly value="Broiler Operations"></div>
      <div class="field"><label>Type of Action</label><input readonly value="Salary Alignment"></div>
      <div class="field"><label>Submitted</label><input readonly value="Jul 8, 2026 · 09:41"></div>
      <div class="field full"><label>Justification</label>
        <textarea rows="3" readonly>Current pay is below the approved 2026 salary structure for Farm Technician II. Requesting alignment to the job-level minimum.</textarea></div>
      <div class="field full"><label>Supporting Document</label>
        <div class="attachrow"><span class="pdf">PDF</span> salary_structure_alignment_santos.pdf <small>· 268 KB</small>
          <span class="spacer"></span><button class="btn ghost" type="button">Open</button></div></div>
    </div>
    <div class="formfoot">
      <a class="btn" href="{{ route('requests.index') }}" wire:navigate style="text-decoration:none">← Back to my requests</a>
      <div class="spacer"></div>
      <button class="btn" disabled>Read-only while in workflow</button>
    </div>
  </div>
</div>
