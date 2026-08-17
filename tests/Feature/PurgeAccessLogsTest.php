<?php

use App\Models\AccessLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function accessLogAt(string $email, \Illuminate\Support\Carbon $createdAt): AccessLog
{
    $log = AccessLog::create(['email' => $email, 'success' => true, 'ip_address' => '127.0.0.1', 'user_agent' => 'test']);
    $log->forceFill(['created_at' => $createdAt])->save();

    return $log;
}

test('deletes only access-log entries past the retention window', function () {
    $old = accessLogAt('old@bfcgroup.org', now()->subDays(31));
    $recent = accessLogAt('recent@bfcgroup.org', now()->subDays(5));

    $this->artisan('panda:purge-access-logs')->assertSuccessful();

    expect(AccessLog::find($old->id))->toBeNull()
        ->and(AccessLog::find($recent->id))->not->toBeNull();
});

test('the retention window is configurable via --days', function () {
    $log = accessLogAt('a@bfcgroup.org', now()->subDays(10));

    $this->artisan('panda:purge-access-logs', ['--days' => 3])->assertSuccessful();

    expect(AccessLog::find($log->id))->toBeNull();
});
