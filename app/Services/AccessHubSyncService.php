<?php

namespace App\Services;

use App\Enums\UserSource;
use App\Livewire\Admin\UserAccess;
use App\Models\AccessHubConnection;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Fetch -> compare -> preview -> apply, per hub-integration-guide.md §4.5/§4.6.
 * Never writes directly from a sync — compare() only ever reports what would
 * change; apply() only ever touches rows explicitly confirmed by the caller.
 */
class AccessHubSyncService
{
    public function __construct(private AccessHubService $hub) {}

    /** Passthrough so a caller can hold one AccessHubSyncService and still read why compare() returned null. */
    public function lastFailureReason(): ?string
    {
        return $this->hub->lastFailureReason();
    }

    /**
     * Fetches, compares, and — on a successful fetch — stamps last_synced_at
     * (guide §4.7: "on a successful fetch, not on apply").
     *
     * @return array{empty: bool, new: array, changed: array, local_only: array}|null
     *              null only when the hub fetch itself failed — see AccessHubService::lastFailureReason().
     */
    public function compare(): ?array
    {
        $people = $this->hub->fetchGrants();
        if ($people === null) {
            return null;
        }

        AccessHubConnection::current()->update(['last_synced_at' => now()]);

        if ($people === []) {
            return ['empty' => true, 'new' => [], 'changed' => [], 'local_only' => []];
        }

        return $this->compareAgainst($people);
    }

    /**
     * @param  array<int, array>  $people  raw hub /grants "people" array
     * @return array{empty: bool, new: array, changed: array, local_only: array}
     */
    public function compareAgainst(array $people): array
    {
        $byId = collect($people)->keyBy('user_id');
        $locals = User::withTrashed()->get()->keyBy('id');

        $new = [];
        $changed = [];

        foreach ($byId as $id => $person) {
            $local = $locals->get($id);

            if ($local === null) {
                if ($person['active'] ?? false) {
                    $new[] = ['hub' => $person, 'mapped' => $this->mapRoles($person['roles'] ?? [])];
                }

                continue;
            }

            // Rows this sync doesn't own are never touched, no matter what the hub says —
            // resolved into the local_only group below, not here (see class docblock on
            // AccessHub's manual-row handling and hub-integration-guide.md §4.6).
            if ($local->source !== UserSource::Hub) {
                continue;
            }

            $active = $person['active'] ?? false;

            if (! $active) {
                if (! $local->trashed()) {
                    $changed[] = ['type' => 'revoke', 'user' => $local, 'hub' => $person, 'mapped' => null];
                }

                continue;
            }

            $mapped = $this->mapRoles($person['roles'] ?? []);
            $currentlyDiffers = $this->differsFromCurrent($local, $mapped);

            if ($local->trashed() || $currentlyDiffers) {
                $changed[] = ['type' => 'update', 'user' => $local, 'hub' => $person, 'mapped' => $mapped];
            }
        }

        $localOnly = $locals->reject(fn (User $u) => $byId->has($u->id))
            ->map(fn (User $u) => ['user' => $u, 'hub' => null, 'reason' => null])
            ->values()
            ->all();

        // The manual-row edge case: known to the hub, but this row isn't sync's to
        // manage — functionally identical to true local-only ("never touched"), so it
        // lives in the same group, just annotated with why. See class docblock.
        $manualOverrides = $locals
            ->filter(fn (User $u) => $u->source !== UserSource::Hub && $byId->has($u->id))
            ->map(fn (User $u) => ['user' => $u, 'hub' => $byId->get($u->id), 'reason' => 'manual_override'])
            ->values()
            ->all();

        return [
            'empty' => false,
            'new' => $new,
            'changed' => $changed,
            'local_only' => [...$localOnly, ...$manualOverrides],
        ];
    }

    /** @return array<string,bool> keyed by UserAccess::PERM_COLUMNS keys, union across all $roles */
    public function mapRoles(array $roles): array
    {
        $mapping = config('access_hub_roles');
        $result = array_fill_keys(array_keys(UserAccess::PERM_COLUMNS), false);

        foreach ($roles as $role) {
            if (! array_key_exists($role, $mapping)) {
                Log::warning('Access Hub: unrecognized role, skipped', ['role' => $role]);

                continue;
            }

            foreach ($mapping[$role] as $permKey) {
                $result[$permKey] = true;
            }
        }

        return $result;
    }

    /**
     * Applies only the confirmed rows — new/changed ids the admin ticked in the
     * preview. Every permission write goes through UserPermissionWriter, the
     * same path UserAccess::save() uses (guide §4.6).
     *
     * @param  array{new: array, changed: array}  $preview  the group arrays from compare()
     * @param  array<int>  $confirmedNewIds  hub user_ids to create
     * @param  array<int>  $confirmedChangedIds  local user ids to update/revoke
     */
    public function apply(array $preview, array $confirmedNewIds, array $confirmedChangedIds): void
    {
        foreach ($preview['new'] as $row) {
            $id = (int) $row['hub']['user_id'];
            if (! in_array($id, $confirmedNewIds, true)) {
                continue;
            }

            $this->createFromHub($row['hub'], $row['mapped']);
        }

        foreach ($preview['changed'] as $row) {
            if (! in_array($row['user']->id, $confirmedChangedIds, true)) {
                continue;
            }

            if ($row['type'] === 'revoke') {
                $row['user']->delete(); // soft — same as Users::revoke()
                continue;
            }

            if ($row['user']->trashed()) {
                $row['user']->restore();
            }
            UserPermissionWriter::write($row['user'], $row['mapped']);
        }
    }

    private function createFromHub(array $person, array $mapped): void
    {
        $user = new User;
        $user->fill([
            'external_id' => (string) $person['user_id'],
            'name' => $person['name'],
            'email' => $person['email'],
            'username' => $this->uniqueUsername($person['email'], $person['user_id']),
            'position' => $person['position'] ?? null, // display-only, per guide §3
            'source' => UserSource::Hub,
        ]);
        $user->id = $person['user_id']; // must match the hub, never auto-increment — guide §4.6
        $user->save();

        UserPermissionWriter::write($user, $mapped);
    }

    private function uniqueUsername(string $email, int $id): string
    {
        $base = Str::of($email)->before('@')->lower()->toString() ?: 'user';

        return User::withTrashed()->where('username', $base)->exists() ? "{$base}{$id}" : $base;
    }

    /** @param array<string,bool> $mapped */
    private function differsFromCurrent(User $user, array $mapped): bool
    {
        foreach (UserAccess::PERM_COLUMNS as $key => $column) {
            if ((bool) $user->{$column} !== ($mapped[$key] ?? false)) {
                return true;
            }
        }

        return false;
    }
}
