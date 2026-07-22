{{-- Shared "Update PAN" modal (Employees lens + PAN history). Expects $updateEmployee
     (the armed target, nullable) from the component using the StartsHrPan trait. --}}
<x-modal id="update-modal" :open="$showUpdateModal" close="$set('showUpdateModal', false)" title="Update PAN{{ $updateEmployee ? ' — '.$updateEmployee->name.' ('.$updateEmployee->employee_no.')' : '' }}">
  <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
    <div class="note info" style="margin:0"><span class="ic">i</span>This starts a new PAN directly at HR Preparation — no requestor or Division Head approval step. Employee details carry over automatically.</div>
    <div class="field"><label>Type of Action <em>*</em></label>
      <select wire:model="updateAction">
        <option value="">Select…</option>
        @foreach (App\Enums\ActionType::cases() as $type)
        <option value="{{ $type->value }}">{{ $type->label() }}</option>
        @endforeach
      </select>
      @error('updateAction')<span class="hint" style="color:var(--bad)">{{ $message }}</span>@enderror</div>
    <div class="field"><label>Supporting Documents (PDF, up to 3) <em>*</em></label>

      @foreach ($updateAttachments as $i => $file)
      <div class="attachrow" wire:key="upd-{{ $i }}"><span class="pdf">PDF</span> {{ $file->getClientOriginalName() }}
        <small>· {{ number_format($file->getSize() / 1024) }} KB</small> <small style="color:var(--accent)">· ready to upload</small>
        <span class="spacer"></span>
        <button class="btn ghost" type="button" wire:click="removeUpdateAttachment({{ $i }})">Remove</button>
      </div>
      @endforeach

      @if (count($updateAttachments) < 3)
      <label class="upload" style="cursor:pointer">
        <b>Choose PDF{{ count($updateAttachments) ? '(s)' : '' }}</b>
        or drag {{ count($updateAttachments) ? 'them' : 'it' }} here — wage order issuance, memo, etc.
        <small style="display:block;color:var(--ink-3)">{{ 3 - count($updateAttachments) }} of 3 slots remaining</small>
        <input type="file" accept="application/pdf" wire:model="updateAttachments" multiple style="display:none">
      </label>
      <x-upload-progress />
      @else
      <small style="color:var(--ink-3)">3 of 3 attached — remove one to add another.</small>
      @endif
      @error('updateAttachments')<span class="hint" style="color:var(--bad)">{{ $message }}</span>@enderror
      @error('updateAttachments.*')<span class="hint" style="color:var(--bad)">{{ $message }}</span>@enderror</div>
  </div>
  <x-slot:footer>
    <button class="btn" type="button" wire:click="$set('showUpdateModal', false)">Cancel</button>
    <div class="spacer"></div>
    <button class="btn primary" type="button" wire:click="createHrPan" wire:loading.attr="disabled" wire:target="updateAttachments,createHrPan">Create &amp; Prepare</button>
  </x-slot:footer>
</x-modal>
