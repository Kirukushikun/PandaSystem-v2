<div>
  <p class="crumb">HR Preparation · signed in as {{ $isHrHead ? 'HR Head Preparer' : 'HR Preparer' }}</p>
  <div class="htop">
    <div><h2>Preparation Queue</h2>
      <p>Division-approved PANs awaiting paperwork. Each must be tagged for confidentiality before preparation can begin.</p></div>
    <div class="spacer"></div>
    <a class="btn" href="{{ route('employees.index') }}" wire:navigate style="text-decoration:none">Employees</a>
  </div>

  <div class="stats">
    <x-stat :value="$stats['prepare']" label="Awaiting preparation" tone="warn" />
    <x-stat :value="$stats['returned']" label="Returned for resolution" tone="bad" />
    <x-stat :value="$stats['serve']" label="Approved — to serve / file" tone="acc" />
  </div>

  <div class="bar">
    <div class="search">⌕<input placeholder="Search PANs awaiting HR paperwork…" wire:model.live.debounce.300ms="search"></div>
    <button class="chip @if ($filter === 'all') on @endif" type="button" wire:click="$set('filter', 'all')">All</button>
    <button class="chip @if ($filter === 'prepare') on @endif" type="button" wire:click="$set('filter', 'prepare')">To prepare</button>
    <button class="chip @if ($filter === 'approval') on @endif" type="button" wire:click="$set('filter', 'approval')">In approval</button>
    <button class="chip @if ($filter === 'serve') on @endif" type="button" wire:click="$set('filter', 'serve')">To serve / file</button>
    <x-filters-menu :open="$showFilters" :active="$this->hasActiveFilters() || $tagFilter !== 'all'" clear="clearPanFilters">
      {{-- Confidentiality filter — Manila only shows up for HR Head Preparers; a normal
           preparer never sees Manila rows anyway (scope() already strips them), so the
           chip would just be dead weight for that role. --}}
      <div class="filters-section-label">Tags</div>
      <div class="bar">
        <button class="chip @if ($tagFilter === 'all') on @endif" type="button" wire:click="$set('tagFilter', 'all')">All tags</button>
        <button class="chip @if ($tagFilter === 'untagged') on @endif" type="button" wire:click="$set('tagFilter', 'untagged')"><x-tag-dot /> Untagged</button>
        <button class="chip @if ($tagFilter === 'tarlac') on @endif" type="button" wire:click="$set('tagFilter', 'tarlac')"><x-tag-dot tag="tarlac" /> Tarlac</button>
        @if ($isHrHead)
        <button class="chip @if ($tagFilter === 'manila') on @endif" type="button" wire:click="$set('tagFilter', 'manila')"><x-tag-dot tag="manila" /> Manila</button>
        @endif
      </div>
      <hr class="filters-divider">
      <x-pan-filters />
    </x-filters-menu>
  </div>

  @if ($pans->isEmpty())
  <x-empty-state title="Nothing to prepare" message="No PANs match — clear the search and filters, or wait for the next division approval." />
  @else
  <div class="card"><div class="twrap"><table>
    <thead><tr><th>Tag</th><th>Reference</th><th>Employee</th><th>Type of Action</th><th>Department</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @foreach ($pans as $pan)
      <tr wire:key="pan-{{ $pan->id }}">
        <td><x-tag-dot :tag="$pan->confidentiality_tag->value" /></td> {{-- 'untagged' falls to the gray default --}}
        <td class="ref">{{ $pan->reference }}</td>
        <td><div class="who"><b>{{ $pan->employee->name }}</b><small>{{ $pan->employee->employee_no }}</small></div></td>
        <td>{{ $pan->action_type->label() }}</td>
        <td>{{ $pan->employee->department->name }}</td>
        <td>
          @php
            // InPreparation is the landing spot for TWO different bounce-backs (DH dispute,
            // Final Approver rejection) as well as plain fresh preparation — without this,
            // all three look identical and the preparer has to open the PAN to find out why
            // it's back on their desk.
            $bounced = $pan->status === App\Enums\PanStatus::InPreparation
                && $pan->latestReturn
                && $pan->latestReturn->to_status === App\Enums\PanStatus::InPreparation
                ? $pan->latestReturn->action
                : null;
          @endphp
          @if ($pan->status === App\Enums\PanStatus::AwaitingTag)
            <x-status-pill status="in-preparation">Tag &amp; prepare</x-status-pill>
          @elseif ($bounced === 'dispute')
            <x-status-pill tone="ret">Disputed — by Division Head</x-status-pill>
          @elseif ($bounced === 'reject_final')
            <x-status-pill tone="ret">Rejected — by Final Approver</x-status-pill>
          @elseif ($pan->status === App\Enums\PanStatus::InPreparation && $pan->confidentiality_tag === App\Enums\ConfidentialityTag::Manila)
            <x-status-pill status="in-preparation">Preparing — confidential</x-status-pill>
          @else
            <x-status-pill :status="$pan->status->value" />
          @endif
        </td>
        {{-- No print icon until a PAN is prepared (no disabled buttons — the pill explains the state). --}}
        <x-row-actions>
          <a class="btn ghost" href="{{ route('preparation.show', $pan->reference) }}" wire:navigate style="text-decoration:none">View</a>
          @if ($pan->status === App\Enums\PanStatus::AwaitingTag)
            <a class="btn primary" href="{{ route('preparation.edit', $pan->reference) }}" wire:navigate style="text-decoration:none">Open</a>
          @elseif ($pan->status === App\Enums\PanStatus::InPreparation)
            <a class="btn primary" href="{{ route('preparation.edit', $pan->reference) }}" wire:navigate style="text-decoration:none">Continue</a>
          @elseif ($pan->status === App\Enums\PanStatus::ReturnedToPreparer)
            <a class="btn primary" href="{{ route('preparation.edit', $pan->reference) }}" wire:navigate style="text-decoration:none">Revise</a>
          @elseif ($pan->status === App\Enums\PanStatus::Approved)
            @can('markServed', $pan)
            <button class="btn primary" type="button" wire:click="markServed({{ $pan->id }})" wire:confirm="Mark {{ $pan->reference }} as Served?">Mark Served</button>
            @endcan
          @elseif ($pan->status === App\Enums\PanStatus::Served)
            @can('filePan', $pan)
            <button class="btn primary" type="button" wire:click="filePan({{ $pan->id }})" wire:confirm="File {{ $pan->reference }}? This closes the cycle.">File</button>
            @endcan
          @endif
          @if ($pan->form)
            <x-print-btn href="{{ route('pan.print', $pan->reference) }}" />
          @endif
          @can('void', $pan)
            <x-kebab><x-kebab.item danger wire:click="startReason({{ $pan->id }}, 'void')">Void / Delete…</x-kebab.item></x-kebab>
          @endcan
          @can('markUnserved', $pan)
            <x-kebab><x-kebab.item wire:click="startReason({{ $pan->id }}, 'mark_unserved')">Mark Unserved…<small>AWOL, resigned, terminated, or custom</small></x-kebab.item></x-kebab>
          @endcan
          @if ($pan->status === App\Enums\PanStatus::Filed)
            <x-kebab><x-kebab.item wire:navigate href="{{ route('employees.history', $pan->employee->employee_no) }}">Start Follow-up PAN<small>New cycle for this employee</small></x-kebab.item></x-kebab>
          @endif
        </x-row-actions>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>
  {{ $pans->links('components.pagination') }}
  @endif
  @if ($isHrHead)
  <p class="locknote" style="margin:8px 2px 0">Tag colors — <x-tag-dot tag="manila" /> Manila (confidential) · <x-tag-dot tag="tarlac" /> Tarlac (routine) · <x-tag-dot /> untagged. Visible to HR Head Preparers only. Once tagged Manila, ordinary preparers can no longer open the record.</p>
  @endif

  <x-modal id="reason-modal" :open="$showModal" close="$set('showModal', false)" title="{{ $modalAction === 'void' ? 'Void / Delete' : 'Mark Unserved' }}{{ $modalPan ? ' — '.$modalPan->reference : '' }}">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="field"><label>Reason <em>*</em></label>
        <select wire:model="reason">
          <option value="">Select a reason…</option>
          @if ($modalAction === 'void')
          <option>Duplicate request</option>
          <option>Raised in error</option>
          <option>Overtaken by events</option>
          @else
          <option>AWOL</option>
          <option>Resigned before serving</option>
          <option>Terminated</option>
          @endif
          <option>Custom reason…</option>
        </select>
        @error('reason')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
      <div class="field"><label>Details @if ($reason === 'Custom reason…')<em>*</em>@else (optional)@endif</label>
        <textarea rows="3" wire:model="details" maxlength="1000"></textarea>
        @error('details')<span class="hint" style="color:var(--red)">{{ $message }}</span>@enderror</div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" wire:click="$set('showModal', false)">Cancel</button>
      <div class="spacer"></div>
      <button class="btn danger" type="button" wire:click="submitReason">
        {{ $modalAction === 'void' ? 'Void this PAN' : 'Mark Unserved' }}
      </button>
    </x-slot:footer>
  </x-modal>
</div>
