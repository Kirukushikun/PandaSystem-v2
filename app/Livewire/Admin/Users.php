<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithPerPage;
use App\Models\User;
use App\Services\UserDirectoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * User Accounts. Two modes, switched by AUTH_FAKE:
 *  - dev (AUTH_FAKE=true): the local users table only — the seeded role accounts.
 *  - live (AUTH_FAKE=false + USER_API_ENDPOINT): the full org directory from the
 *    external User API, merged with local rows. Granting access creates the local
 *    row with the API's real id (users.id must match it — the login flow resolves
 *    people by that id). Revoking soft-deletes; re-granting restores.
 */
#[Layout('layouts.app')]
#[Title('User Accounts — PANDA')]
class Users extends Component
{
    use WithPagination;
    use WithPerPage;

    public string $search = '';

    public string $filter = 'all'; // all | heads | hr | admins | noaccess (directory mode only)

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    /** Human summary of the five stage booleans, mockup-style ("Requestor · Division Head"). */
    public static function stageSummary(User $user): string
    {
        $stages = array_keys(array_filter([
            'Requestor' => $user->is_requestor,
            'Division Head' => $user->is_division_head,
            'HR Preparer' => $user->is_hr_preparer,
            'HR Approver' => $user->is_hr_approver,
            'Final Approver' => $user->is_final_approver,
        ]));

        return $stages === [] ? '—' : implode(' · ', $stages);
    }

    public function refreshDirectory(): void
    {
        app(UserDirectoryService::class)->refresh();
        $this->js("showToast('Directory refreshed from the company system.')");
    }

    /** Create the local row for an org user — id comes from the directory, never the client. */
    public function grant(int $id): void
    {
        $directory = app(UserDirectoryService::class);
        abort_unless($directory->enabled(), 404);

        $api = collect($directory->all())->firstWhere('id', $id);
        if ($api === null) {
            $this->js("showToast('That user is no longer in the directory — refresh and try again.')");

            return;
        }

        $user = User::withTrashed()->find($id);

        if ($user !== null) {
            if ($user->trashed()) {
                $user->restore();
            }
        } else {
            $user = new User;
            $user->fill([
                'name' => $api['name'],
                'email' => $api['email'],
                'username' => $this->uniqueUsername($api['email'], $id),
                'external_id' => (string) $id,
            ]);
            $user->id = $id; // must match the external system — the login flow finds people by it
            $user->save();
        }

        // Straight to User Access: a fresh grant has no permissions yet.
        $this->redirectRoute('admin.users.access', $user->username, navigate: true);
    }

    public function revoke(int $id): void
    {
        abort_unless(app(UserDirectoryService::class)->enabled(), 404);

        if ($id === auth()->id()) {
            $this->js("showToast('You cannot revoke your own access.')");

            return;
        }

        $user = User::findOrFail($id);
        $user->delete(); // soft — re-granting restores the same row, permissions intact

        $this->js("showToast('{$user->name}\'s PANDA access has been revoked.')");
    }

    private function uniqueUsername(string $email, int $id): string
    {
        $base = Str::of($email)->before('@')->lower()->toString() ?: 'user';

        return User::withTrashed()->where('username', $base)->exists() ? "{$base}{$id}" : $base;
    }

    /** @return array<int, array{user: ?User, api: ?array}> */
    private function rows(UserDirectoryService $directory): array
    {
        if (! $directory->enabled()) {
            return $this->localQuery()->get()
                ->map(fn (User $u) => ['user' => $u, 'api' => null])
                ->all();
        }

        $locals = User::with('farm')->get()->keyBy('id');
        $apiUsers = collect($directory->all());
        $apiIds = $apiUsers->pluck('id');

        return $apiUsers
            ->map(fn (array $api) => ['user' => $locals->get($api['id']), 'api' => $api])
            // locals missing from the directory (e.g. seeded dev accounts) stay visible
            ->concat($locals->reject(fn (User $u) => $apiIds->contains($u->id))
                ->map(fn (User $u) => ['user' => $u, 'api' => null]))
            ->filter(fn (array $row) => $this->matchesSearch($row) && $this->matchesFilter($row))
            ->sortBy([fn ($a, $b) => ($a['user'] === null) <=> ($b['user'] === null),
                fn ($a, $b) => strcasecmp($a['user']?->name ?? $a['api']['name'], $b['user']?->name ?? $b['api']['name'])])
            ->values()
            ->all();
    }

    private function matchesSearch(array $row): bool
    {
        if ($this->search === '') {
            return true;
        }

        $haystack = $row['user'] !== null
            ? "{$row['user']->name} {$row['user']->username} {$row['user']->email} {$row['user']->position}"
            : "{$row['api']['name']} {$row['api']['email']}";

        return Str::contains(Str::lower($haystack), Str::lower($this->search));
    }

    private function matchesFilter(array $row): bool
    {
        $u = $row['user'];

        return match ($this->filter) {
            'heads' => $u !== null && ($u->is_division_head || $u->is_dh_head),
            'hr' => $u !== null && ($u->is_hr_preparer || $u->is_hr_approver || $u->is_hr_head),
            'admins' => $u !== null && $u->is_admin,
            'noaccess' => $u === null,
            default => true,
        };
    }

    private function localQuery(): Builder
    {
        return User::query()
            ->with('farm')
            ->when($this->search !== '', function (Builder $q) {
                $q->where(fn (Builder $q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('position', 'like', "%{$this->search}%"));
            })
            ->when($this->filter === 'heads', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('is_division_head', true)->orWhere('is_dh_head', true)))
            ->when($this->filter === 'hr', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('is_hr_preparer', true)->orWhere('is_hr_approver', true)->orWhere('is_hr_head', true)))
            ->when($this->filter === 'admins', fn (Builder $q) => $q->where('is_admin', true))
            ->orderBy('name');
    }

    public function render(UserDirectoryService $directory)
    {
        // Four stats in one aggregate pass over users (booleans are 0/1 in the DB).
        $stats = User::query()->selectRaw('
            count(*) as total,
            sum(case when is_division_head = 1 or is_dh_head = 1 then 1 else 0 end) as heads,
            sum(is_hr_preparer) as preparers,
            sum(is_admin) as admins
        ')->toBase()->first();

        // rows() is a merged in-memory list (API + local), so paginate it manually.
        $all = $this->rows($directory);
        $page = Paginator::resolveCurrentPage();
        $rows = new LengthAwarePaginator(
            array_slice($all, ($page - 1) * $this->perPage, $this->perPage),
            count($all), $this->perPage, $page,
        );

        return view('livewire.admin.users', [
            'rows' => $rows,
            'directoryMode' => $directory->enabled(),
            'stats' => [
                'total' => (int) $stats->total,
                'heads' => (int) $stats->heads,
                'preparers' => (int) $stats->preparers,
                'admins' => (int) $stats->admins,
            ],
        ]);
    }
}
