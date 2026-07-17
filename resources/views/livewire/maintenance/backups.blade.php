{{-- Restore confirm modal is fully Livewire-driven (type RESTORE to enable the button). --}}
<div>
  <p class="crumb">Administration · Maintenance</p>
  <x-maintenance-tabs />

  <div class="htop">
    <div><h2>Backup &amp; Restore</h2>
      <p>Scheduled backups run automatically every night; from here you can also trigger one manually or restore from an uploaded backup.</p></div>
  </div>

  <div class="stats">
    <x-stat value="Healthy" label="Backup health check" tone="ok" />
    <x-stat value="01:00" label="Nightly schedule" />
    <x-stat value="14" label="Backups retained" />
    <x-stat value="318 MB" label="Latest backup size" tone="acc" />
  </div>

  <div class="twocol">
    <div class="pane">
      <h3>Recent backups</h3>
      <div>
        <div class="logrow"><time>Jul 15 · 01:00</time><span style="flex:1">pandav2_2026-07-15.sql.gz · 318 MB</span><span class="pill p-appr">OK</span></div>
        <div class="logrow"><time>Jul 14 · 01:00</time><span style="flex:1">pandav2_2026-07-14.sql.gz · 317 MB</span><span class="pill p-appr">OK</span></div>
        <div class="logrow"><time>Jul 13 · 01:00</time><span style="flex:1">pandav2_2026-07-13.sql.gz · 317 MB</span><span class="pill p-appr">OK</span></div>
        <div class="logrow"><time>Jul 12 · 01:00</time><span style="flex:1">pandav2_2026-07-12.sql.gz · 315 MB</span><span class="pill p-appr">OK</span></div>
      </div>
      <div style="display:flex;gap:8px;padding:14px 16px;border-top:1px solid var(--line-soft)">
        <button class="btn primary" type="button" wire:click="runBackup">Run Backup Now</button>
        <button class="btn" type="button" onclick="showToast('Download arrives with the real build.')">Download latest</button>
      </div>
    </div>
    <div class="pane">
      <h3>Import / Restore</h3>
      <div class="pad" style="display:flex;flex-direction:column;gap:12px;padding-top:14px">
        <div class="upload"><b>Choose a backup file</b> or drag it here — .sql.gz produced by this system</div>
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
        <button class="dz-action red" type="button" wire:click="queueRestore" @disabled($confirmInput !== 'RESTORE')>Queue Restore</button>
      </div>
    </div>
  </div>
</div>
