<?php

use App\Contracts\ExternalAuthenticator;
use App\Models\AccessLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the fake external authenticator accepts seeded users with the dev password', function () {
    $user = User::factory()->create(['username' => 'kreyes', 'name' => 'K. Reyes']);

    $profile = app(ExternalAuthenticator::class)->attempt('kreyes', 'password');

    expect($profile)->not->toBeNull()
        ->and($profile['username'])->toBe('kreyes')
        ->and($profile['name'])->toBe('K. Reyes');
});

test('the fake external authenticator rejects wrong passwords and unknown users', function () {
    User::factory()->create(['username' => 'kreyes']);

    $auth = app(ExternalAuthenticator::class);

    expect($auth->attempt('kreyes', 'wrong-password'))->toBeNull()
        ->and($auth->attempt('ghost', 'password'))->toBeNull();
});

test('a successful login is recorded in the access log', function () {
    $user = User::factory()->create(['username' => 'kreyes']);

    event(new Login('web', $user, false));

    expect(AccessLog::count())->toBe(1);

    $log = AccessLog::first();
    expect($log->username)->toBe('kreyes')
        ->and($log->user_id)->toBe($user->id)
        ->and($log->successful)->toBeTrue();
});

test('a failed attempt is recorded with the username as typed — even with no account', function () {
    event(new Failed('web', null, ['username' => 'admin', 'password' => 'guess']));

    $log = AccessLog::first();
    expect($log)->not->toBeNull()
        ->and($log->username)->toBe('admin')
        ->and($log->user_id)->toBeNull()
        ->and($log->successful)->toBeFalse();
});
