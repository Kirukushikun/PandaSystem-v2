<?php

use App\Enums\ActionType;
use App\Enums\EmploymentStatus;
use App\Enums\PanOrigin;
use App\Enums\PanStatus;
use App\Models\Employee;
use App\Models\PanForm;
use App\Models\PanRequest;
use App\Models\PanReturn;
use App\Services\PanReferenceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Reference generator
|--------------------------------------------------------------------------
*/

test('references are year-scoped, five digits, and sequential', function () {
    $generator = new PanReferenceGenerator;
    $year = now()->year;

    expect($generator->next())->toBe("PAN-{$year}-00001");

    PanRequest::factory()->create(['reference' => "PAN-{$year}-00001"]);
    expect($generator->next())->toBe("PAN-{$year}-00002");

    PanRequest::factory()->create(['reference' => "PAN-{$year}-00347"]);
    expect($generator->next())->toBe("PAN-{$year}-00348");
});

test('soft-deleted PANs still hold their reference number (no reuse)', function () {
    $year = now()->year;
    PanRequest::factory()->create(['reference' => "PAN-{$year}-00005"])->delete();

    expect((new PanReferenceGenerator)->next())->toBe("PAN-{$year}-00006");
});

test('last year\'s references do not leak into this year\'s sequence', function () {
    $lastYear = now()->year - 1;
    PanRequest::factory()->create(['reference' => "PAN-{$lastYear}-00944"]);

    expect((new PanReferenceGenerator)->next())->toBe('PAN-'.now()->year.'-00001');
});

/*
|--------------------------------------------------------------------------
| Ongoing scope — the employee-delete guard
|--------------------------------------------------------------------------
*/

test('drafts and closed-out PANs do not block employee deletion; everything in-flight does', function () {
    $employee = Employee::factory()->create();

    // drafts don't block
    PanRequest::factory()->for($employee)->status(PanStatus::Draft)->create();
    expect($employee->hasOngoingPan())->toBeFalse();

    // terminal states don't block
    foreach ([PanStatus::Filed, PanStatus::Withdrawn, PanStatus::Voided, PanStatus::Unserved] as $closed) {
        PanRequest::factory()->for($employee)->status($closed)->create();
    }
    expect($employee->fresh()->hasOngoingPan())->toBeFalse();

    // anything in-flight blocks
    $inFlight = PanRequest::factory()->for($employee)->status(PanStatus::WithDivisionHead)->create();
    expect($employee->fresh()->hasOngoingPan())->toBeTrue();

    $inFlight->update(['status' => PanStatus::Served]); // served but not yet filed still blocks
    expect($employee->fresh()->hasOngoingPan())->toBeTrue();
});

test('the ongoing scope matches PanStatus::isOngoing() case by case', function () {
    $employee = Employee::factory()->create();

    foreach (PanStatus::cases() as $status) {
        PanRequest::factory()->for($employee)->status($status)->create();
    }

    $ongoingCount = collect(PanStatus::cases())->filter(fn ($s) => $s->isOngoing())->count();

    expect(PanRequest::ongoing()->count())->toBe($ongoingCount);
});

/*
|--------------------------------------------------------------------------
| The chain, the form, the history
|--------------------------------------------------------------------------
*/

test('previous_pan_id chains PANs both directions', function () {
    $first = PanRequest::factory()->status(PanStatus::Filed)->create();
    $followUp = PanRequest::factory()
        ->for($first->employee)
        ->create(['previous_pan_id' => $first->id]);

    expect($followUp->previousPan->is($first))->toBeTrue()
        ->and($first->followUps->first()->is($followUp))->toBeTrue();
});

test('enum casts round-trip on the PAN request', function () {
    $pan = PanRequest::factory()->manila()->hrOriginated()->create([
        'action_type' => ActionType::WageOrder,
    ]);

    $fresh = $pan->fresh();
    expect($fresh->status)->toBe(PanStatus::AwaitingTag)
        ->and($fresh->action_type)->toBe(ActionType::WageOrder)
        ->and($fresh->origin)->toBe(PanOrigin::Hr)
        ->and($fresh->requested_by)->toBeNull(); // HR-originated: no requestor
});

test('the prepared form stores the ordered action_reference JSON the print view consumes', function () {
    $form = PanForm::factory()->create();

    $rows = $form->fresh()->action_reference;
    expect(array_column($rows, 'field'))
        ->toBe(['section', 'place', 'head', 'position', 'joblevel', 'basic'])
        ->and($form->fresh()->employment_status)->toBe(EmploymentStatus::Probationary)
        ->and($form->panRequest->form->is($form))->toBeTrue();
});

test('a return records the workflow action, both statuses, and the mandatory reason', function () {
    $return = PanReturn::factory()->create();

    $fresh = $return->fresh();
    expect($fresh->from_status)->toBe(PanStatus::WithDivisionHead)
        ->and($fresh->to_status)->toBe(PanStatus::ReturnedToRequestor)
        ->and($fresh->reason)->not->toBeEmpty()
        ->and($fresh->panRequest->returns->first()->is($return))->toBeTrue();
});
