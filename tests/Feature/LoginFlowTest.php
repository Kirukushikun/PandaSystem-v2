<?php

use App\Models\AccessLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.auth_api.fake' => false, // these tests exercise the real external flow
        'services.auth_api.base_uri' => 'https://auth.test',
        'services.auth_api.api_key' => 'test-key',
        'services.auth_api.auth_user_api_key' => 'test-user-key',
        'services.turnstile.verify' => false,
    ]);
});

function fakeExternalAuth(bool $authOk = true, ?int $userId = null, string $email = 'kreyes@bfcgroup.org'): void
{
    Http::fake([
        'https://auth.test/api/v1/auth/login' => $authOk
            ? Http::response(['token' => 'tok-123', 'expires_at' => now()->addHour()->toISOString(), 'email' => $email])
            : Http::response(['message' => 'Incorrect email or password.'], 401),
        'https://auth.test/api/v1/users/get-user-id*' => Http::response(['id' => $userId]),
    ]);
}

test('the login page renders', function () {
    $this->get('/login')->assertOk()->assertSee('Sign in');
});

test('guests are redirected to login on every app route', function () {
    $this->get('/requests')->assertRedirect('/login');
    $this->get('/')->assertRedirect('/login');
    $this->get('/maintenance/danger')->assertRedirect('/login');
    $this->get('/pan/PAN-2026-00001/print')->assertRedirect('/login');
});

test('a valid company login with a local user row signs in and is access-logged', function () {
    $user = User::factory()->requestor()->create(['email' => 'kreyes@bfcgroup.org']);
    fakeExternalAuth(authOk: true, userId: $user->id);

    $response = $this->post('/login', ['email' => 'kreyes@bfcgroup.org', 'password' => 'secret']);

    $response->assertRedirect(route('requests.index'));
    $this->assertAuthenticatedAs($user);

    $log = AccessLog::sole();
    expect($log->email)->toBe('kreyes@bfcgroup.org')
        ->and($log->success)->toBeTrue()
        ->and(session('auth_token'))->toBe('tok-123');
});

test('wrong credentials are rejected with attempts remaining, and logged', function () {
    fakeExternalAuth(authOk: false);

    $response = $this->from('/login')->post('/login', ['email' => 'kreyes@bfcgroup.org', 'password' => 'nope']);

    $response->assertRedirect('/login')
        ->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('2 attempt(s) remaining');
    $this->assertGuest();
    expect(AccessLog::sole()->success)->toBeFalse();
});

test('three failed attempts lock the account for 15 minutes', function () {
    fakeExternalAuth(authOk: false);

    foreach (range(1, 3) as $i) {
        $this->post('/login', ['email' => 'kreyes@bfcgroup.org', 'password' => 'nope']);
    }

    $response = $this->from('/login')->post('/login', ['email' => 'kreyes@bfcgroup.org', 'password' => 'nope']);

    $response->assertInvalid(['email' => 'temporarily locked']);
    expect(AccessLog::count())->toBe(3); // the locked attempt never reaches the API
});

test('a valid company login with NO local user row is rejected — local users table is the authorization', function () {
    fakeExternalAuth(authOk: true, userId: 999999);

    $response = $this->from('/login')->post('/login', ['email' => 'kreyes@bfcgroup.org', 'password' => 'secret']);

    $response->assertInvalid(['email' => 'not authorized']);
    $this->assertGuest();
    expect(AccessLog::sole()->success)->toBeFalse();
});

test('app-to-app login signs in via encrypted user id', function () {
    $user = User::factory()->create();

    $this->get('/app-login/'.Crypt::encryptString((string) $user->id))
        ->assertRedirect(route('requests.index'));
    $this->assertAuthenticatedAs($user);
});

test('a tampered app-login id is rejected', function () {
    $response = $this->get('/app-login/garbage');

    $response->assertOk();
    expect($response->getContent())->toContain('Login Error [0]');
    $this->assertGuest();
});

test('an unknown app-login id gets no access', function () {
    $response = $this->get('/app-login/'.Crypt::encryptString('424242'));

    expect($response->getContent())->toContain('Login Error [2]');
    $this->assertGuest();
});

test('logout invalidates the session and returns to login', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
    $this->assertGuest();
});

test('when Turnstile is enabled, a failed challenge blocks login before the auth API is called', function () {
    config(['services.turnstile.verify' => true, 'services.turnstile.secret' => 's3cret']);
    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => false]),
    ]);

    $response = $this->from('/login')->post('/login', ['email' => 'kreyes@bfcgroup.org', 'password' => 'secret', 'turnstile_token' => 'bad']);

    $response->assertSessionHasErrors('turnstile_token');
    $this->assertGuest();
    expect(AccessLog::count())->toBe(0);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'auth.test'));
});

test('when Turnstile passes, login proceeds to the external auth flow', function () {
    config(['services.turnstile.verify' => true, 'services.turnstile.secret' => 's3cret']);
    $user = User::factory()->create(['email' => 'kreyes@bfcgroup.org']);
    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true]),
        'https://auth.test/api/v1/auth/login' => Http::response(['token' => 'tok-123', 'email' => 'kreyes@bfcgroup.org']),
        'https://auth.test/api/v1/users/get-user-id*' => Http::response(['id' => $user->id]),
    ]);

    $this->post('/login', ['email' => 'kreyes@bfcgroup.org', 'password' => 'secret', 'turnstile_token' => 'good'])
        ->assertRedirect(route('requests.index'));
    $this->assertAuthenticatedAs($user);
});
