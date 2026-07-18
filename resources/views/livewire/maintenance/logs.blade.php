<div>
  <p class="crumb">Administration · Maintenance</p>
  <x-maintenance-tabs />

  <div class="htop">
    <div><h2>Logs &amp; Audit</h2>
      <p>Read-only records: sign-in attempts, and the workflow's own trail — every return with its actor and reason, and every filed cycle.</p></div>
    <div class="spacer"></div>
    <button class="btn" type="button" wire:click="exportCsv">Export CSV</button>
  </div>

  <div class="twocol">
    <div class="pane">
      <h3>Access Log — sign-in attempts</h3>
      <div>
        @forelse ($accessLogs as $log)
        <div class="logrow" wire:key="al-{{ $log->id }}"><time>{{ $log->created_at->format('M j · H:i') }}</time><span style="flex:1"><b>{{ $log->email }}</b> · {{ $log->ip_address }}</span>
          <span class="pill {{ $log->success ? 'p-appr' : 'p-ret' }}">{{ $log->success ? 'Success' : 'Failed' }}</span></div>
        @empty
        <div class="logrow"><span style="color:var(--ink-3)">No sign-in attempts recorded yet.</span></div>
        @endforelse
      </div>
    </div>
    <div class="pane">
      <h3>Audit Trail — workflow actions</h3>
      <div>
        @forelse ($audit as $i => $event)
        <div class="logrow" wire:key="au-{{ $i }}"><time>{{ $event['at']->format('M j · H:i') }}</time><span class="mod">{{ $event['module'] }}</span><span>{{ $event['text'] }}</span></div>
        @empty
        <div class="logrow"><span style="color:var(--ink-3)">Workflow activity (returns, disputes, voids, filings) will appear here.</span></div>
        @endforelse
      </div>
    </div>
  </div>
</div>
