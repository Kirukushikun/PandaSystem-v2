<?php

use App\Livewire\Admin\Users;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.auth_api.fake' => false, // directory mode requires real-auth mode
        'services.user_api.endpoint' => 'https://auth.test/api/v1/users',
        'services.user_api.key' => 'listing-key',
    ]);

    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

/** Mirrors the live API's real shape: a bare array, first/middle/last name fields. */
function fakeDirectory(array $users): void
{
    Http::fake([
        'https://auth.test/api/v1/users' => Http::response(array_map(fn (array $u) => [
            'id' => Crypt::encryptString((string) $u['id']),
            'first_name' => Illuminate\Support\Str::beforeLast($u['name'], ' '),
            'last_name' => Illuminate\Support\Str::afterLast($u['name'], ' '),
            'middle_name' => null,
            'email' => $u['email'],
            'created_at' => null,
            'updated_at' => now()->toISOString(),
        ], $users)),
    ]);
}

test('directory mode lists org users, marking who has PANDA access', function () {
    fakeDirectory([
        ['id' => $this->admin->id, 'name' => $this->admin->name, 'email' => $this->admin->email],
        ['id' => 77, 'name' => 'N. Villanueva', 'email' => 'nvillanueva@bfcgroup.org'],
    ]);

    Livewire::test(Users::class)
        ->assertSee('N. Villanueva')
        ->assertSee('No PANDA access')
        ->assertSee('Grant access')
        ->assertSee($this->admin->name);
});

test('granting access creates the local row with the API id and lands on User Access', function () {
    fakeDirectory([['id' => 77, 'name' => 'N. Villanueva', 'email' => 'nvillanueva@bfcgroup.org']]);

    Livewire::test(Users::class)->call('grant', 77)
        ->assertRedirect(route('admin.users.access', 'nvillanueva'));

    $user = User::findOrFail(77); // id must match the external system
    expect($user->email)->toBe('nvillanueva@bfcgroup.org')
        ->and($user->username)->toBe('nvillanueva')
        ->and($user->is_requestor)->toBeFalse(); // no permissions until User Access grants them
});

test('revoking soft-deletes and re-granting restores the same row, permissions intact', function () {
    $member = User::factory()->requestor()->create(['email' => 'kreyes@bfcgroup.org']);
    fakeDirectory([['id' => $member->id, 'name' => $member->name, 'email' => $member->email]]);

    Livewire::test(Users::class)->call('revoke', $member->id);
    expect(User::find($member->id))->toBeNull()
        ->and(User::withTrashed()->find($member->id)->trashed())->toBeTrue();

    Livewire::test(Users::class)->call('grant', $member->id);
    $restored = User::findOrFail($member->id);
    expect($restored->is_requestor)->toBeTrue(); // permissions survived the revoke
});

test('an admin cannot revoke their own access', function () {
    fakeDirectory([['id' => $this->admin->id, 'name' => $this->admin->name, 'email' => $this->admin->email]]);

    Livewire::test(Users::class)->call('revoke', $this->admin->id);

    expect(User::find($this->admin->id))->not->toBeNull();
});

test('ids encrypted with a foreign APP_KEY are skipped, and an unreachable directory shows empty state', function () {
    Http::fake(['https://auth.test/api/v1/users' => Http::response(['data' => [
        ['id' => 'not-encrypted-garbage', 'name' => 'Ghost', 'email' => 'ghost@x.test'],
    ]])]);

    Livewire::test(Users::class)->assertDontSee('Ghost');
});

test('grant and revoke 404 in dev fake-auth mode', function () {
    config(['services.auth_api.fake' => true]);

    Livewire::test(Users::class)->call('grant', 99)->assertStatus(404);
    expect(User::find(99))->toBeNull();
});
