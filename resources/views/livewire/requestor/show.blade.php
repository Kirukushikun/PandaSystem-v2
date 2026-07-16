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
    <x-pan.request-details
      employee="A. Santos" employee-id="EMP-10301" department="Broiler Operations"
      action="Salary Alignment" submitted="Jul 8, 2026 · 09:41"
      justification="Current pay is below the approved 2026 salary structure for Farm Technician II. Requesting alignment to the job-level minimum."
      document="salary_structure_alignment_santos.pdf" document-size="268 KB" />
    <div class="formfoot">
      <a class="btn" href="{{ route('requests.index') }}" wire:navigate style="text-decoration:none">← Back to my requests</a>
      <div class="spacer"></div>
      <button class="btn" disabled>Read-only while in workflow</button>
    </div>
  </div>
</div>
