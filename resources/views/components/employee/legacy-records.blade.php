{{-- Legacy Records panel — scanned/legacy PAN copies attached to an Employee (not a
     specific PanRequest). HR Head only for upload/remove; Final Approver gets the same
     panel with $canManage=false (view/download only). Never rendered at all for a
     plain HR Preparer — that's decided by the parent component before including this. --}}
@props(['employee', 'legacyRecords', 'canManage' => false, 'showUploadModal' => false, 'newRecords' => []])
<div class="card legacypanel" style="margin-top:18px">
  <div class="htop" style="padding:14px 16px 0">
    <div>
      <h2 style="font-size:15px">Legacy Records</h2>
      <p>Scanned copies of PANs that predate this system. <x-tag-dot tag="manila" /> Manila-confidentiality only, for now.</p>
    </div>
    <div class="spacer"></div>
    @if ($canManage)
    <button class="btn primary" type="button" wire:click="startLegacyUpload">+ Upload Legacy Record</button>
    @endif
  </div>

  @if ($legacyRecords->isEmpty())
  <div class="legacyempty" style="padding:20px 16px;text-align:center;color:var(--ink-3);font-size:13px">No legacy records on file yet.</div>
  @else
  <div style="padding:10px 16px 14px;display:flex;flex-direction:column;gap:6px">
    @foreach ($legacyRecords as $record)
    @php($isImage = in_array(strtolower(pathinfo($record->original_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']))
    <div class="attachrow" wire:key="legacy-{{ $record->id }}">
      <span class="pdf" @if ($isImage) style="background:var(--blue-soft);color:var(--blue)" @endif>{{ $isImage ? 'IMG' : 'PDF' }}</span>
      {{ $record->original_name }}
      <small>· {{ number_format($record->size / 1024) }} KB · uploaded by {{ $record->uploadedBy->name }} · {{ $record->created_at->format('M j, Y') }}</small>
      <span class="spacer"></span>
      <a class="btn ghost" href="{{ route('employees.legacy-record', [$employee->employee_no, $record->id]) }}" target="_blank" rel="noopener" style="text-decoration:none">Open</a>
      @if ($canManage)
      <button class="btn ghost" type="button" style="color:var(--red)" wire:click="removeLegacyRecord({{ $record->id }})" wire:confirm="Remove {{ $record->original_name }}?">Remove</button>
      @endif
    </div>
    @endforeach
  </div>
  @endif
</div>

@if ($canManage)
<x-modal id="legacy-upload-modal" title="Upload Legacy Record" :open="$showUploadModal" close="$set('showLegacyUploadModal', false)">
  <div style="padding:16px 18px">
    <p class="note manila"><span class="ic">●</span> Saved as Manila confidentiality — visible to HR Head and Final Approver only.</p>

    @foreach ($newRecords as $i => $file)
    <div class="attachrow" wire:key="new-legacy-{{ $i }}" style="margin-bottom:6px"><span class="pdf">FILE</span> {{ $file->getClientOriginalName() }}
      <small style="color:var(--accent)">· ready to upload</small>
      <span class="spacer"></span>
      <button class="btn ghost" type="button" wire:click="removeNewLegacyRecord({{ $i }})">Remove</button>
    </div>
    @endforeach

    <label class="upload" style="cursor:pointer;display:block{{ $errors->has('newLegacyRecords') ? ';border-color:var(--red)' : '' }}">
      <input type="file" accept=".pdf,.jpg,.jpeg,.png" wire:model="newLegacyRecords" multiple hidden>
      <b>Choose files</b> or drag them here
      <small style="display:block;color:var(--ink-3)">PDF, JPG or PNG · up to 10 MB each</small>
    </label>
    <x-upload-progress />
    @error('newLegacyRecords')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror
    @error('newLegacyRecords.*')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror
  </div>
  <x-slot:footer>
    <div class="spacer"></div>
    <button class="btn" type="button" wire:click="$set('showLegacyUploadModal', false)">Cancel</button>
    <button class="btn primary" type="button" wire:click="uploadLegacyRecords" wire:loading.attr="disabled" wire:target="newLegacyRecords,uploadLegacyRecords">Upload</button>
  </x-slot:footer>
</x-modal>
@endif
