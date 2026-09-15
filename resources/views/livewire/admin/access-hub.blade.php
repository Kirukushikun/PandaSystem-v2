{{-- Reachable only via "Sync from Hub" on User Accounts — no sidebar nav entry of its own. --}}
<div>
  <p class="crumb">Administration</p>
  <div class="htop">
    <div><h2>Sync from Access Hub</h2>
      <p>Review what would change before anything is written.</p></div>
    <div class="spacer"></div>
    <a class="btn" href="{{ route('admin.users') }}" wire:navigate style="text-decoration:none">← Back to User Accounts</a>
  </div>

  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:0 0 14px">
    <p class="hint" style="margin:0">Last synced:
      @if ($lastSyncedAt)
        <span @if ($lastSyncedAt->diffInDays() > 30) style="color:var(--amber);font-weight:600" @endif>{{ $lastSyncedAt->diffForHumans() }}</span>
      @else
        never
      @endif
    </p>
    <div class="spacer"></div>
    @if ($enrolled)
    <button class="btn" type="button" wire:click="runSync" wire:loading.attr="disabled" wire:target="runSync">
      <span wire:loading.remove wire:target="runSync">Sync now</span>
      <span wire:loading wire:target="runSync">Syncing…</span>
    </button>
    <button class="btn danger" type="button" wire:click="resetConnection" wire:confirm="Reset the Access Hub connection? You'll need a new connection code to sync again.">Reset connection</button>
    @else
    <button class="btn primary" type="button" wire:click="openEnroll">Connect to Access Hub</button>
    @endif
  </div>

  @if (! $enrolled && $preview === null && $fetchError === null)
  <div class="note info"><span class="ic">i</span><span>Not connected yet — the roster stays managed by hand until you connect.</span></div>
  @endif

  @if ($fetchError === 'unreachable')
  <div class="note warn"><span class="ic">!</span><span>Could not reach the Access Hub. Nothing was changed — try again shortly.</span></div>
  @elseif ($fetchError === 'unauthorized')
  <div class="note warn"><span class="ic">!</span><span>The hub rejected this connection's credentials. <button type="button" class="btn ghost" style="padding:2px 8px;font-size:12px" wire:click="resetConnection">Reset connection</button> and enroll again.</span></div>
  @elseif ($fetchError === 'rate_limited')
  <div class="note warn"><span class="ic">!</span><span>The hub is rate-limiting sync requests — wait a moment and try again.</span></div>
  @elseif ($fetchError === 'not_enrolled' && $enrolled)
  <div class="note warn"><span class="ic">!</span><span>Sync is not configured for this environment.</span></div>
  @endif

  @if ($preview !== null && $preview['empty'])
  <div class="note info"><span class="ic">i</span><span>Nothing to sync — the hub returned zero people.</span></div>
  @endif

  @if ($preview !== null && ! $preview['empty'])
  <div class="pane" style="margin-bottom:14px">
    <h3>New <small style="font-weight:400;color:var(--ink-3)">— the hub knows them, this system doesn't yet</small></h3>
    <div class="pad">
      @forelse ($preview['new'] as $row)
      <div class="logrow" wire:key="new-{{ $row['hub']['user_id'] }}">
        <input type="checkbox" wire:model="selectedNew" value="{{ $row['hub']['user_id'] }}">
        <span style="flex:1">{{ $row['hub']['name'] }} <small style="color:var(--ink-3)">{{ $row['hub']['farm'] ?? '—' }} · {{ $row['hub']['department'] ?? '—' }} · {{ $row['hub']['position'] ?? '—' }}</small></span>
        <span style="font-size:11px;color:var(--ink-3)">{{ implode(', ', $row['hub']['roles'] ?? []) }}</span>
      </div>
      @empty
      <div class="logrow"><span style="color:var(--ink-3)">None.</span></div>
      @endforelse
    </div>
  </div>

  <div class="pane" style="margin-bottom:14px">
    <h3>Changed <small style="font-weight:400;color:var(--ink-3)">— access here would differ, or they went inactive</small></h3>
    <div class="pad">
      @forelse ($preview['changed'] as $row)
      <div class="logrow" wire:key="chg-{{ $row['user']->id }}">
        <input type="checkbox" wire:model="selectedChanged" value="{{ $row['user']->id }}">
        <span style="flex:1">{{ $row['user']->name }} <small style="color:var(--ink-3)">{{ $row['hub']['farm'] ?? '—' }} · {{ $row['hub']['department'] ?? '—' }} · {{ $row['hub']['position'] ?? '—' }}</small></span>
        <span style="font-size:11px;color:{{ $row['type'] === 'revoke' ? 'var(--red)' : 'var(--amber)' }}">
          {{ $row['type'] === 'revoke' ? 'Inactive at hub — will revoke' : 'Will update: '.implode(', ', $row['hub']['roles'] ?? []) }}
        </span>
      </div>
      @empty
      <div class="logrow"><span style="color:var(--ink-3)">None.</span></div>
      @endforelse
    </div>
  </div>

  <div class="pane" style="margin-bottom:14px">
    <h3>Local only <small style="font-weight:400;color:var(--ink-3)">— never touched, shown for reassurance</small></h3>
    <div class="pad">
      @forelse ($preview['local_only'] as $row)
      <div class="logrow" wire:key="local-{{ $row['user']->id }}">
        <span style="flex:1">{{ $row['user']->name }}</span>
        <span style="font-size:11px;color:var(--ink-3)">
          @if ($row['reason'] === 'manual_override')
            Also present at hub as {{ implode(', ', $row['hub']['roles'] ?? []) }}, but this account is manual — never touched by sync
          @else
            Not known to the hub
          @endif
        </span>
      </div>
      @empty
      <div class="logrow"><span style="color:var(--ink-3)">None.</span></div>
      @endforelse
    </div>
  </div>

  <div style="display:flex;justify-content:flex-end">
    <button class="btn primary" type="button" wire:click="apply" @if (empty($selectedNew) && empty($selectedChanged)) disabled @endif>Apply selected changes</button>
  </div>
  @endif

  <x-modal id="hub-enroll-modal" :open="$showEnrollModal" close="$set('showEnrollModal', false)" title="Connect to Access Hub">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="field"><label>Connection code</label>
        <input wire:model="enrollCode" autocomplete="off" placeholder="Paste the code from the Access Hub">
        @error('enrollCode')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror
        @if ($enrollError)<span class="hint" style="color:var(--red)">{{ $enrollError }}</span>@endif
      </div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" wire:click="$set('showEnrollModal', false)">Cancel</button>
      <button class="btn primary" type="button" wire:click="enroll">Connect</button>
    </x-slot:footer>
  </x-modal>
</div>
