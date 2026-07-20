<div>
  <p class="crumb">Administration</p>
  {{-- Mockup's "User Access" subtab is a separate per-user route here: /admin/users/{user} --}}
  <div class="htop">
    <div><h2>User Accounts</h2>
      @if ($directoryMode)
      <p>The full company directory from the external system. Grant access to bring someone into PANDA — their permissions are set on the User Access screen. Login identity always comes from the company system.</p>
      @else
      <p>Everyone with access to PANDA. Login identity comes from the external company system; what's managed here is what each person can do. Open a user to edit their stage permissions, departments, flags, and profile.</p>
      @endif
    </div>
    <div class="spacer"></div>
    @if ($directoryMode)
    <button class="btn ghost" type="button" wire:click="refreshDirectory">⟳ Refresh directory</button>
    @endif
  </div>

  <div class="stats">
    <x-stat :value="$stats['total']" label="User accounts" />
    <x-stat :value="$stats['heads']" label="Division Heads" tone="acc" />
    <x-stat :value="$stats['preparers']" label="HR Preparers" />
    <x-stat :value="$stats['admins']" label="Admins" tone="warn" />
  </div>

  <div class="bar">
    <div class="search">⌕<input placeholder="Find a user account…" wire:model.live.debounce.300ms="search"></div>
    <button class="chip @if ($filter === 'all') on @endif" type="button" wire:click="$set('filter', 'all')">All</button>
    <button class="chip @if ($filter === 'heads') on @endif" type="button" wire:click="$set('filter', 'heads')">Division Heads</button>
    <button class="chip @if ($filter === 'hr') on @endif" type="button" wire:click="$set('filter', 'hr')">HR</button>
    <button class="chip @if ($filter === 'admins') on @endif" type="button" wire:click="$set('filter', 'admins')">Admins</button>
    @if ($directoryMode)
    <button class="chip @if ($filter === 'noaccess') on @endif" type="button" wire:click="$set('filter', 'noaccess')">No access</button>
    @endif
  </div>

  @if ($rows->isEmpty())
  <x-empty-state title="No accounts found" message="{{ $directoryMode ? 'Nothing matches — or the company directory is unreachable. Try Refresh directory.' : 'Nothing matches — clear the search or pick another filter.' }}" />
  @else
  <div class="card"><div class="twrap"><table>
    <thead><tr><th>User</th><th>Farm / Site</th><th>Position</th><th>Stage Permissions</th><th>Flags</th><th></th></tr></thead>
    <tbody>
      @foreach ($rows as $row)
      @php($user = $row['user'])
      @if ($user !== null)
      <tr wire:key="user-{{ $user->id }}">
        <td><div class="who"><b>{{ $user->name }}</b><small>{{ $directoryMode ? $user->email : $user->username }}@if ($directoryMode && $row['api'] === null) · not in directory @endif</small></div></td>
        <td>{{ $user->farm?->name ?? '—' }}</td><td>{{ $user->position ?? '—' }}</td>
        <td>{{ App\Livewire\Admin\Users::stageSummary($user) }}</td>
        <td>
          @if ($user->is_hr_head)<span class="pill p-final">HR Head</span>
          @elseif ($user->is_dh_head)<span class="pill p-final">DH Head</span>
          @elseif ($user->is_admin)<span class="pill p-ret">Admin</span>
          @else — @endif
        </td>
        <td class="acts">
          <a class="btn ghost" href="{{ route('admin.users.access', $user->username) }}" wire:navigate style="text-decoration:none">View</a>
          @if ($directoryMode && $user->id !== auth()->id())
          <x-kebab><x-kebab.item danger wire:click="revoke({{ $user->id }})" wire:confirm="Revoke {{ $user->name }}'s PANDA access? Their permissions are kept and restored if you re-grant.">Revoke access</x-kebab.item></x-kebab>
          @endif
        </td>
      </tr>
      @else
      <tr wire:key="api-{{ $row['api']['id'] }}">
        <td><div class="who"><b>{{ $row['api']['name'] }}</b><small>{{ $row['api']['email'] }}</small></div></td>
        <td>—</td><td>—</td>
        <td><span class="pill p-draft">No PANDA access</span></td>
        <td>—</td>
        <td class="acts"><button class="btn primary" type="button" wire:click="grant({{ $row['api']['id'] }})">Grant access</button></td>
      </tr>
      @endif
      @endforeach
    </tbody>
  </table></div></div>
  {{ $rows->links('components.pagination') }}
  @endif
</div>
