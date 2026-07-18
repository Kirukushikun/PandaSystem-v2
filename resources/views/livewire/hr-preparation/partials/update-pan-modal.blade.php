{{-- Shared "Update PAN" modal (Employees lens + PAN history). Expects $updateEmployee
     (the armed target, nullable) from the component using the StartsHrPan trait. --}}
<x-modal id="update-modal" title="Update PAN{{ $updateEmployee ? ' — '.$updateEmployee->name.' ('.$updateEmployee->employee_no.')' : '' }}">
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
    <div class="field"><label>Supporting Document (PDF) <em>*</em></label>
      <label class="upload" style="cursor:pointer">
        <b>{{ $updateAttachment ? $updateAttachment->getClientOriginalName() : 'Choose a PDF' }}</b>
        {{ $updateAttachment ? '— ready to upload' : 'or drag it here — wage order issuance, memo, etc.' }}
        <input type="file" accept="application/pdf" wire:model="updateAttachment" style="display:none">
      </label>
      @error('updateAttachment')<span class="hint" style="color:var(--bad)">{{ $message }}</span>@enderror</div>
  </div>
  <x-slot:footer>
    <button class="btn" type="button" data-close>Cancel</button>
    <div class="spacer"></div>
    <button class="btn primary" type="button" wire:click="createHrPan" wire:loading.attr="disabled" wire:target="updateAttachment,createHrPan">Create &amp; Prepare</button>
  </x-slot:footer>
</x-modal>
