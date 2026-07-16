<div>
  <p class="crumb">Final Approver</p>
  <div class="htop">
    <div><h2>Final Sign-off</h2>
      <p>Act on requests individually or in bulk — select rows, or target all requests of one action type at once. Rejection returns a PAN to HR Preparation with a mandatory reason.</p></div>
  </div>

  <div class="stats">
    <x-stat value="6" label="Awaiting final approval" tone="warn" />
    <x-stat value="41" label="Approved this quarter" tone="ok" />
    <x-stat value="3" label="Rejected this quarter" tone="bad" />
  </div>

  <div class="bulk">
    {{ count($selected) }} selected
    <div class="spacer"></div>
    <select wire:change="selectType($event.target.value)" style="border:1px solid var(--line);border-radius:7px;padding:6px 10px;font:inherit;background:var(--panel);color:var(--ink)">
      <option value="">Select all of type…</option>
      <option value="Regularization">Regularization (3)</option>
      <option value="Wage Order">Wage Order (2)</option>
      <option value="Promotion">Promotion (1)</option>
    </select>
    <button class="btn primary" type="button" wire:click="approveSelected">Approve selected</button>
    <button class="btn danger" type="button" data-modal-open="reject-modal">Reject selected…</button>
  </div>

  <div class="card"><div class="twrap"><table>
    <thead><tr><th style="width:36px"><input type="checkbox" wire:click="toggleAll" @checked(count($selected) === count($rows)) aria-label="Select all"></th>
      <th>Reference</th><th>Employee</th><th>Type of Action</th><th>Effectivity</th><th>Basic Pay (From → To)</th><th></th></tr></thead>
    <tbody>
      @foreach ($rows as $row)
      <tr wire:key="{{ $row['ref'] }}">
        <td><input type="checkbox" wire:model.live="selected" value="{{ $row['ref'] }}" aria-label="Select"></td>
        <td class="ref">{{ $row['ref'] }}</td>
        <td><div class="who"><b>{{ $row['name'] }}</b><small>{{ $row['dept'] }}</small></div></td>
        <td>{{ $row['type'] }}</td><td>{{ $row['eff'] }}</td><td class="num">{{ $row['pay'] }}</td>
        <x-row-actions>
          <a class="btn ghost" href="{{ route('final-approval.show', $row['ref']) }}" wire:navigate style="text-decoration:none">View</a>
          <button class="btn primary" type="button" onclick="showToast('Final approval given (UI scaffold — nothing is persisted yet).')">Approve</button>
          <x-kebab><x-kebab.item danger data-modal-open="reject-modal">Reject — back to HR Prep…</x-kebab.item></x-kebab>
        </x-row-actions>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>

  <div class="note info" style="margin-top:14px"><span class="ic">i</span>Approving a <b>&nbsp;Regularization&nbsp;</b> automatically finalizes the employee's status as "Regular", overriding any tentative status set earlier.</div>

  <x-modal id="reject-modal" title="Reject — back to HR Preparation">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr">
      <div class="field"><label>Reason <em>*</em></label>
        <select><option>Values need revision</option><option>Wrong effectivity date</option><option>Incorrect approver routing</option><option>Needs supporting document</option><option>Custom reason…</option></select></div>
      <div class="field"><label>Details (optional)</label>
        <textarea rows="3"></textarea></div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" data-close>Cancel</button>
      <div class="spacer"></div>
      <button class="btn danger" type="button" data-close onclick="showToast('Rejected — returned to HR Preparation with reason (UI scaffold — nothing is persisted yet).')">Reject to HR Preparation</button>
    </x-slot:footer>
  </x-modal>
</div>
