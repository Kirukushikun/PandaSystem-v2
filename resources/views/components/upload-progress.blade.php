{{-- Hidden until app.js's Livewire upload-event listeners toggle it. Only one
     upload field is ever visible on screen at a time in this app, so a shared
     class (not a per-instance id) is enough — see app.js for the wiring. --}}
<div class="upload-progress" hidden style="margin-top:8px">
  <div class="upload-progress-track" style="height:6px;border-radius:99px;background:var(--panel-2);overflow:hidden">
    <div class="upload-progress-bar" style="height:100%;width:0%;background:var(--accent);transition:width .15s linear"></div>
  </div>
  <small style="color:var(--ink-3)">Uploading… <span class="upload-progress-pct">0</span>%</small>
</div>
