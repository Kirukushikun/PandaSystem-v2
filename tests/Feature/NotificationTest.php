<?php

use App\Enums\PanStatus;
use App\Livewire\Shared\NotificationBell;
use App\Models\Department;
use App\Models\PanForm;
use App\Models\PanRequest;
use App\Models\User;
use App\Notifications\PanActivity;
use App\Services\PanWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function transition(PanRequest $pan, string $action): void
{
    $pan->update(['status' => app(PanWorkflow::class)->apply($pan->status, $action)]);
}

test('submitting pings the department heads — approving pings every preparer', function () {
    $department = Department::factory()->create();
    $head = User::factory()->divisionHead()->create();
    $head->headedDepartments()->attach($department);
    $preparer = User::factory()->hrPreparer()->create();

    $pan = PanRequest::factory()->status(PanStatus::Draft)->create(['department_id' => $department->id]);

    transition($pan, 'submit');
    expect($head->notifications()->count())->toBe(1)
        ->and($head->notifications()->first()->data['title'])->toBe('Awaiting your decision');

    transition($pan, 'approve_division');
    expect($preparer->notifications()->first()->data['title'])->toBe('Ready for tagging');
});

test('backward moves carry the return reason to the person who must fix it', function () {
    $requestor = User::factory()->requestor()->create();
    $pan = PanRequest::factory()->status(PanStatus::WithDivisionHead)->create(['requested_by' => $requestor->id]);

    $pan->returns()->create([
        'action' => 'return_to_requestor',
        'from_status' => PanStatus::WithDivisionHead,
        'to_status' => PanStatus::ReturnedToRequestor,
        'reason' => 'Incomplete supporting document',
        'returned_by' => User::factory()->divisionHead()->create()->id,
    ]);
    transition($pan, 'return_to_requestor');

    $note = $requestor->notifications()->sole();
    expect($note->data['title'])->toBe('Returned to you')
        ->and($note->data['body'])->toContain('Incomplete supporting document');
});

test('the actor is never notified about their own move', function () {
    $department = Department::factory()->create();
    $selfServing = User::factory()->requestor()->divisionHead()->create();
    $selfServing->headedDepartments()->attach($department);

    $this->actingAs($selfServing);
    $pan = PanRequest::factory()->status(PanStatus::Draft)
        ->create(['department_id' => $department->id, 'requested_by' => $selfServing->id]);

    transition($pan, 'submit'); // they head the very department they submitted for

    expect($selfServing->notifications()->count())->toBe(0);
});

test('tagging tells nobody; filing tells the requestor', function () {
    $preparer = User::factory()->hrPreparer()->create();
    $requestor = User::factory()->requestor()->create();
    $pan = PanRequest::factory()->status(PanStatus::AwaitingTag)
        ->create(['requested_by' => $requestor->id, 'hr_preparer_id' => $preparer->id]);

    transition($pan, 'tag');
    expect($preparer->notifications()->count())->toBe(0)
        ->and($requestor->notifications()->count())->toBe(0);

    $pan->update(['status' => PanStatus::Served]);
    $requestor->notifications()->delete(); // ignore intermediate pings for this assertion
    transition($pan, 'file');

    expect($requestor->notifications()->sole()->data['title'])->toBe('Filed — cycle complete');
});

test('final approval pings the preparer to serve', function () {
    $preparer = User::factory()->hrPreparer()->create();
    $pan = PanRequest::factory()->status(PanStatus::ForFinalApproval)->create(['hr_preparer_id' => $preparer->id]);

    transition($pan, 'approve_final');

    expect($preparer->notifications()->sole()->data['title'])->toBe('Approved — ready to serve');
});

test('expiry reminders go out once per preparer per PAN', function () {
    $preparer = User::factory()->hrPreparer()->create();
    $pan = PanRequest::factory()->status(PanStatus::Approved)->create();
    PanForm::factory()->create(['pan_request_id' => $pan->id, 'doe_to' => today()->addDays(5)]);

    $this->artisan('panda:expiry-reminders')->assertSuccessful();
    $this->artisan('panda:expiry-reminders')->assertSuccessful(); // second run must not duplicate

    $notes = $preparer->notifications()->get();
    expect($notes)->toHaveCount(1)
        ->and($notes->first()->data['title'])->toBe('Allowance expiring soon')
        ->and($notes->first()->data['context'])->toBe('Expiry reminder');
});

test('open-ended or far-future effectivity does not trigger reminders', function () {
    $preparer = User::factory()->hrPreparer()->create();
    $open = PanRequest::factory()->status(PanStatus::Approved)->create();
    PanForm::factory()->create(['pan_request_id' => $open->id, 'doe_to' => null]);
    $far = PanRequest::factory()->status(PanStatus::Approved)->create();
    PanForm::factory()->create(['pan_request_id' => $far->id, 'doe_to' => today()->addMonths(3)]);

    $this->artisan('panda:expiry-reminders')->assertSuccessful();

    expect($preparer->notifications()->count())->toBe(0);
});

test('the bell lists the signed-in user\'s notifications and marks all read', function () {
    $user = User::factory()->requestor()->create();
    $user->notify(new PanActivity('Returned to you', 'PAN-2026-00001 — test.', 'PAN-2026-00001', 'Requestor'));

    $this->actingAs($user);

    Livewire::test(NotificationBell::class)
        ->assertSee('Returned to you')
        ->call('markAllRead');

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('the bell re-renders when a live notification arrives (Reverb path)', function () {
    $user = User::factory()->requestor()->create();

    $component = Livewire::actingAs($user)->test(NotificationBell::class)
        ->assertDontSee('Filed — cycle complete');

    $user->notify(new PanActivity('Filed — cycle complete', 'PAN-2026-00002 — test.', 'PAN-2026-00002', 'Requestor'));

    // Mirrors what app.js's Echo listener dispatches on a broadcast — the
    // component doesn't touch the payload, it just re-fetches from the DB.
    $component->call('refresh')->assertSee('Filed — cycle complete');
});

test('PanActivity broadcasts on the user\'s private channel in addition to saving to the database', function () {
    Notification::fake();

    $user = User::factory()->requestor()->create();
    $notification = new PanActivity('Returned to you', 'PAN-2026-00003 — test.', 'PAN-2026-00003', 'Requestor');
    $user->notify($notification);

    Notification::assertSentTo($user, PanActivity::class, function (PanActivity $n) use ($user) {
        return in_array('database', $n->via($user), true)
            && in_array('broadcast', $n->via($user), true)
            && $n->toBroadcast($user)->data['title'] === 'Returned to you';
    });
});

test('the private channel rule only allows a user to open their own channel', function () {
    // Testing this through the real /broadcasting/auth HTTP endpoint would require
    // swapping the active broadcaster mid-test (phpunit.xml pins BROADCAST_CONNECTION
    // to null, which no-ops all channel authorization) — but Broadcast::channel()
    // registers its callback on whichever broadcaster instance is active AT BOOT,
    // so a later config() swap creates a fresh, empty-registered instance and every
    // request 403s regardless of the rule itself. Testing the rule directly avoids
    // that plumbing entirely and is exactly as strong a guarantee for this file's
    // one line of actual logic.
    Broadcast::shouldReceive('channel')
        ->once()
        ->with('App.Models.User.{id}', Mockery::on(function ($callback) use (&$rule) {
            $rule = $callback;

            return true;
        }));

    require base_path('routes/channels.php');

    $me = User::factory()->requestor()->create();
    $someoneElse = User::factory()->requestor()->create();

    expect($rule($me, $me->id))->toBeTrue()
        ->and($rule($me, $someoneElse->id))->toBeFalse();
});
