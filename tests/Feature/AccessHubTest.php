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

test('a 429 on /grants is rate_limited, distinct from a generic unreachable failure', function () {
    enrollConnection();
    fakeHubGrants([], status: 429);

    Livewire::test(AccessHub::class)
        ->call('runSync')
        ->assertOk()
        ->assertSee('rate-limiting sync requests');
});

test('a 429 on /enroll shows a distinct rate-limited message, not a generic one', function () {
    Http::fake(['https://hub.test/api/v1/enroll' => Http::response([], 429)]);

    Livewire::test(AccessHub::class)
        ->set('enrollCode', 'connect-me')
        ->call('enroll')
        ->assertSee('Too many attempts');

    expect(AccessHubConnection::current()->isEnrolled())->toBeFalse();
});

test('an invalid enroll code gets its own message', function () {
    Http::fake(['https://hub.test/api/v1/enroll' => Http::response([], 422)]);

    Livewire::test(AccessHub::class)->set('enrollCode', 'bad')->call('enroll')
        ->assertSee('wrong, expired, or already used');
});

test('an unrecognized project key gets its own message, distinct from a bad code', function () {
    Http::fake(['https://hub.test/api/v1/enroll' => Http::response([], 404)]);

    Livewire::test(AccessHub::class)->set('enrollCode', 'bad')->call('enroll')
        ->assertSee('does not recognize this project');
});

// --- New rows are pre-ticked by default (guide §4.5: "Safe — tick all") ------------

test('New rows arrive pre-selected; Changed rows do not', function () {
    User::factory()->create(['id' => 9010, 'source' => UserSource::Hub, 'is_division_head' => false]);
    enrollConnection();
    fakeHubGrants([
        hubPerson(['user_id' => 9011, 'roles' => ['manager']]), // new
        hubPerson(['user_id' => 9010, 'roles' => ['division_head']]), // changed
    ]);

    $component = Livewire::test(AccessHub::class)->call('runSync');

    expect($component->get('selectedNew'))->toBe([9011])
        ->and($component->get('selectedChanged'))->toBe([]);
});

// --- Exact wire protocol (guide §5-equivalent verification: assert what's actually sent) --

test('fetchGrants sends X-Client-Id/X-Client-Secret, not x-api-key or Authorization', function () {
    enrollConnection();
    fakeHubGrants([]);

    app(\App\Services\AccessHubSyncService::class)->compare();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://hub.test/api/v1/grants'
            && $request->hasHeader('X-Client-Id', 'cid')
            && $request->hasHeader('X-Client-Secret', 'secret')
            && ! $request->hasHeader('x-api-key')
            && ! $request->hasHeader('Authorization');
    });
});

test('enroll posts code, project_key, and environment as the request body', function () {
    config(['services.access_hub.project_key' => 'panda-v2']);
    Http::fake(['https://hub.test/api/v1/enroll' => Http::response(['client_id' => 'a', 'client_secret' => 'b'])]);

    app(\App\Services\AccessHubService::class)->enroll('the-code');

    Http::assertSent(fn ($request) => $request->url() === 'https://hub.test/api/v1/enroll'
        && $request['code'] === 'the-code'
        && $request['project_key'] === 'panda-v2'
        && $request['environment'] === app()->environment());
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

// --- Farm/department/position are pulled from the hub (resolved against real FKs) --

test('a newly-created hub row gets farm, requestor department, and position from the hub', function () {
    $farm = App\Models\Farm::factory()->create(['name' => 'BFC']);
    $department = App\Models\Department::factory()->create(['name' => 'Poultry']);

    $preview = app(AccessHubSyncService::class)->compareAgainst([
        hubPerson(['user_id' => 9012, 'farm' => 'bfc', 'department' => 'poultry', 'position' => 'Senior Accountant']),
    ]);
    app(AccessHubSyncService::class)->apply($preview, [9012], []);

    $user = User::find(9012);
    expect($user->position)->toBe('Senior Accountant')
        ->and($user->farm_id)->toBe($farm->id)
        ->and($user->requestorDepartments()->pluck('departments.id')->all())->toBe([$department->id]);
});

test('the hub\'s "BROOKDALE" resolves to PANDA\'s BDL farm code via the alias config', function () {
    $bdl = App\Models\Farm::factory()->create(['name' => 'BDL']);
    App\Models\Farm::factory()->create(['name' => 'BRD']); // the other Brookdale code — must NOT be picked

    $preview = app(AccessHubSyncService::class)->compareAgainst([
        hubPerson(['user_id' => 9017, 'farm' => 'BROOKDALE']),
    ]);
    app(AccessHubSyncService::class)->apply($preview, [9017], []);

    expect(User::find(9017)->farm_id)->toBe($bdl->id);
});

test('the hub\'s "RH/BBGC" resolves to PANDA\'s RH farm code via the alias config', function () {
    $rh = App\Models\Farm::factory()->create(['name' => 'RH']);

    $preview = app(AccessHubSyncService::class)->compareAgainst([
        hubPerson(['user_id' => 9018, 'farm' => 'RH/BBGC']),
    ]);
    app(AccessHubSyncService::class)->apply($preview, [9018], []);

    expect(User::find(9018)->farm_id)->toBe($rh->id);
});

test('an unmatched farm or department name logs a warning and never crashes the sync', function () {
    Log::spy();

    $preview = app(AccessHubSyncService::class)->compareAgainst([
        hubPerson(['user_id' => 9014, 'farm' => 'Nonexistent Farm', 'department' => 'Nonexistent Dept']),
    ]);
    app(AccessHubSyncService::class)->apply($preview, [9014], []);

    $user = User::find(9014);
    expect($user)->not->toBeNull()
        ->and($user->farm_id)->toBeNull()
        ->and($user->requestorDepartments()->count())->toBe(0);
    Log::shouldHaveReceived('warning')->with('Access Hub: farm name matched no known Farm, left unchanged', \Mockery::any())->once();
    Log::shouldHaveReceived('warning')->with('Access Hub: department name matched no known Department, left unchanged', \Mockery::any())->once();
});

test('a profile-only change (farm/department/position) surfaces in Changed even with no permission change', function () {
    $farm = App\Models\Farm::factory()->create(['name' => 'PFC']);
    $user = User::factory()->create(['id' => 9015, 'source' => UserSource::Hub, 'is_requestor' => true, 'farm_id' => null]);

    $preview = app(AccessHubSyncService::class)->compareAgainst([
        hubPerson(['user_id' => 9015, 'roles' => ['manager'], 'farm' => 'PFC']), // same role, different farm
    ]);

    expect(collect($preview['changed'])->firstWhere('user.id', 9015))->not->toBeNull();

    app(AccessHubSyncService::class)->apply($preview, [], [9015]);
    expect($user->fresh()->farm_id)->toBe($farm->id);
});

test('re-syncing an already-resolved profile does not spuriously mark the row Changed', function () {
    $farm = App\Models\Farm::factory()->create(['name' => 'RH']);
    $department = App\Models\Department::factory()->create(['name' => 'Swine']);
    $user = User::factory()->create([
        'id' => 9016, 'source' => UserSource::Hub, 'is_requestor' => true,
        'farm_id' => $farm->id, 'position' => 'Farm Hand',
    ]);
    $user->requestorDepartments()->sync([$department->id]);

    $preview = app(AccessHubSyncService::class)->compareAgainst([
        hubPerson(['user_id' => 9016, 'roles' => ['manager'], 'farm' => 'rh', 'department' => 'swine', 'position' => 'Farm Hand']),
    ]);

    expect(collect($preview['changed'])->firstWhere('user.id', 9016))->toBeNull();
});

// --- A hand edit on a hub-owned row detaches it from sync -----------------------------

test('saving a hub-owned account through UserAccess flips it to manual', function () {
    $account = User::factory()->create(['source' => UserSource::Hub, 'is_admin' => false]);

    Livewire::test(UserAccess::class, ['user' => $account->username])
        ->call('togglePerm', 'requestor')
        ->set('farmId', $account->farm_id)
        ->set('position', 'Edited by hand')
        ->call('save');

    expect($account->fresh()->source)->toBe(UserSource::Manual);
});

test('a hub-owned account detached by a hand edit is untouched by the next sync', function () {
    $account = User::factory()->create(['id' => 9013, 'source' => UserSource::Hub, 'is_division_head' => false]);

    Livewire::test(UserAccess::class, ['user' => $account->username])
        ->call('togglePerm', 'division_head') // manual grant, deliberately not what the hub maps
        ->set('farmId', $account->farm_id)
        ->set('position', $account->position ?? 'Manager')
        ->call('save');

    $preview = app(AccessHubSyncService::class)->compareAgainst([
        hubPerson(['user_id' => 9013, 'roles' => ['manager']]), // hub disagrees — would map to requestor only
    ]);

    expect(collect($preview['changed'])->firstWhere('user.id', 9013))->toBeNull();
    expect($account->fresh()->is_division_head)->toBeTrue(); // the hand grant survives
});
