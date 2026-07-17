{{-- Adapted from data-wipe.html via the mockup — same flow, but the state is live Livewire:
     pick a mode → Preview Count → the confirm modal demands the exact count typed back. --}}
<div>
  <p class="crumb">Administration · Maintenance</p>
  <x-maintenance-tabs />

  <div class="htop">
    <div><h2>Danger Zone</h2>
      <p>Destructive housekeeping. Every action here needs a preview, a typed confirmation, and lands in the Audit Trail.</p></div>
  </div>

  <div class="dz-banner">
    <div class="dz-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg></div>
    <div>
      <h3>Destructive Actions Ahead</h3>
      <p>Actions on this page permanently delete data from the system. This cannot be undone. Filed PANs are business records — wipe only what retention policy allows.</p>
    </div>
  </div>

  @foreach (self::GROUPS as $g => $cfg)
  <div class="dz-card" wire:key="dz-{{ $g }}">
    <div class="dz-head">
      <h3>{{ $cfg['title'] }}</h3>
      <p>{{ $cfg['desc'] }}</p>
    </div>
    <div class="dz-body">
      <div>
        <span class="dz-label">{{ $cfg['label'] }}</span>
        <div class="rgrid" @if ($g === 'attach') style="max-width:460px" @endif>
          @foreach ($cfg['modes'] as $mode => [$mTitle, $mSmall])
          <label class="rcard @if ($modes[$g] === $mode) sel @endif" wire:key="dz-{{ $g }}-{{ $mode }}" wire:click="selectMode('{{ $g }}', '{{ $mode }}')">
            <input type="radio" name="{{ $g }}Mode" @checked($modes[$g] === $mode)><span><b>{{ $mTitle }}</b><small>{{ $mSmall }}</small></span></label>
          @endforeach
        </div>
      </div>

      @if ($modes[$g] === 'range')
      <div class="formgrid" style="padding:0;grid-template-columns:1fr 1fr;max-width:440px">
        <div class="field"><label>From Date</label><input type="date"></div>
        <div class="field"><label>To Date</label><input type="date"></div>
      </div>
      @elseif ($modes[$g] === 'year')
      <div class="field" style="max-width:200px"><label>Select Year</label>
        <select><option>2022</option><option>2023</option><option>2024</option><option>2025</option><option>2026</option></select></div>
      @elseif ($modes[$g] === 'quarter')
      <div class="formgrid" style="padding:0;grid-template-columns:1fr 1fr;max-width:440px">
        <div class="field"><label>Select Year</label>
          <select wire:model.live="attachYear"><option value="">— Select Year —</option><option>2022</option><option>2023</option><option>2024</option><option>2025</option><option>2026</option></select></div>
        <div class="field"><label>Select Quarter</label>
          <select @disabled($attachYear === '')><option value="">— Select Quarter —</option><option>Q1 — Jan–Mar</option><option>Q2 — Apr–Jun</option><option>Q3 — Jul–Sep</option><option>Q4 — Oct–Dec</option></select></div>
      </div>
      @endif

      <div class="dz-prevrow">
        <button class="btn" type="button" wire:click="preview('{{ $g }}')"><svg class="dz-btnicon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg> Preview Count</button>
        @if ($counts[$g] !== null)
        <span class="count-badge {{ $cfg['color'] }}"><svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><span>{{ number_format($counts[$g]) }}{{ $cfg['badge'] }}</span></span>
        @endif
      </div>
      <div class="dz-foot">
        <button class="dz-action {{ $cfg['color'] }}" type="button" wire:click="openConfirm('{{ $g }}')">
          @if ($cfg['icon'] === 'trash')
          <svg class="dz-btnicon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
          @else
          <svg class="dz-btnicon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          @endif
          {{ $cfg['action'] }}</button>
      </div>
    </div>
  </div>
  @endforeach

  {{-- Type-the-exact-count confirm modal — Livewire-driven so the required text follows the previewed count --}}
  <div class="overlay @if ($confirm) on @endif" wire:click.self="closeConfirm" wire:keydown.escape.window="closeConfirm">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="dm-title">
      <div style="padding:24px 24px 8px">
        <div class="dz-micon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg></div>
        <h3 id="dm-title" style="margin:0 0 8px;text-align:center;font-size:16px">{{ $confirm['title'] ?? 'Confirm' }}</h3>
        <p style="margin:0 0 18px;text-align:center;font-size:13px;color:var(--ink-2)">{!! $confirm['msg'] ?? '' !!}</p>
        <div class="field" style="margin-bottom:16px"><label>Type <b style="color:var(--red)">{{ $confirm['required'] ?? '—' }}</b> to confirm</label>
          <input wire:model.live="confirmInput" autocomplete="off" placeholder="Enter it exactly to confirm"></div>
      </div>
      <div class="dz-mfoot" style="padding-bottom:20px">
        <button class="btn" type="button" wire:click="closeConfirm">Cancel</button>
        <button class="dz-action red" type="button" wire:click="queueConfirmed" @disabled($confirmInput !== ($confirm['required'] ?? null))>{{ $confirm['button'] ?? 'Confirm' }}</button>
      </div>
    </div>
  </div>
</div>
