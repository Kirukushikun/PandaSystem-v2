<?php

namespace App\Services;

use App\Enums\UserSource;
use App\Livewire\Admin\UserAccess;
use App\Models\AccessHubConnection;
use App\Models\Department;
use App\Models\Farm;
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
            $currentlyDiffers = $this->differsFromCurrent($local, $mapped, $person);

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
        // Checkbox values arrive as strings from a real wire:model.live-bound
        // checkbox array — cast up front so the strict in_array() checks below
        // compare like-for-like against the int ids on $row['user']/hub.user_id,
        // regardless of whether the caller passed strings (real browser) or ints
        // (server-side pre-ticked defaults, or a test calling this directly).
        $confirmedNewIds = array_map('intval', $confirmedNewIds);
        $confirmedChangedIds = array_map('intval', $confirmedChangedIds);

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
            $this->applyHubProfile($row['user'], $row['hub']);
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
            'source' => UserSource::Hub,
        ]);
        $user->id = $person['user_id']; // must match the hub, never auto-increment — guide §4.6
        $user->save();

        UserPermissionWriter::write($user, $mapped);
        $this->applyHubProfile($user, $person);
    }

    /**
     * Farm, department ("Requests for"), and position — pulled from the hub for
     * source=hub rows, refreshed on every sync (not just at creation, so this
     * never goes stale the way an unrefreshed carry-over value did elsewhere in
     * this app). Farm and department arrive as display strings from the hub but
     * are real relations here (Farm FK, department_user_requestor pivot), so each
     * is resolved by name first.
     *
     * Deliberately only ever STRENGTHENS data, never degrades it: a name that
     * doesn't match anything logs a warning and leaves whatever was already
     * there alone, rather than nulling out (or, for department, wiping) a
     * working assignment over what's more likely a naming mismatch than a
     * genuine "remove this" signal — the hub has an explicit signal for removal
     * (active:false) and this isn't it.
     */
    private function applyHubProfile(User $user, array $person): void
    {
        $updates = [];

        $position = trim((string) ($person['position'] ?? ''));
        if ($position !== '') {
            $updates['position'] = $position;
        }

        $farmName = $person['farm'] ?? null;
        if ($farmName !== null) {
            $farmId = $this->resolveFarmId($farmName);
            if ($farmId !== null) {
                $updates['farm_id'] = $farmId;
            } else {
                Log::warning('Access Hub: farm name matched no known Farm, left unchanged', ['farm' => $farmName, 'user_id' => $user->id]);
            }
        }

        if ($updates !== []) {
            $user->update($updates);
        }

        $departmentName = $person['department'] ?? null;
        if ($departmentName !== null) {
            $departmentId = $this->resolveDepartmentId($departmentName);
            if ($departmentId !== null) {
                $user->requestorDepartments()->sync([$departmentId]);
            } else {
                Log::warning('Access Hub: department name matched no known Department, left unchanged', ['department' => $departmentName, 'user_id' => $user->id]);
            }
        }
    }

    /** Checks config/access_hub_farm_aliases.php first (e.g. hub's "BROOKDALE" -> PANDA's "BDL"), then falls back to a direct name match. */
    private function resolveFarmId(string $name): ?int
    {
        $name = trim($name);
        $normalized = Str::lower($name);

        $aliasTarget = collect(config('access_hub_farm_aliases'))
            ->mapWithKeys(fn (string $target, string $hubName) => [Str::lower($hubName) => $target])
            ->get($normalized);

        return Farm::whereRaw('LOWER(name) = ?', [Str::lower($aliasTarget ?? $name)])->value('id');
    }

    private function resolveDepartmentId(string $name): ?int
    {
        return Department::whereRaw('LOWER(name) = ?', [Str::lower(trim($name))])->value('id');
    }

    private function uniqueUsername(string $email, int $id): string
    {
        $base = Str::of($email)->before('@')->lower()->toString() ?: 'user';

        return User::withTrashed()->where('username', $base)->exists() ? "{$base}{$id}" : $base;
    }

    /**
     * @param  array<string,bool>  $mapped
     * @param  array  $person  raw hub person row — only checked for fields
     *                         applyHubProfile() would actually change (resolved
     *                         farm/department, non-blank position); an unmatched
     *                         name never counts as "differs" since it won't be
     *                         written either.
     */
    private function differsFromCurrent(User $user, array $mapped, array $person): bool
    {
        foreach (UserAccess::PERM_COLUMNS as $key => $column) {
            if ((bool) $user->{$column} !== ($mapped[$key] ?? false)) {
                return true;
            }
        }

        $position = trim((string) ($person['position'] ?? ''));
        if ($position !== '' && $position !== $user->position) {
            return true;
        }

        $farmName = $person['farm'] ?? null;
        if ($farmName !== null) {
            $farmId = $this->resolveFarmId($farmName);
            if ($farmId !== null && $farmId !== $user->farm_id) {
                return true;
            }
        }

        $departmentName = $person['department'] ?? null;
        if ($departmentName !== null) {
            $departmentId = $this->resolveDepartmentId($departmentName);
            if ($departmentId !== null && ! $user->requestorDepartments()->where('departments.id', $departmentId)->exists()) {
                return true;
            }
        }

        return false;
    }
}
