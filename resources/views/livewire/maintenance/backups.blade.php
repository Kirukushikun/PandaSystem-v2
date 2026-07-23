{{-- Restore confirm modal is fully Livewire-driven (type RESTORE to enable the button). --}}
<div>
  <p class="crumb">Administration · Maintenance</p>
  <x-maintenance-tabs />

  <div class="htop">
    <div><h2>Backup &amp; Restore</h2>
      <p>Scheduled backups run automatically every night at 01:00; from here you can also trigger one manually or restore from an uploaded backup.</p></div>
  </div>

  <div class="stats">
    <x-stat :value="$stats['health']" label="Backup health check" :tone="$stats['healthTone']" />
    <x-stat value="01:00" label="Nightly schedule" />
    <x-stat :value="$stats['retained']" label="Backups retained" />
    <x-stat :value="$stats['size']" label="Latest backup size" tone="acc" />
  </div>

  <div class="twocol">
    <div class="pane">
      <h3>Recent backups</h3>
      <div>
        @forelse ($backups as $backup)
        <div class="logrow" wire:key="b-{{ $backup['file'] }}"><time>{{ date('M j · H:i', $backup['at']) }}</time>
          <span style="flex:1">{{ basename($backup['file']) }} · {{ round($backup['size'] / 1048576, 1) }} MB</span>
          <a class="btn ghost" style="padding:2px 8px;font-size:12px;text-decoration:none" href="{{ route('maintenance.backups.download', basename($backup['file'])) }}">Download</a></div>
        @empty
        <div class="logrow"><span style="color:var(--ink-3)">No backups yet — run one now, or wait for the nightly schedule.</span></div>
        @endforelse
      </div>
      <div style="display:flex;gap:8px;padding:14px 16px;border-top:1px solid var(--line-soft)">
        <button class="btn primary" type="button" wire:click="runBackup" wire:loading.attr="disabled" wire:target="runBackup">
          <span wire:loading.remove wire:target="runBackup">Run Backup Now</span>
          <span wire:loading wire:target="runBackup">Backing up…</span>
        </button>
      </div>
    </div>
    <div class="pane">
      <h3>Import / Restore</h3>
      <div class="pad" style="display:flex;flex-direction:column;gap:12px;padding-top:14px">
        <label class="upload" style="cursor:pointer">
          <b>{{ $restoreFile ? $restoreFile->getClientOriginalName() : 'Choose a backup file' }}</b>
          {{ $restoreFile ? '— ready to restore' : 'or drag it here — .sql produced by this system' }}
          <input type="file" accept=".sql" wire:model="restoreFile" style="display:none">
        </label>
        @error('restoreFile')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror
        <div class="note warn" style="margin:0"><span class="ic">!</span>Restoring overwrites current data with the backup's contents. A safety backup is taken automatically first.</div>
        <div style="display:flex;justify-content:flex-end">
          <button class="btn danger" type="button" wire:click="openRestore">Restore from Backup…</button>
        </div>
      </div>
    </div>
  </div>

  <div class="overlay @if ($showRestore) on @endif" wire:click.self="closeRestore" wire:keydown.escape.window="closeRestore">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="restore-title">
      <div style="padding:24px 24px 8px">
        <div class="dz-micon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg></div>
        <h3 id="restore-title" style="margin:0 0 8px;text-align:center;font-size:16px">Confirm Data Restore</h3>
        <p style="margin:0 0 18px;text-align:center;font-size:13px;color:var(--ink-2)">This will <b>overwrite current data</b> with the uploaded backup's contents. A safety backup is taken first. This cannot be undone.</p>
        <div class="field" style="margin-bottom:16px"><label>Type <b style="color:var(--red)">RESTORE</b> to confirm</label>
          <input wire:model.live="confirmInput" autocomplete="off" placeholder="Enter it exactly to confirm"></div>
      </div>
      <div class="dz-mfoot" style="padding-bottom:20px">
        <button class="btn" type="button" wire:click="closeRestore">Cancel</button>
        <button class="dz-action red" type="button" wire:click="runRestore" @disabled($confirmInput !== 'RESTORE')>Restore Now</button>
      </div>
    </div>
  </div>
</div>
