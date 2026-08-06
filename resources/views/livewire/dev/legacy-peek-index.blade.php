{{-- Dev-only, id 61 only — see project-overview/legacy-peek-tool-plan.md. Not linked from any nav. --}}
<div>
  <p class="crumb">Dev · v1 Peek</p>
  <div class="htop">
    <div><h2>v1 Peek</h2>
    <p>v2's roster, checked live against PandaSystem (v1) — who exists there, and what their latest PAN looks like. Read-only, on demand.</p></div>
  </div>

  @unless ($v1Enabled)
  <div class="note warn"><span class="ic">!</span><span>LEGACY_V1_BASE_URI isn't set — this tool is inert until it's configured in <code>.env</code>.</span></div>
  @endunless

  <div class="bar">
    <div class="search">⌕<input placeholder="Search employees by name, ID, or department…" wire:model.live.debounce.300ms="search"></div>
    <button class="chip @if ($departmentFilter === null) on @endif" type="button" wire:click="$set('departmentFilter', null)">All departments</button>
    @foreach ($departments as $department)
    <button class="chip @if ($departmentFilter === $department->id) on @endif" type="button" wire:click="$set('departmentFilter', {{ $department->id }})">{{ $department->name }}</button>
    @endforeach
    <div class="spacer"></div>
    <button class="btn" type="button" wire:click="checkPage" wire:loading.attr="disabled" wire:target="checkPage" @disabled(! $v1Enabled)>
      <span wire:loading.remove wire:target="checkPage">Check this page against v1</span>
      <span wire:loading wire:target="checkPage">Checking…</span>
    </button>
  </div>

  @if ($employees->isEmpty())
  <x-empty-state title="No employees found" message="Nothing matches — clear the search or pick another department." />
  @else
  <div class="card"><div class="twrap"><table>
    <thead><tr><th>Employee</th><th>Department</th><th>v2 PANs</th><th>v1 status</th><th></th></tr></thead>
    <tbody>
      @foreach ($employees as $employee)
      @php $status = $v1Status[$employee->employee_no] ?? 'unchecked'; @endphp
      <tr wire:key="emp-{{ $employee->id }}">
        <td><div class="who"><b>{{ $employee->name }}</b><small>{{ $employee->employee_no }}</small></div></td>
        <td>{{ $employee->department->name }}</td>
        <td class="num">{{ $employee->pan_requests_count }}</td>
        <td>
          @if ($status === 'unchecked')
          <span style="color:var(--ink-3);font-size:12px">— not checked yet</span>
          @elseif ($status === null)
          <span style="color:var(--ink-3);font-size:12px">not found in v1</span>
          @else
          <span style="color:var(--accent);font-size:12px">{{ $status['latest_pan']['status'] ?? 'found' }} in v1</span>
          @endif
        </td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('dev.legacy-peek.show', $employee->employee_no) }}" wire:navigate style="text-decoration:none">Compare v1 · v2</a>
        </x-row-actions>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>
  {{ $employees->links('components.pagination') }}
  @endif
</div>
