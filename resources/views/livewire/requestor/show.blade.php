<div>
  <p class="crumb">Requestor · {{ $departments ?: 'no departments assigned' }}</p>
  <div class="htop">
    <div><h2>View Request — <span class="ref" style="font-size:18px">{{ $panRequest->reference }}</span></h2>
    <p>Your request as submitted. It stays read-only unless the Division Head returns it for correction.</p></div>
    <div class="spacer"></div>
    <x-status-pill :status="$panRequest->status->value" />
  </div>

  <x-stage-tracker :stages="$stages" :current="$current" />

  <x-pan.return-history :returns="$panRequest->returns" />

  <div class="card">
    <x-pan.request-details
      :employee="$panRequest->employee->name"
      :employee-id="$panRequest->employee->employee_no"
      :department="$panRequest->employee->department->name"
      :action="$panRequest->action_type->label()"
      :submitted="$panRequest->submitted_at?->format('M j, Y · H:i') ?? 'Not yet submitted'"
      :justification="$panRequest->justification ?? '—'"
      :attachments="$panRequest->attachments"
      :pan-reference="$panRequest->reference" />
    <div class="formfoot">
      <a class="btn" href="{{ route('requests.index') }}" wire:navigate style="text-decoration:none">← Back to my requests</a>
      <div class="spacer"></div>
      @if ($panRequest->status === App\Enums\PanStatus::Draft)
        <a class="btn primary" href="{{ route('requests.edit', $panRequest->reference) }}" wire:navigate style="text-decoration:none">Edit draft</a>
      @elseif ($panRequest->status === App\Enums\PanStatus::ReturnedToRequestor)
        <button class="btn danger" type="button" wire:click="withdraw" wire:confirm="Withdraw {{ $panRequest->reference }}? It stays on record but leaves the workflow.">Withdraw</button>
        <a class="btn primary" href="{{ route('requests.edit', $panRequest->reference) }}" wire:navigate style="text-decoration:none">Resolve &amp; Resubmit</a>
      @endif
    </div>
  </div>
</div>
