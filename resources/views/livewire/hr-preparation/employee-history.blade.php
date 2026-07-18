<div>
  <p class="crumb">HR Preparation · signed in as {{ $isHrHead ? 'HR Head Preparer' : 'HR Preparer' }}</p>
  <div class="htop">
    <div><h2>PAN History — {{ $employee->name }} <span class="ref" style="font-size:14px">{{ $employee->employee_no }}</span></h2>
    <p>Every PAN on record for this employee, newest first. Each PAN's "From" values chain from the previous one's "To" values.</p></div>
    <div class="spacer"></div>
    @can('createHr', App\Models\PanRequest::class)
    <button class="btn primary" type="button" wire:click="startUpdate({{ $employee->id }})" data-modal-open="update-modal">Update PAN</button>
    @endcan
  </div>

  <div class="stats">
    <x-stat :value="$stats['total']" label="Total PANs" />
    <x-stat :value="$stats['filed']" label="Filed" tone="ok" />
    <x-stat :value="$stats['rework']" label="In rework" tone="bad" />
    <x-stat :value="$stats['basic']" label="Current basic pay" tone="acc" />
  </div>

  @if ($pans->isEmpty())
  <x-empty-state title="No PANs yet" message="This employee has no PAN on record — start one with Update PAN." />
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
          <a class="btn ghost" href="{{ route('preparation.show', $pan->reference) }}" wire:navigate style="text-decoration:none">View</a>
          @if ($pan->form)
          <x-print-btn href="{{ route('pan.print', $pan->reference) }}" />
          @endif
        </x-row-actions>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>
  @endif

  <div style="display:flex;margin-top:14px">
    <a class="btn" href="{{ route('employees.index') }}" wire:navigate style="text-decoration:none">← Back to employees</a>
  </div>

  @include('livewire.hr-preparation.partials.update-pan-modal')
</div>
