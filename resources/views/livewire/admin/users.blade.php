<div>
  <p class="crumb">Administration</p>
  {{-- Mockup's "User Access" subtab is a separate per-user route here: /admin/users/{user} --}}
  <div class="htop">
    <div><h2>User Accounts</h2>
      <p>Everyone with access to PANDA. Login identity comes from the external company system; what's managed here is what each person can do. Open a user to edit their stage permissions, departments, flags, and profile.</p></div>
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
  </div>

  @if ($users->isEmpty())
  <x-empty-state title="No accounts found" message="Nothing matches — clear the search or pick another filter." />
  @else
  <div class="card"><div class="twrap"><table>
    <thead><tr><th>User</th><th>Farm / Site</th><th>Position</th><th>Stage Permissions</th><th>Flags</th><th></th></tr></thead>
    <tbody>
      @foreach ($users as $user)
      <tr wire:key="user-{{ $user->id }}">
        <td><div class="who"><b>{{ $user->name }}</b><small>{{ $user->username }}</small></div></td>
        <td>{{ $user->farm?->name ?? '—' }}</td><td>{{ $user->position }}</td>
        <td>{{ App\Livewire\Admin\Users::stageSummary($user) }}</td>
        <td>
          @if ($user->is_hr_head)<span class="pill p-final">HR Head</span>
          @elseif ($user->is_dh_head)<span class="pill p-final">DH Head</span>
          @elseif ($user->is_admin)<span class="pill p-ret">Admin</span>
          @else — @endif
        </td>
        <td class="acts"><a class="btn ghost" href="{{ route('admin.users.access', $user->username) }}" wire:navigate style="text-decoration:none">View</a></td>
      </tr>
      @endforeach
    </tbody>
  </table></div></div>
  @endif
</div>
