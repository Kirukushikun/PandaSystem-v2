{{-- Dev-only, id 61 only — see project-overview/legacy-peek-tool-plan.md. Not linked from any nav. --}}
<div>
  <p class="crumb">Dev · v1 Peek</p>
  <div class="htop">
    <div><h2>{{ $employee->name }}</h2>
    <p>{{ $employee->employee_no }} — v2's record next to whatever v1 (PandaSystem) has on file for the same employee.</p></div>
    <div class="spacer"></div>
    <button class="btn" type="button" wire:click="refreshPeek" wire:loading.attr="disabled" wire:target="refreshPeek">
      <span wire:loading.remove wire:target="refreshPeek">Refresh v1</span>
      <span wire:loading wire:target="refreshPeek">Checking…</span>
    </button>
    <a class="btn" href="{{ route('dev.legacy-peek') }}" wire:navigate style="text-decoration:none">← All employees</a>
  </div>

  @if ($checked && ! $legacyPeek)
  <div class="note warn"><span class="ic">!</span><span>Not found in v1, or v1 is unreachable right now.</span></div>
  @endif

  @if ($legacyPeek)
  <div class="card" style="margin-bottom:14px">
    <div class="sect">Prep simulation — if HR Prep started a new PAN right now</div>
    <p class="hint" style="padding:0 16px 10px">Runs the real carry-over logic (<code>CarryOverService</code>) against this employee and diffs each field against v1's latest "To" value — the same check a fresh prep would silently rely on.</p>
    <div class="twrap"><table class="fromto" style="width:100%;min-width:560px;table-layout:fixed">
      <thead><tr><th style="width:120px">Item</th><th>v2 would carry "From"</th><th class="arrow" style="width:30px"></th><th>v1's actual "To"</th><th style="width:90px">Match?</th></tr></thead>
      <tbody>
        @foreach ($simulation as $row)
        <tr wire:key="sim-{{ $row['field'] }}">
          <td class="lbl">{{ $row['field'] }}</td>
          <td>{{ $row['v2Simulated'] }}</td>
          <td class="arrow">→</td>
          <td>{{ $row['v1Actual'] ?? '— no v1 data' }}</td>
          <td>
            @if ($row['match'] === null)
            <span style="color:var(--ink-3);font-size:12px">—</span>
            @elseif ($row['match'])
            <span style="color:var(--accent);font-size:12px">✓ match</span>
            @else
            <span style="color:var(--red);font-size:12px">✗ differs</span>
            @endif
          </td>
        </tr>
        @endforeach
        <tr wire:key="sim-employment_status">
          <td class="lbl">Employment status</td>
          <td>{{ $simulatedEmploymentStatus }}</td>
          <td class="arrow">→</td>
          <td>{{ $legacyPeek['latest_pan']['employment_status'] ?? '— no v1 data' }}</td>
          <td>
            @if (! ($legacyPeek['latest_pan']['employment_status'] ?? null))
            <span style="color:var(--ink-3);font-size:12px">—</span>
            @elseif ($simulatedEmploymentStatus === $legacyPeek['latest_pan']['employment_status'])
            <span style="color:var(--accent);font-size:12px">✓ match</span>
            @else
            <span style="color:var(--red);font-size:12px">✗ differs</span>
            @endif
          </td>
        </tr>
      </tbody>
    </table></div>
  </div>
  @endif

  <div class="twocol">
    <div class="pane" style="display:flex;flex-direction:column">
      <h3>v2</h3>
      <div class="formgrid" style="padding-bottom:28px;min-height:300px;align-content:start">
        <div class="field"><label>Department / Position / Farm</label>
          <input readonly value="{{ $employee->department->name }} · {{ $employee->position }} · {{ $employee->farm->name }}"></div>
        @if ($v2Latest)
        <div class="field"><label>Latest v2 PAN</label>
          <input readonly value="{{ $v2Latest->reference }} — {{ $v2Latest->status->label() }} ({{ $v2Latest->action_type->label() }})"></div>
        <div class="field"><label>Employment status (v2)</label>
          <input readonly value="{{ $v2Latest->form?->employment_status?->label() ?? '—' }}"></div>
        @endif
      </div>

      @if ($v2Latest?->form?->action_reference)
      <div class="sect">v2's action reference</div>
      <div class="twrap"><table class="fromto" style="width:100%;min-width:380px;table-layout:fixed">
        <thead><tr><th style="width:120px">Item</th><th>From</th><th class="arrow" style="width:30px"></th><th>To</th></tr></thead>
        <tbody>
          @foreach ($v2Latest->form->action_reference as $row)
          <tr wire:key="v2-{{ $row['field'] }}">
            <td class="lbl">{{ $row['field'] }}</td>
            <td>{{ $row['from'] }}</td><td class="arrow">→</td><td>{{ $row['to'] }}</td>
          </tr>
          @endforeach
        </tbody>
      </table></div>
      @else
      <p class="hint" style="padding:0 16px 14px">No prepared v2 PAN yet — nothing to compare.</p>
      @endif

      @if ($v2Pans->count() > 1)
      <div class="sect">Other v2 PANs on record</div>
      <div style="padding:0 16px 14px;display:flex;flex-direction:column;gap:4px">
        @foreach ($v2Pans->skip(1) as $pan)
        <div style="font-size:13px;color:var(--ink-2)">{{ $pan->reference }} — {{ $pan->status->label() }}</div>
        @endforeach
      </div>
      @endif
    </div>

    <div class="pane" style="display:flex;flex-direction:column">
      <h3>v1</h3>
      @if ($legacyPeek)
      <div class="formgrid" style="padding-bottom:28px;min-height:300px;align-content:start">
        <div class="field"><label>Department / Position / Farm (current)</label>
          <input readonly value="{{ $legacyPeek['employee']['department'] }} · {{ $legacyPeek['employee']['position'] }} · {{ $legacyPeek['employee']['farm'] }}"></div>
        @if ($legacyPeek['latest_pan'])
        <div class="field"><label>Latest v1 PAN</label>
          <input readonly value="{{ $legacyPeek['latest_pan']['pan_number'] }} — {{ $legacyPeek['latest_pan']['status'] }} ({{ $legacyPeek['latest_pan']['type_of_action'] }})"></div>
        <div class="field"><label>Dept snapshot on that PAN</label>
          <input readonly value="{{ $legacyPeek['latest_pan']['department_snapshot'] }}"></div>
        <div class="field"><label>Employment status (v1)</label>
          <input readonly value="{{ $legacyPeek['latest_pan']['employment_status'] ?? '—' }}"></div>
        @if ($legacyPeek['latest_pan']['justification'])
        <div class="field full"><label>Justification (v1)</label>
          <input readonly value="{{ $legacyPeek['latest_pan']['justification'] }}"></div>
        @endif
        @endif
      </div>

      @if ($legacyPeek['latest_pan']['action_reference_data'] ?? null)
      <div class="sect">v1's action reference</div>
      <div class="twrap"><table class="fromto" style="width:100%;min-width:380px;table-layout:fixed">
        <thead><tr><th style="width:120px">Item</th><th>From</th><th class="arrow" style="width:30px"></th><th>To</th></tr></thead>
        <tbody>
          @foreach ($legacyPeek['latest_pan']['action_reference_data'] as $row)
          <tr wire:key="v1-{{ $row['field'] }}">
            <td class="lbl">{{ $row['field'] }}</td>
            <td>{{ $row['from'] }}</td><td class="arrow">→</td><td>{{ $row['to'] }}</td>
          </tr>
          @endforeach
        </tbody>
      </table></div>
      @elseif ($legacyPeek['latest_pan'])
      <p class="hint" style="padding:0 16px 14px">This PAN hasn't reached HR Prep in v1 yet — no action reference to compare.</p>
      @endif

      @if (count($legacyPeek['recent_pans'] ?? []) > 1)
      <div class="sect">Other v1 PANs on record</div>
      <div style="padding:0 16px 14px;display:flex;flex-direction:column;gap:4px">
        @foreach (array_slice($legacyPeek['recent_pans'], 1) as $pan)
        <div style="font-size:13px;color:var(--ink-2)">{{ $pan['pan_number'] }} — {{ $pan['status'] }}</div>
        @endforeach
      </div>
      @endif

      <div class="formfoot" style="margin-top:auto">
        <span class="hint" style="margin:0">Checked {{ \Illuminate\Support\Carbon::parse($legacyPeek['checked_at'])->diffForHumans() }}.</span>
      </div>
      @else
      <p class="hint" style="padding:14px 16px;margin-top:auto">{{ $checked ? 'Nothing to show.' : 'Checking…' }}</p>
      @endif
    </div>
  </div>
</div>
