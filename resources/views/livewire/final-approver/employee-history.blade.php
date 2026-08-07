{{-- Read-only clone of hr-preparation/employee-history.blade.php — no "Update PAN"
     action, no Manila filtering (Final Approver has no confidentiality distinction),
     plus the new Legacy Records panel (view/download only, $canManage=false). --}}
<div>
  <p class="crumb">Final Approver</p>
  <div class="htop">
    <div><h2>PAN History — {{ $employee->name }} <span class="ref" style="font-size:14px">{{ $employee->employee_no }}</span></h2>
    <p>Every PAN on record for this employee, newest first.</p></div>
    <div class="spacer"></div>
  </div>

  <div class="stats">
    <x-stat :value="$stats['total']" label="Total PANs" />
    <x-stat :value="$stats['filed']" label="Filed" tone="ok" />
    <x-stat :value="$stats['rework']" label="In rework" tone="bad" />
    <x-stat :value="$stats['basic']" label="Current basic pay" tone="acc" />
  </div>

  @if ($pans->isEmpty())
  <x-empty-state title="No PANs yet" message="This employee has no PAN on record." />
  @else
  <div class="card"><div class="twrap"><table>
    <thead><tr><th>Reference</th><th>Type of Action</th><th>Effectivity</th><th>Key Change</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @foreach ($pans as $pan)
      @php
        $changed = collect($pan->form?->displayRows() ?? [])->firstWhere('chg', true);
      @endphp
      <tr wire:key="pan-{{ $pan->id }}">
        <td class="ref">{{ $pan->reference }}</td>
        <td>{{ $pan->action_type->label() }}</td>
        <td>{{ $pan->form?->doe_from?->format('M j, Y') ?? '—' }}</td>
        <td @if ($changed && $changed['num']) class="num" @endif>{{ $changed ? $changed['from'].' → '.$changed['to'] : '—' }}</td>
        <td><x-status-pill :status="$pan->status->value" /></td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('final-approval.show', $pan->reference) }}" wire:navigate style="text-decoration:none">View</a>
          @if ($pan->form)
          <x-print-btn href="{{ route('pan.print', $pan->reference) }}" />
          @endif
        </x-row-actions>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>
  @endif

  <x-employee.legacy-records :employee="$employee" :legacy-records="$legacyRecords" :can-manage="false" />

  <div style="display:flex;margin-top:14px">
    <a class="btn" href="{{ route('final-approval.employees.index') }}" wire:navigate style="text-decoration:none">← Back to employees</a>
  </div>
</div>
