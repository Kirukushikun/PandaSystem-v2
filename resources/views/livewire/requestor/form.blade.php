<div>
  <p class="crumb">Requestor · {{ $departments ?: 'no departments assigned' }}</p>
  <div class="htop"><div>
    <h2>{{ $panRequest ? ($panRequest->status === App\Enums\PanStatus::ReturnedToRequestor ? 'Resolve Return — ' : 'Edit Draft — ').$panRequest->reference : 'New PAN Request' }}</h2>
    <p>Employee list is limited to your registered department(s). A PDF attachment is required to submit; drafts may be saved without one.</p></div></div>

  @if ($panRequest?->status === App\Enums\PanStatus::ReturnedToRequestor && ($return = $panRequest->returns->last()))
  <div class="note warn"><span class="ic">!</span><span><b>Returned:</b>&nbsp;{{ $return->reason }}@if ($return->details) — {{ $return->details }}@endif</span></div>
  @endif

  <div class="card">
    <div class="formgrid">
      <div class="field"><label>Employee <em>*</em></label>
        <select wire:model.live="employee_id">
          <option value="">— Select employee —</option>
          @foreach ($this->employees as $employee)
          <option value="{{ $employee->id }}">{{ $employee->name }} — {{ $employee->employee_no }}</option>
          @endforeach
        </select>
        @error('employee_id')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      <div class="field"><label>Employee ID</label><input readonly value="{{ $selectedEmployee?->employee_no }}"><span class="hint">Auto-filled from employee</span></div>
      <div class="field"><label>Department</label><input readonly value="{{ $selectedEmployee?->department->name }}"><span class="hint">Auto-filled from employee</span></div>
      <div class="field"><label>Type of Action <em>*</em></label>
        <select wire:model="action_type">
          <option value="">— Select type —</option>
          @foreach ($actionTypes as $type)
          <option value="{{ $type->value }}">{{ $type->label() }}</option>
          @endforeach
        </select>
        @error('action_type')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      <div class="field full"><label>Justification</label>
        <textarea rows="3" wire:model="justification" placeholder="Why this action is being requested…"></textarea>
        @error('justification')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      <div class="field full"><label>Supporting Document (PDF) <em>*</em></label>
        <label class="upload" style="cursor:pointer;display:block">
          <input type="file" accept="application/pdf" wire:model="attachment" hidden>
          <b>Choose a PDF</b> or drag it here — performance evaluation, recommendation memo, etc.
          @if ($attachment)
            <br>{{ $attachment->getClientOriginalName() }} · {{ number_format($attachment->getSize() / 1024) }} KB
          @elseif ($panRequest?->attachment_path)
            <br>{{ basename($panRequest->attachment_path) }} (already uploaded — choose a file to replace it)
          @endif
          <span wire:loading wire:target="attachment"><br>Uploading…</span>
        </label>
        @error('attachment')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
    </div>
    <div class="formfoot">
      @if ($panRequest?->status !== App\Enums\PanStatus::ReturnedToRequestor)
      <button class="btn" type="button" wire:click="saveDraft" wire:loading.attr="disabled">Save as Draft</button>
      @endif
      <div class="spacer"></div>
      <a class="btn" href="{{ route('requests.index') }}" wire:navigate style="text-decoration:none">Cancel</a>
      <button class="btn primary" type="button" wire:click="submit" wire:loading.attr="disabled">
        {{ $panRequest?->status === App\Enums\PanStatus::ReturnedToRequestor ? 'Resubmit to Division Head' : 'Submit to Division Head' }}
      </button>
    </div>
  </div>
</div>
