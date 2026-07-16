{{-- The request-as-submitted block, shared by every role's Show view (Requestor, Division Head,
     HR Prep/Approver, Final Approver). Renders inside a .card. Pass requested-by only for
     reviewer views — the Requestor's own view omits it. `sect` adds the "Request details"
     section header used when the PAN extension follows below. --}}
@props([
    'employee', 'employeeId', 'department', 'action', 'submitted',
    'requestedBy' => null,
    'justification' => '',
    'justificationRows' => 3,
    'document', 'documentSize',
    'sect' => false,
])
@if ($sect)<div class="sect">Request details</div>@endif
<div class="formgrid" @if ($sect) style="padding-top:10px" @endif>
  <div class="field"><label>Employee</label><input readonly value="{{ $employee }}"></div>
  <div class="field"><label>Employee ID</label><input readonly value="{{ $employeeId }}"></div>
  <div class="field"><label>Department</label><input readonly value="{{ $department }}"></div>
  <div class="field"><label>Type of Action</label><input readonly value="{{ $action }}"></div>
  @if ($requestedBy)
  <div class="field"><label>Requested by</label><input readonly value="{{ $requestedBy }}"></div>
  @endif
  <div class="field"><label>Submitted</label><input readonly value="{{ $submitted }}"></div>
  <div class="field full"><label>Justification</label>
    <textarea rows="{{ $justificationRows }}" readonly>{{ $justification }}</textarea></div>
  <div class="field full"><label>Supporting Document</label>
    <div class="attachrow"><span class="pdf">PDF</span> {{ $document }} <small>· {{ $documentSize }}</small>
      <span class="spacer"></span><button class="btn ghost" type="button">Open</button></div></div>
</div>
