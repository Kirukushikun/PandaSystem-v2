<div>
  <p class="crumb">Administration</p>
  {{-- Mockup's "User Access" subtab is a separate per-user route here: /admin/users/{user} --}}
  <div class="htop">
    <div><h2>User Accounts</h2>
      <p>Everyone with access to PANDA. Login identity comes from the external company system; what's managed here is what each person can do. Open a user to edit their stage permissions, departments, flags, and profile.</p></div>
  </div>

  <div class="stats">
    <x-stat value="36" label="User accounts" />
    <x-stat value="11" label="Division Heads" tone="acc" />
    <x-stat value="4" label="HR Preparers" />
    <x-stat value="2" label="Admins" tone="warn" />
  </div>

  <div class="bar">
    <x-search-bar placeholder="Find a user account…" />
    <x-chip on>All</x-chip><x-chip>Division Heads</x-chip><x-chip>HR</x-chip><x-chip>Admins</x-chip>
  </div>

  <div class="card"><div class="twrap"><table>
    <thead><tr><th>User</th><th>Farm / Site</th><th>Position</th><th>Stage Permissions</th><th>Flags</th><th></th></tr></thead>
    <tbody>
      @foreach ([
        ['name' => 'K. Reyes',     'user' => 'kreyes',    'site' => 'San Rafael Farm', 'pos' => 'Farm Supervisor II',        'stages' => 'Requestor · Division Head',               'flag' => null],
        ['name' => 'M. Dela Cruz', 'user' => 'mdelacruz', 'site' => 'Main Office',     'pos' => 'Sr. HR Officer',            'stages' => 'Requestor · Division Head · HR Preparer', 'flag' => ['p-final', 'HR Head']],
        ['name' => 'C. Aguirre',   'user' => 'caguirre',  'site' => 'Main Office',     'pos' => 'AVP — Corporate Services',  'stages' => 'Division Head',                            'flag' => ['p-final', 'DH Head']],
        ['name' => 'T. Navarro',   'user' => 'tnavarro',  'site' => 'Main Office',     'pos' => 'HR Officer',                'stages' => 'HR Preparer',                              'flag' => null],
        ['name' => 'R. Ocampo',    'user' => 'rocampo',   'site' => 'Main Office',     'pos' => 'HR Manager',                'stages' => 'HR Approver',                              'flag' => null],
        ['name' => 'V. Salazar',   'user' => 'vsalazar',  'site' => 'Main Office',     'pos' => 'VP — Operations',           'stages' => 'Final Approver',                           'flag' => null],
        ['name' => 'IT Admin',     'user' => 'admin_it',  'site' => 'Main Office',     'pos' => 'Systems Administrator',     'stages' => '—',                                        'flag' => ['p-ret', 'Admin']],
      ] as $row)
      <tr>
        <td><div class="who"><b>{{ $row['name'] }}</b><small>{{ $row['user'] }}</small></div></td>
        <td>{{ $row['site'] }}</td><td>{{ $row['pos'] }}</td>
        <td>{{ $row['stages'] }}</td>
        <td>@if ($row['flag'])<span class="pill {{ $row['flag'][0] }}">{{ $row['flag'][1] }}</span>@else — @endif</td>
        <td class="acts"><a class="btn ghost" href="{{ route('admin.users.access', $row['user']) }}" wire:navigate style="text-decoration:none">View</a></td>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>
</div>
