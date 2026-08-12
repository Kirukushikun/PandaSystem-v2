<div>
  <p class="crumb">Administration · Maintenance</p>
  <x-maintenance-tabs />

  <div class="htop">
    <div><h2>Proxy Approver</h2>
      <p>Controls the temporary override that lets Proxy Approvers act on stalled Division Head approvals (Tarlac/Untagged only — Manila is never affected). Switch this off once the backlog clears; nothing already recorded is lost.</p></div>
  </div>

  <div class="card" style="max-width:520px">
    <div class="pad">
      <div class="formgrid" style="grid-template-columns:1fr">
        <div class="field">
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
            <input type="checkbox" wire:model="enabled" style="width:16px;height:16px">
            <span>Proxy Approver feature enabled</span>
          </label>
          <span class="hint">When off, the Proxy Approver queue is hidden and every proxy action is blocked, regardless of who holds the role.</span>
        </div>
        <div class="field">
          <label>Staleness threshold (days) <em>*</em></label>
          <input type="number" min="1" max="90" wire:model="thresholdDays" style="max-width:140px">
          <span class="hint">A PAN becomes eligible for proxy approval once it has sat at a Division Head stage this many days without a decision.</span>
          @error('thresholdDays')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror
        </div>
      </div>
      <div class="formfoot">
        <div class="spacer"></div>
        <button class="btn primary" type="button" wire:click="save">Save settings</button>
      </div>
    </div>
  </div>
</div>
