<?php

use App\Enums\PanStatus;
use App\Livewire\DivisionHead\Queue;
use App\Livewire\DivisionHead\Show;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->head = User::factory()->divisionHead()->create();
    $this->head->headedDepartments()->attach($this->department);
    $this->employee = Employee::factory()->create(['department_id' => $this->department->id]);

    $this->actingAs($this->head);
});

function deptPan(PanStatus $status, array $extra = []): PanRequest
{
    return PanRequest::factory()->status($status)->create([
        'employee_id' => test()->employee->id,
        'department_id' => test()->department->id,
        ...$extra,
    ]);
}

test('approving a submitted request forwards it to HR Preparation and records the deciding head', function () {
    $pan = deptPan(PanStatus::WithDivisionHead);

    Livewire::test(Queue::class)->call('approve', $pan->id);

    $pan->refresh();
    expect($pan->status)->toBe(PanStatus::AwaitingTag)
        ->and($pan->division_head_id)->toBe($this->head->id);
});

test('returning to the requestor demands a reason and writes the pan_returns row', function () {
    $pan = deptPan(PanStatus::WithDivisionHead);

    $test = Livewire::test(Queue::class)
        ->call('startReason', $pan->id, 'return_to_requestor')
        ->call('submitReason')
        ->assertHasErrors(['reason' => 'required']);

    $test->set('reason', 'Incomplete supporting document')
        ->set('details', 'Page 2 of the evaluation form is missing.')
        ->call('submitReason')
        ->assertHasNoErrors();

    expect($pan->fresh()->status)->toBe(PanStatus::ReturnedToRequestor)
        ->and($pan->returns()->sole())
        ->action->toBe('return_to_requestor')
        ->reason->toBe('Incomplete supporting document')
        ->returned_by->toBe($this->head->id);
});

test('a custom reason requires the details field', function () {
    $pan = deptPan(PanStatus::WithDivisionHead);

    Livewire::test(Queue::class)
        ->call('startReason', $pan->id, 'return_to_requestor')
        ->set('reason', 'Custom reason…')
        ->call('submitReason')
        ->assertHasErrors(['details' => 'required_if']);

    expect($pan->fresh()->status)->toBe(PanStatus::WithDivisionHead);
});

test('confirming an HR-prepared PAN forwards it to the HR Approver', function () {
    $pan = deptPan(PanStatus::ForConfirmation);

    Livewire::test(Queue::class)->call('confirmPrepared', $pan->id);

    expect($pan->fresh()->status)->toBe(PanStatus::ForHrApproval);
});

test('disputing a prepared PAN sends it back to In Preparation with the reason on record', function () {
    $pan = deptPan(PanStatus::ForConfirmation);

    Livewire::test(Queue::class)
        ->call('startReason', $pan->id, 'dispute')
        ->set('reason', 'Prepared values are incorrect')
        ->call('submitReason')
        ->assertHasNoErrors();

    expect($pan->fresh()->status)->toBe(PanStatus::InPreparation)
        ->and($pan->returns()->sole()->action)->toBe('dispute');
});

test('the queue shows the department\'s PANs but never drafts, Manila rows, or other departments', function () {
    $mine = deptPan(PanStatus::WithDivisionHead, ['reference' => 'PAN-2026-90101']);
    deptPan(PanStatus::Draft, ['reference' => 'PAN-2026-90102']);
    PanRequest::factory()->status(PanStatus::InPreparation)->manila()->create([
        'employee_id' => $this->employee->id,
        'department_id' => $this->department->id,
        'reference' => 'PAN-2026-90103',
    ]);
    PanRequest::factory()->status(PanStatus::WithDivisionHead)->create(['reference' => 'PAN-2026-90104']);

    Livewire::test(Queue::class)
        ->set('filter', 'all')
        ->assertSee('PAN-2026-90101')
        ->assertDontSee('PAN-2026-90102')  // draft — in nobody's queue
        ->assertDontSee('PAN-2026-90103')  // Manila — DH Head territory, even own dept
        ->assertDontSee('PAN-2026-90104'); // someone else's department
});

test('"All stages" keeps a PAN visible read-only long after this head\'s decision is done, including once it\'s Filed', function () {
    $filed = deptPan(PanStatus::Filed, ['reference' => 'PAN-2026-90111']);
    $inPrep = deptPan(PanStatus::InPreparation, ['reference' => 'PAN-2026-90112']);

    Livewire::test(Queue::class)
        ->set('filter', 'all')
        ->assertSee('PAN-2026-90111')
        ->assertSee('PAN-2026-90112')
        ->set('filter', 'completed')
        ->assertSee('PAN-2026-90111')
        ->assertDontSee('PAN-2026-90112');
});

test('a DH Head sees ONLY Manila PANs, across all departments', function () {
    $dhHead = User::factory()->dhHead()->create();

    deptPan(PanStatus::WithDivisionHead, ['reference' => 'PAN-2026-90201']); // routine
    PanRequest::factory()->status(PanStatus::ForConfirmation)->manila()
        ->create(['reference' => 'PAN-2026-90202']); // Manila, some other department

    $this->actingAs($dhHead);
    Livewire::test(Queue::class)
        ->set('filter', 'all')
        ->assertSee('PAN-2026-90202')
        ->assertDontSee('PAN-2026-90201');
});

test('acting on another department\'s PAN is forbidden even with a hand-crafted request', function () {
    $foreign = PanRequest::factory()->status(PanStatus::WithDivisionHead)->create();

    Livewire::test(Queue::class)
        ->call('approve', $foreign->id)
        ->assertForbidden();

    expect($foreign->fresh()->status)->toBe(PanStatus::WithDivisionHead);
});

test('a department head cannot approve a Manila PAN of their own department', function () {
    $manila = PanRequest::factory()->status(PanStatus::ForConfirmation)->manila()->create([
        'employee_id' => $this->employee->id,
        'department_id' => $this->department->id,
    ]);

    Livewire::test(Queue::class)
        ->call('confirmPrepared', $manila->id)
        ->assertForbidden();
});

test('a PAN already tagged Manila skips re-tagging when it comes back through Division Head a second time', function () {
    // Mirrors the real reported flow: DH -> HR Preparer tags Manila -> HR Head returns to
    // Requestor -> Requestor resubmits -> WithDivisionHead again, now for the DH Head.
    // Forcing a second tag here risks a different (wrong) call than the first one.
    $dhHead = User::factory()->dhHead()->create();
    $manila = PanRequest::factory()->status(PanStatus::WithDivisionHead)->manila()->create([
        'employee_id' => $this->employee->id,
        'department_id' => $this->department->id,
        'hr_preparer_id' => User::factory()->hrPreparer()->create()->id,
    ]);

    $this->actingAs($dhHead);
    Livewire::test(Show::class, ['pan' => $manila->reference])
        ->call('approve')
        ->assertRedirect(route('division.queue'));

    expect($manila->fresh()->status)->toBe(PanStatus::InPreparation)
        ->and($manila->fresh()->confidentiality_tag)->toBe(\App\Enums\ConfidentialityTag::Manila);
});

test('the Show page renders the request; the prepared extension appears once a form exists', function () {
    $bare = deptPan(PanStatus::WithDivisionHead);
    Livewire::test(Show::class, ['pan' => $bare->reference])
        ->assertSee($bare->reference)
        ->assertDontSee('PAN Details — prepared by HR');

    $prepared = deptPan(PanStatus::ForConfirmation);
    App\Models\PanForm::factory()->create(['pan_request_id' => $prepared->id]);

    Livewire::test(Show::class, ['pan' => $prepared->reference])
        ->assertSee('PAN Details — prepared by HR')
        ->assertSee('Basic Pay');
});

test('Show actions move the PAN and bounce back to the queue', function () {
    $pan = deptPan(PanStatus::WithDivisionHead);

    Livewire::test(Show::class, ['pan' => $pan->reference])
        ->call('approve')
        ->assertRedirect(route('division.queue'));

    expect($pan->fresh()->status)->toBe(PanStatus::AwaitingTag);
});
