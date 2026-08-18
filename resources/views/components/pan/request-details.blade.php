{{-- The request-as-submitted block, shared by every role's Show view (Requestor, Division Head,
     HR Prep/Approver, Final Approver). Renders inside a .card. Pass requested-by only for
     reviewer views — the Requestor's own view omits it. `sect` adds the "Request details"
     section header used when the PAN extension follows below. --}}
@props([
    'employee', 'employeeId', 'department', 'action', 'submitted',
    'requestedBy' => null,
    'justification' => '',
    'justificationRows' => 3,
    'attachments' => [], // Collection<PanAttachment>
    'panReference' => null, // needed to build each attachment's download URL
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
  <div class="field full"><label>Supporting Document{{ count($attachments) === 1 ? '' : 's' }}</label>
    @forelse ($attachments as $file)
    <div class="attachrow" style="margin-bottom:4px"><span class="pdf">PDF</span> {{ $file->original_name }}
      <small>· {{ number_format($file->size / 1024) }} KB</small>
      <span class="spacer"></span>
      <a class="btn ghost" href="{{ route('pan.attachment', [$panReference, $file->id]) }}" target="_blank" rel="noopener" style="text-decoration:none">Open</a>
    </div>
    @empty
    <div class="attachrow"><small style="color:var(--ink-3)">No documents attached yet — at least one is required before submitting.</small></div>
    @endforelse</div>
</div>
