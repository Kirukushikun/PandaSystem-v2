<?php

use App\Enums\ConfidentialityTag;
use App\Enums\PanStatus;
use App\Livewire\ProxyApprover\Queue;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PanRequest;
use App\Models\ProxyApproverSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->proxy = User::factory()->proxyApprover()->create();
    $this->department = Department::factory()->create();
    $this->head = User::factory()->divisionHead()->create();
    $this->head->headedDepartments()->attach($this->department);
    $this->employee = Employee::factory()->create(['department_id' => $this->department->id]);

    $this->actingAs($this->proxy);
});

function stalePan(PanStatus $status, array $extra = []): PanRequest
{
    return PanRequest::factory()->status($status)->create([
        'employee_id' => test()->employee->id,
        'department_id' => test()->department->id,
        'submitted_at' => now()->subDays(20),
        ...$extra,
    ]);
}

test('only Tarlac/Untagged PANs past the threshold appear, never Manila, never fresh ones', function () {
    $stale = stalePan(PanStatus::WithDivisionHead, ['reference' => 'PAN-2026-91001']);
    $fresh = PanRequest::factory()->status(PanStatus::WithDivisionHead)->create([
        'employee_id' => $this->employee->id, 'department_id' => $this->department->id,
        'submitted_at' => now()->subDays(2), 'reference' => 'PAN-2026-91002',
    ]);
    $manila = PanRequest::factory()->status(PanStatus::WithDivisionHead)->manila()->create([
        'employee_id' => $this->employee->id, 'department_id' => $this->department->id,
        'submitted_at' => now()->subDays(20), 'reference' => 'PAN-2026-91003',
    ]);

    Livewire::test(Queue::class)
        ->assertSee('PAN-2026-91001')
        ->assertDontSee('PAN-2026-91002')
        ->assertDontSee('PAN-2026-91003');
});

test('a PAN already proxy-approved once is eligible again immediately, no repeat wait', function () {
    $pan = stalePan(PanStatus::WithDivisionHead);
    Livewire::test(Queue::class)
        ->call('startReason', $pan->id, 'proxy_approve_dh')
        ->set('reason', 'The approval waiting period took too long')
        ->call('submitReason')
        ->assertHasNoErrors();

    $pan->refresh();
    expect($pan->status)->toBe(PanStatus::AwaitingTag);

    // Simulate it reaching ForConfirmation moments later — submitted_at never resets
    // on a real transition, so it's still 20 days old; eligibility here should come
    // from the prior proxy_approve_dh log, not from staleness at all.
    $pan->update(['status' => PanStatus::ForConfirmation]);

    Livewire::test(Queue::class)->assertSee($pan->reference);
});

test('proxy-approving requires a reason and writes the pan_returns row', function () {
    $pan = stalePan(PanStatus::WithDivisionHead);

    $test = Livewire::test(Queue::class)
        ->call('startReason', $pan->id, 'proxy_approve_dh')
        ->call('submitReason')
        ->assertHasErrors(['reason' => 'required']);

    $test->set('reason', 'The approval waiting period took too long')
        ->call('submitReason')
        ->assertHasNoErrors();

    expect($pan->fresh()->status)->toBe(PanStatus::AwaitingTag)
        ->and($pan->returns()->sole())
        ->action->toBe('proxy_approve_dh')
        ->reason->toBe('The approval waiting period took too long')
        ->returned_by->toBe($this->proxy->id);
});

test('proxy-confirming a stalled ForConfirmation PAN forwards it to the HR Approver', function () {
    $pan = stalePan(PanStatus::ForConfirmation, ['confidentiality_tag' => ConfidentialityTag::Tarlac]);

    Livewire::test(Queue::class)
        ->call('startReason', $pan->id, 'proxy_approve_confirmation')
        ->set('reason', 'Backlog clearance — HR-initiated')
        ->call('submitReason')
        ->assertHasNoErrors();

    expect($pan->fresh()->status)->toBe(PanStatus::ForHrApproval)
        ->and($pan->returns()->sole()->action)->toBe('proxy_approve_confirmation');
});

test('disabling the feature 403s the action and empties the queue', function () {
    ProxyApproverSetting::current()->update(['enabled' => false]);
    $pan = stalePan(PanStatus::WithDivisionHead);

    Livewire::test(Queue::class)->assertDontSee($pan->reference);

    Livewire::test(Queue::class)
        ->call('startReason', $pan->id, 'proxy_approve_dh')
        ->call('submitReason')
        ->assertForbidden();

    expect($pan->fresh()->status)->toBe(PanStatus::WithDivisionHead);
});

test('a plain requestor cannot proxy-approve even with a hand-crafted call', function () {
    $requestor = User::factory()->requestor()->create();
    $pan = stalePan(PanStatus::WithDivisionHead);

    $this->actingAs($requestor);
    Livewire::test(Queue::class)
        ->call('startReason', $pan->id, 'proxy_approve_dh')
        ->call('submitReason')
        ->assertForbidden();
});
