<?php

use App\Models\AccessLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the bypass is a no-op while BYPASS_SECRET is empty', function () {
    config(['app.bypass_secret' => null]);
    User::factory()->admin()->create();

    $this->post('/bypass', ['p' => 'anything'])->assertRedirect('/login');
    $this->assertGuest();
});

test('a wrong secret fails silently to /login and is access-logged', function () {
    config(['app.bypass_secret' => 'correct-horse-battery-staple-32ch']);
    User::factory()->admin()->create();

    $this->post('/bypass', ['p' => 'wrong'])->assertRedirect('/login');
    $this->assertGuest();

    $log = AccessLog::sole();
    expect($log->email)->toBe('(bypass)')->and($log->success)->toBeFalse();
});

test('the correct secret signs in the configured bypass user and is access-logged', function () {
    $admin = User::factory()->admin()->create();
    config([
        'app.bypass_secret' => 'correct-horse-battery-staple-32ch',
        'app.bypass_user_id' => $admin->id,
    ]);

    $this->post('/bypass', ['p' => 'correct-horse-battery-staple-32ch'])
        ->assertRedirect(route('requests.index'));
    $this->assertAuthenticatedAs($admin);

    $log = AccessLog::sole();
    expect($log->email)->toBe($admin->email)->and($log->success)->toBeTrue();
});

test('without BYPASS_USER_ID it falls back to the first admin account', function () {
    User::factory()->create();                        // non-admin, lower id
    $admin = User::factory()->admin()->create();
    config(['app.bypass_secret' => 'correct-horse-battery-staple-32ch', 'app.bypass_user_id' => null]);

    $this->post('/bypass', ['p' => 'correct-horse-battery-staple-32ch']);

    $this->assertAuthenticatedAs($admin);
});

test('the bypass form renders for guests and bounces signed-in users into the app', function () {
    $this->get('/bypass')->assertOk()->assertSee('name="p"', false);

    $this->actingAs(User::factory()->create())
        ->get('/bypass')
        ->assertRedirect(route('requests.index'));
});

test('POST /bypass is rate-limited to 5 attempts per minute', function () {
    config(['app.bypass_secret' => 'correct-horse-battery-staple-32ch']);

    foreach (range(1, 5) as $i) {
        $this->post('/bypass', ['p' => 'wrong'])->assertRedirect('/login');
    }

    $this->post('/bypass', ['p' => 'wrong'])->assertStatus(429);
});
