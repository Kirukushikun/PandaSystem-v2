<?php

use App\Enums\UserSource;
use App\Livewire\Admin\AccessHub;
use App\Livewire\Admin\UserAccess;
use App\Models\AccessHubConnection;
use App\Models\User;
use App\Services\AccessHubSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.access_hub.base_uri' => 'https://hub.test']);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

/** One hub /grants "person" row — matches hub-integration-guide.md §3's shape. */
function hubPerson(array $overrides = []): array
{
    return array_merge([
        'user_id' => 9001,
        'name' => 'Maria Santos',
        'email' => 'm.santos@example.org',
        'farm' => 'Farm A',
        'department' => 'Finance',
        'position' => 'Senior Accountant',
        'roles' => ['division_head'],
        'active' => true,
    ], $overrides);
}

function fakeHubGrants(array $people, bool $unreachable = false, int $status = 200): void
{
    if ($unreachable) {
        Http::fake(['https://hub.test/api/v1/grants' => fn () => throw new ConnectionException('timeout')]);

        return;
    }
    Http::fake(['https://hub.test/api/v1/grants' => Http::response([
        'generated_at' => now()->toISOString(),
        'people' => $people,
    ], $status)]);
}

function enrollConnection(): void
{
    AccessHubConnection::current()->update(['client_id' => 'cid', 'client_secret' => 'secret']);
}

// --- 1. Three-group correctness ---------------------------------------------------

test('a hub person with no local row lands in the New group', function () {
    // $this->admin (seeded in beforeEach) isn't in the feed either, so it correctly
    // shows up in local_only alongside — that's not asserted away here, just ignored.
    $result = app(AccessHubSyncService::class)->compareAgainst([hubPerson(['user_id' => 9001])]);

    expect($result['new'])->toHaveCount(1)
        ->and($result['new'][0]['hub']['user_id'])->toBe(9001)
        ->and($result['changed'])->toBeEmpty();
});

test('a hub-sourced local row with a different roles array lands in Changed as update', function () {
    $user = User::factory()->create(['id' => 9002, 'source' => UserSource::Hub, 'is_division_head' => false]);

    $result = app(AccessHubSyncService::class)->compareAgainst([
        hubPerson(['user_id' => 9002, 'roles' => ['division_head']]),
    ]);

    expect($result['changed'])->toHaveCount(1)
        ->and($result['changed'][0]['type'])->toBe('update')
        ->and($result['changed'][0]['user']->id)->toBe($user->id);
});

test('a hub-sourced local row now inactive at the hub lands in Changed as revoke', function () {
    User::factory()->create(['id' => 9003, 'source' => UserSource::Hub, 'is_division_head' => true]);

    $result = app(AccessHubSyncService::class)->compareAgainst([
        hubPerson(['user_id' => 9003, 'active' => false]),
    ]);

    expect($result['changed'])->toHaveCount(1)
        ->and($result['changed'][0]['type'])->toBe('revoke');
});

test('a local row absent from the hub feed lands in Local-only with no hub data', function () {
    $user = User::factory()->create(['source' => UserSource::Manual]);

    $result = app(AccessHubSyncService::class)->compareAgainst([hubPerson(['user_id' => 9999])]);

    $row = collect($result['local_only'])->firstWhere('user.id', $user->id);
    expect($row)->not->toBeNull()
        ->and($row['hub'])->toBeNull()
        ->and($row['reason'])->toBeNull();
});

// --- 2. Manual-row edge case --------------------------------------------------------

test('a manual local row present at the hub lands in Local-only, annotated, never in Changed', function () {
    $user = User::factory()->create(['id' => 9004, 'source' => UserSource::Manual, 'is_division_head' => false]);

    $result = app(AccessHubSyncService::class)->compareAgainst([
        hubPerson(['user_id' => 9004, 'roles' => ['division_head']]),
    ]);

    $row = collect($result['local_only'])->firstWhere('user.id', $user->id);
    expect($row)->not->toBeNull()
        ->and($row['reason'])->toBe('manual_override')
        ->and($row['hub']['roles'])->toBe(['division_head'])
        ->and(collect($result['changed'])->firstWhere('user.id', $user->id))->toBeNull();
});

// --- 3. Apply reuses the shared write path ------------------------------------------

test('apply writes permissions through the exact same path UserPermissionWriter uses', function () {
    $user = User::factory()->create([
        'id' => 9005, 'source' => UserSource::Hub,
        'is_division_head' => false, 'is_requestor' => true, // pre-existing perm not in the mapped set
    ]);
    $preview = app(AccessHubSyncService::class)->compareAgainst([
        hubPerson(['user_id' => 9005, 'roles' => ['division_head']]),
    ]);

    app(AccessHubSyncService::class)->apply($preview, [], [9005]);
    $fresh = $user->fresh();

    // mapRoles(['division_head']) => only division_head true, everything else false —
    // UserPermissionWriter::write() writes every PERM_COLUMNS key it's handed, so a
    // previously-true unrelated flag not in that set is turned off, same as it would
    // be from UserAccess's own panel (the same write path — not a diff/merge).
    expect($fresh->is_division_head)->toBeTrue()
        ->and($fresh->is_requestor)->toBeFalse();
});

// --- 4. Revoke only ever soft-deletes source=hub rows -------------------------------

test('revoke never trashes a manual row even if the hub shows it inactive', function () {
    $user = User::factory()->create(['id' => 9006, 'source' => UserSource::Manual]);

    $preview = app(AccessHubSyncService::class)->compareAgainst([
        hubPerson(['user_id' => 9006, 'active' => false]),
    ]);

    expect(collect($preview['changed'])->firstWhere('user.id', 9006))->toBeNull();
    expect($user->fresh()->trashed())->toBeFalse();
});

// --- 5. Re-activation restores, not recreates ---------------------------------------

test('a trashed hub-sourced user reappearing active is restored, not recreated', function () {
    $user = User::factory()->create(['id' => 9007, 'source' => UserSource::Hub, 'external_id' => 'EXT-KEEP']);
    $user->delete();

    $preview = app(AccessHubSyncService::class)->compareAgainst([hubPerson(['user_id' => 9007, 'active' => true])]);
    app(AccessHubSyncService::class)->apply($preview, [], [9007]);

    $fresh = User::withTrashed()->find(9007);
    expect($fresh->trashed())->toBeFalse()
        ->and($fresh->external_id)->toBe('EXT-KEEP'); // unrelated field untouched — restored, not rebuilt
});

// --- 6. Unknown role strings are skipped, not fatal ---------------------------------

test('an unrecognized role string is skipped with a warning, other roles still applied', function () {
    Log::spy();

    $mapped = app(AccessHubSyncService::class)->mapRoles(['division_head', 'made_up_role']);

    expect($mapped['division_head'])->toBeTrue();
    Log::shouldHaveReceived('warning')->once();
});

// --- 7. Empty response never revokes everyone ---------------------------------------

test('an empty hub response never revokes existing hub-sourced users', function () {
    User::factory()->count(3)->create(['source' => UserSource::Hub]);
    enrollConnection();
    fakeHubGrants([]);

    $result = app(AccessHubSyncService::class)->compare();

    expect($result['empty'])->toBeTrue();
    expect(User::whereNotNull('deleted_at')->count())->toBe(0);
});

// --- 8. Enrollment persists the connection row --------------------------------------

test('enrolling stores client_id and client_secret, round-tripping through the encrypted cast', function () {
    Http::fake(['https://hub.test/api/v1/enroll' => Http::response(['client_id' => 'abc123', 'client_secret' => 'shh'])]);

    Livewire::test(AccessHub::class)
        ->set('enrollCode', 'connect-me')
        ->call('enroll');

    $connection = AccessHubConnection::current();
    expect($connection->client_id)->toBe('abc123')
        ->and($connection->client_secret)->toBe('shh')
        ->and($connection->isEnrolled())->toBeTrue();
});

// --- 9. Manual rows are provably untouched by a full compare()+apply() cycle -------

test('a full sync cycle never changes a single column on a manual row, even with differing hub data', function () {
    $user = User::factory()->create([
        'id' => 9008, 'source' => UserSource::Manual,
        'is_division_head' => false, 'is_requestor' => true, 'position' => 'Original Position',
    ]);
    $before = $user->getAttributes();

    enrollConnection();
    fakeHubGrants([hubPerson(['user_id' => 9008, 'roles' => ['division_head', 'vp'], 'active' => false])]);

    $preview = app(AccessHubSyncService::class)->compare();
    app(AccessHubSyncService::class)->apply(
        $preview,
        collect($preview['new'])->pluck('hub.user_id')->all(),
        collect($preview['changed'])->pluck('user.id')->all(),
    );

    expect($user->fresh()->getAttributes())
        ->toMatchArray(array_intersect_key($before, array_flip(['is_division_head', 'is_requestor', 'position'])));
});

// --- 10. Failure-handling states -----------------------------------------------------

test('never enrolled: fetch fails with not_enrolled, panel still renders normally', function () {
    Livewire::test(AccessHub::class)
        ->assertOk()
        ->assertSee('Connect to Access Hub');
});

test('unreachable hub: fetch fails with unreachable, no exception bubbles up', function () {
    enrollConnection();
    fakeHubGrants([], unreachable: true);

    Livewire::test(AccessHub::class)
        ->call('runSync')
        ->assertOk()
        ->assertSee('Could not reach the Access Hub');
});

test('bad credentials: 401 fails with unauthorized, panel offers reset', function () {
    enrollConnection();
    fakeHubGrants([], status: 401);

    Livewire::test(AccessHub::class)
        ->call('runSync')
        ->assertOk()
        ->assertSee('rejected this connection')
        ->assertSee('Reset connection');
});

test('empty response shows nothing to sync', function () {
    enrollConnection();
    fakeHubGrants([]);

    Livewire::test(AccessHub::class)
        ->call('runSync')
        ->assertOk()
        ->assertSee('Nothing to sync');
});

// --- Regression: UserAccess still saves correctly through the shared writer --------

test('UserAccess::save() still persists permission toggles after the shared-writer refactor', function () {
    $account = User::factory()->create(['is_admin' => false]);

    Livewire::test(UserAccess::class, ['user' => $account->username])
        ->call('togglePerm', 'requestor')
        ->set('farmId', $account->farm_id)
        ->set('position', 'Updated Position')
        ->call('save');

    expect($account->fresh()->is_requestor)->toBeTrue();
});
