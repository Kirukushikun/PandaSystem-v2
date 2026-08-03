<div>
  <p class="crumb">Requestor · {{ $departments ?: 'no departments assigned' }}</p>
  <div class="htop"><div>
    <h2>{{ $panRequest ? ($panRequest->status === App\Enums\PanStatus::ReturnedToRequestor ? 'Resolve Return — ' : 'Edit Draft — ').$panRequest->reference : 'New PAN Request' }}</h2>
    <p>Employee list is limited to your registered department(s). At least one PDF (up to 3) is required to submit; drafts may be saved without one.</p></div></div>

  @if ($panRequest?->status === App\Enums\PanStatus::ReturnedToRequestor && ($return = $panRequest->returns->last()))
  <div class="note warn"><span class="ic">!</span><span><b>Returned:</b>&nbsp;{{ $return->reason }}@if ($return->details) — {{ $return->details }}@endif</span></div>
  @endif

  <div class="card">
    <div class="formgrid">
      <div class="field"><label>Employee <em>*</em></label>
        <select wire:model.live="employee_id" @error('employee_id') style="border-color:var(--red)" @enderror>
          <option value="">— Select employee —</option>
          @foreach ($this->employees as $employee)
          <option value="{{ $employee->id }}">{{ $employee->name }} — {{ $employee->employee_no }}</option>
          @endforeach
        </select>
        @error('employee_id')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      <div class="field"><label>Employee ID</label><input readonly value="{{ $selectedEmployee?->employee_no }}"><span class="hint">Auto-filled from employee</span></div>
      <div class="field"><label>Department</label><input readonly value="{{ $selectedEmployee?->department->name }}"><span class="hint">Auto-filled from employee</span></div>
      <div class="field"><label>Type of Action <em>*</em></label>
        <select wire:model.live="action_type" @error('action_type') style="border-color:var(--red)" @enderror>
          <option value="">— Select type —</option>
          @foreach ($actionTypes as $type)
          <option value="{{ $type->value }}">{{ $type->label() }}</option>
          @endforeach
        </select>
        @error('action_type')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      <div class="field full"><label>Justification</label>
        <textarea rows="3" wire:model.blur="justification" maxlength="2000" placeholder="Why this action is being requested…" @error('justification') style="border-color:var(--red)" @enderror></textarea>
        @error('justification')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      <div class="field full"><label>Supporting Documents (PDF, up to 3) <em>*</em></label>

        @php($totalCount = ($panRequest?->attachments->count() ?? 0) + count($newAttachments))
        @if ($panRequest?->attachments->count())
        <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:8px">
          @foreach ($panRequest->attachments as $existing)
          <div class="attachrow" wire:key="existing-{{ $existing->id }}"><span class="pdf">PDF</span> {{ $existing->original_name }}
            <small>· {{ number_format($existing->size / 1024) }} KB</small>
            <span class="spacer"></span>
            <button class="btn ghost" type="button" wire:click="removeAttachment({{ $existing->id }})" wire:confirm="Remove {{ $existing->original_name }}?">Remove</button>
          </div>
          @endforeach
        </div>
        @endif

        @foreach ($newAttachments as $i => $file)
        <div class="attachrow" wire:key="new-{{ $i }}"><span class="pdf">PDF</span> {{ $file->getClientOriginalName() }}
          <small>· {{ number_format($file->getSize() / 1024) }} KB</small> <small style="color:var(--accent)">· ready to upload</small>
          <span class="spacer"></span>
          <button class="btn ghost" type="button" wire:click="removeNewAttachment({{ $i }})">Remove</button>
        </div>
        @endforeach

        @if ($totalCount < 3)
        <label class="upload" style="cursor:pointer;display:block{{ $errors->has('newAttachments') ? ';border-color:var(--red)' : '' }}">
          <input type="file" accept="application/pdf" wire:model="newAttachments" multiple hidden>
          <b>Choose PDF{{ $totalCount > 0 ? '(s)' : '' }}</b> or drag {{ $totalCount > 0 ? 'them' : 'it' }} here — performance evaluation, recommendation memo, etc.
          <small style="display:block;color:var(--ink-3)">{{ 3 - $totalCount }} of 3 slots remaining</small>
        </label>
        <x-upload-progress />
        @else
        <small style="color:var(--ink-3)">3 of 3 attached — remove one to add another.</small>
        @endif
        @error('newAttachments')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror
        @error('newAttachments.*')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
    </div>
    <div class="formfoot">
      @if ($panRequest?->status !== App\Enums\PanStatus::ReturnedToRequestor)
      <button class="btn" type="button" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="newAttachments,saveDraft">Save as Draft</button>
      @endif
      <div class="spacer"></div>
      <a class="btn" href="{{ route('requests.index') }}" wire:navigate style="text-decoration:none">Cancel</a>
      <button class="btn primary" type="button" wire:click="submit" wire:loading.attr="disabled" wire:target="newAttachments,submit">
        {{ $panRequest?->status === App\Enums\PanStatus::ReturnedToRequestor ? 'Resubmit to Division Head' : 'Submit to Division Head' }}
      </button>
    </div>
  </div>
</div>
