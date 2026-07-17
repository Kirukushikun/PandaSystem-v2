{{-- Master employee roster: the single source the HR Preparation "Employees" lens reads from.
     Admin owns the records; HR Preparation only works with PANs raised against them. --}}
<div>
  <p class="crumb">Administration</p>
  <div class="htop">
    <div><h2>Employee Directory</h2>
      <p>The master roster every PAN is raised against — and the source of the HR Preparation "Employees" tab. Add, edit, or remove records here.</p></div>
    <div class="spacer"></div>
    <button class="btn" type="button" onclick="showToast('Spreadsheet import arrives with the real build (maatwebsite/excel).')">Import from spreadsheet…</button>
    <button class="btn" type="button" onclick="showToast('Export arrives with the real build.')">Export</button>
    <button class="btn primary" type="button" data-modal-open="emp-modal">+ Add Employee</button>
  </div>

  <div class="stats">
    <x-stat value="412" label="Employees on roster" />
    <x-stat value="9" label="Departments" tone="acc" />
    <x-stat value="3" label="Farms / sites" />
    <x-stat value="6" label="Added this month" tone="warn" />
  </div>

  <div class="bar">
    <x-search-bar placeholder="Search by name, employee ID, department, or position…" />
    <x-chip on>All</x-chip><x-chip>San Rafael Farm</x-chip><x-chip>Sta. Maria Feedmill</x-chip><x-chip>Main Office</x-chip>
  </div>

  <div class="card"><div class="twrap"><table>
    {{-- "Ongoing PAN" = a PAN anywhere in the workflow, submitted and not yet filed/withdrawn/voided
         (drafts don't count). Remove stays visible but blocked with the reason — enforced in
         policy + query in the real build, never just here. --}}
    <thead><tr><th>Employee</th><th>Employee ID</th><th>Department</th><th>Position</th><th>Farm / Site</th><th>Ongoing PAN</th><th></th></tr></thead>
    <tbody>
      @foreach ([
        ['name' => 'S. Lim',        'id' => 'EMP-10233', 'dept' => 'Feedmill',           'pos' => 'Mill Operator',     'site' => 'Sta. Maria Feedmill', 'pan' => ['p-ret',   'Yes — in rework'],      'blocked' => true],
        ['name' => 'N. Fernandez',  'id' => 'EMP-10490', 'dept' => 'Corporate Office',   'pos' => 'HR Generalist',     'site' => 'Main Office',         'pan' => ['p-prep',  'Yes — HR Preparation'], 'blocked' => true],
        ['name' => 'D. Torres',     'id' => 'EMP-10064', 'dept' => 'Broiler Operations', 'pos' => 'Poultry Caretaker', 'site' => 'San Rafael Farm',     'pan' => ['p-appr',  'Yes — for serving'],    'blocked' => true],
        ['name' => 'J. Ramos',      'id' => 'EMP-10387', 'dept' => 'Hatchery',           'pos' => 'Hatchery Aide',     'site' => 'San Rafael Farm',     'pan' => ['p-dh',    'Yes — with Requestor'], 'blocked' => true],
        ['name' => 'L. Bautista',   'id' => 'EMP-10077', 'dept' => 'Broiler Operations', 'pos' => 'Farm Technician I', 'site' => 'San Rafael Farm',     'pan' => ['p-draft', 'None'],                 'blocked' => false],
        ['name' => 'R. Villanueva', 'id' => 'EMP-10422', 'dept' => 'Broiler Operations', 'pos' => 'Farm Helper',       'site' => 'San Rafael Farm',     'pan' => ['p-draft', 'None — draft only'],    'blocked' => false],
      ] as $row)
      <tr>
        <td><div class="who"><b>{{ $row['name'] }}</b></div></td><td class="ref">{{ $row['id'] }}</td>
        <td>{{ $row['dept'] }}</td><td>{{ $row['pos'] }}</td><td>{{ $row['site'] }}</td>
        <td><span class="pill {{ $row['pan'][0] }}">{{ $row['pan'][1] }}</span></td>
        <x-row-actions>
          <button class="btn ghost" type="button" data-modal-open="emp-modal">Edit</button>
          <x-kebab>
            @if ($row['blocked'])
            <x-kebab.item disabled title="Cannot remove — this employee has an ongoing PAN">Remove employee<small>Blocked — ongoing PAN</small></x-kebab.item>
            @else
            <x-kebab.item danger onclick="showToast('Employee removed — PAN history is kept (UI scaffold — nothing is persisted yet).')">Remove employee</x-kebab.item>
            @endif
          </x-kebab>
        </x-row-actions>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>

  <div class="note info" style="margin-top:14px"><span class="ic">i</span><span><b>Remove is disabled while an employee has an ongoing PAN</b>&nbsp;— anything submitted and not yet filed, withdrawn, or voided blocks removal (drafts don't count). Removing an employee never deletes their PAN history; they simply can no longer be selected for new PANs.</span></div>

  {{-- Mockup shares one modal for Add and Edit (JS swaps the title); same here in the scaffold --}}
  <x-modal id="emp-modal" title="Add / Edit Employee">
    <div class="formgrid" style="padding:16px;grid-template-columns:1fr 1fr">
      <div class="field full"><label>Full Name <em>*</em></label><input placeholder="Surname, First Name M.I."></div>
      <div class="field"><label>Employee ID <em>*</em></label><input placeholder="EMP-00000"></div>
      <div class="field"><label>Department <em>*</em></label>
        <select><option>Broiler Operations</option><option>Hatchery</option><option>Feedmill</option><option>Sales &amp; Distribution</option><option>Corporate Office</option><option>Accounting</option></select></div>
      <div class="field"><label>Position <em>*</em></label><input placeholder="e.g. Farm Technician I"></div>
      <div class="field"><label>Farm / Site <em>*</em></label>
        <select><option>San Rafael Farm</option><option>Sta. Maria Feedmill</option><option>Main Office</option></select></div>
    </div>
    <x-slot:footer>
      <button class="btn" type="button" data-close>Cancel</button>
      <div class="spacer"></div>
      <button class="btn primary" type="button" data-close onclick="showToast('Employee saved (UI scaffold — nothing is persisted yet).')">Save Employee</button>
    </x-slot:footer>
  </x-modal>
</div>
