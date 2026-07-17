<?php

use App\Models\AccessLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.auth_api.fake' => true, 'services.turnstile.verify' => false]);
});

test('fake mode signs in a seeded email with any password, without touching the external API', function () {
    Http::fake(); // any HTTP call would be recorded
    $user = User::factory()->requestor()->create(['email' => 'kreyes@bfcgroup.org']);

    $this->post('/login', ['email' => 'kreyes@bfcgroup.org', 'password' => 'whatever'])
        ->assertRedirect(route('requests.index'));

    $this->assertAuthenticatedAs($user);
    Http::assertNothingSent();
    expect(AccessLog::sole()->success)->toBeTrue();
});

test('fake mode still rejects emails with no local user row', function () {
    $response = $this->from('/login')->post('/login', ['email' => 'ghost@bfcgroup.org', 'password' => 'whatever']);

    $response->assertInvalid(['email' => 'not authorized']);
    $this->assertGuest();
    expect(AccessLog::sole()->success)->toBeFalse();
});

test('fake mode is ignored in production — the external API path runs instead', function () {
    $this->app['env'] = 'production';
    config(['services.auth_api.base_uri' => 'https://auth.test']);
    Http::fake(['https://auth.test/*' => Http::response(['message' => 'nope'], 401)]);
    User::factory()->create(['email' => 'kreyes@bfcgroup.org']);

    // outside the 'testing' env CSRF validation is active again — send a real token
    $this->withSession(['_token' => 'test-token'])
        ->post('/login', ['_token' => 'test-token', 'email' => 'kreyes@bfcgroup.org', 'password' => 'whatever']);

    $this->assertGuest();
    Http::assertSent(fn ($request) => str_contains($request->url(), 'auth.test'));
});
