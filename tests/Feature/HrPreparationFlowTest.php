<?php

use App\Enums\ConfidentialityTag;
use App\Enums\EmploymentStatus;
use App\Enums\PanOrigin;
use App\Enums\PanStatus;
use App\Livewire\HrPreparation\Employees;
use App\Livewire\HrPreparation\PrepareForm;
use App\Livewire\HrPreparation\Queue;
use App\Models\Employee;
use App\Models\PanForm;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake();

    $this->preparer = User::factory()->hrPreparer()->create();
    $this->employee = Employee::factory()->create();

    $this->actingAs($this->preparer);
});

function prepPan(PanStatus $status, array $extra = []): PanRequest
{
    return PanRequest::factory()->status($status)->create([
        'employee_id' => test()->employee->id,
        'department_id' => test()->employee->department_id,
        'action_type' => 'promotion', // deterministic: no wage-no / leave-credits side rules
        ...$extra,
    ]);
}

/*
|--------------------------------------------------------------------------
| Tagging — the four tag/role outcomes
|--------------------------------------------------------------------------
*/

test('tagging Tarlac unlocks preparation and records the preparer', function () {
    $pan = prepPan(PanStatus::AwaitingTag);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->set('tag', 'tarlac')
        ->call('applyTag')
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $pan->refresh();
    expect($pan->status)->toBe(PanStatus::InPreparation)
        ->and($pan->confidentiality_tag)->toBe(ConfidentialityTag::Tarlac)
        ->and($pan->hr_preparer_id)->toBe($this->preparer->id);
});

test('a normal preparer who tags Manila is sent back to the queue and loses the record', function () {
    $pan = prepPan(PanStatus::AwaitingTag);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->set('tag', 'manila')
        ->call('applyTag')
        ->assertRedirect(route('preparation.queue'));

    $pan->refresh();
    expect($pan->confidentiality_tag)->toBe(ConfidentialityTag::Manila)
        ->and($this->preparer->can('view', $pan))->toBeFalse()
        ->and($this->preparer->can('prepare', $pan))->toBeFalse();

    // the permanent lock, over HTTP too
    $this->get('/preparation/'.$pan->reference)->assertForbidden();
});

test('an HR Head who tags Manila keeps working on it', function () {
    $this->actingAs(User::factory()->hrHead()->create());
    $pan = prepPan(PanStatus::AwaitingTag);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->set('tag', 'manila')
        ->call('applyTag')
        ->assertNoRedirect();

    expect($pan->fresh()->status)->toBe(PanStatus::InPreparation);
});

test('an untagged choice is rejected; statuses past preparation cannot open the form at all', function () {
    $pan = prepPan(PanStatus::AwaitingTag);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->call('applyTag')
        ->assertHasErrors(['tag' => 'required']);

    $confirming = prepPan(PanStatus::ForConfirmation);
    Livewire::test(PrepareForm::class, ['pan' => $confirming->reference])
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| The preparation form — saving, submitting, voiding
|--------------------------------------------------------------------------
*/

test('saving writes the pan_form; blank "To" fields carry the "From" value through', function () {
    $pan = prepPan(PanStatus::InPreparation, ['action_type' => 'promotion']);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->set('date_hired', '2023-02-02')
        ->set('doe_from', '2026-08-16')
        ->set('toValues.position', 'Senior Farm Technician')
        ->call('save')
        ->assertHasNoErrors();

    $form = $pan->fresh()->form;
    $rows = collect($form->action_reference);

    expect($form->prepared_by)->toBe($this->preparer->id)
        ->and($rows->firstWhere('field', 'position')['to'])->toBe('Senior Farm Technician')
        ->and($rows->firstWhere('field', 'position')['from'])->toBe($this->employee->position)
        // untouched row: To mirrors From (no change)
        ->and($rows->firstWhere('field', 'place')['to'])->toBe($rows->firstWhere('field', 'place')['from']);
});

test('effectivity and date hired are required; Wage Orders also demand the wage number', function () {
    $pan = prepPan(PanStatus::InPreparation, ['action_type' => 'wage-order']);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->call('submit')
        ->assertHasErrors(['date_hired' => 'required', 'doe_from' => 'required', 'wage_no' => 'required']);
});

test('Leave Credits row appears only for Regularization', function () {
    $regular = prepPan(PanStatus::InPreparation, ['action_type' => 'regularization']);
    Livewire::test(PrepareForm::class, ['pan' => $regular->reference])
        ->assertSee('Leave Credits');

    $promotion = prepPan(PanStatus::InPreparation, ['action_type' => 'promotion']);
    Livewire::test(PrepareForm::class, ['pan' => $promotion->reference])
        ->assertDontSee('Leave Credits');
});

test('submitting sends the PAN for Division Head confirmation', function () {
    $pan = prepPan(PanStatus::InPreparation);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->set('date_hired', '2023-02-02')
        ->set('doe_from', '2026-08-16')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('preparation.queue'));

    expect($pan->fresh()->status)->toBe(PanStatus::ForConfirmation);
});

test('a returned PAN resubmits straight to the HR Approver — no second confirmation', function () {
    $pan = prepPan(PanStatus::ReturnedToPreparer);
    PanForm::factory()->create(['pan_request_id' => $pan->id]);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->call('submit')
        ->assertHasNoErrors();

    expect($pan->fresh()->status)->toBe(PanStatus::ForHrApproval);
});

test('voiding from the form demands a reason and keeps the record', function () {
    $pan = prepPan(PanStatus::InPreparation);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->call('void')
        ->assertHasErrors(['reason' => 'required']);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->set('reason', 'Duplicate request')
        ->call('void')
        ->assertHasNoErrors();

    expect($pan->fresh()->status)->toBe(PanStatus::Voided)
        ->and($pan->returns()->sole()->action)->toBe('void');
});

/*
|--------------------------------------------------------------------------
| Carry-over — the previous_pan_id chain
|--------------------------------------------------------------------------
*/

test('the last filed PAN seeds the From values, links the chain, and locks employment status', function () {
    $previous = prepPan(PanStatus::Filed, ['filed_at' => now()->subMonths(3)]);
    PanForm::factory()->create([
        'pan_request_id' => $previous->id,
        'employment_status' => EmploymentStatus::Regular,
        'action_reference' => [
            ['field' => 'section', 'from' => 'Line A', 'to' => 'Line B'],
            ['field' => 'position', 'from' => 'Helper', 'to' => 'Operator'],
            ['field' => 'basic', 'from' => '17,000.00', 'to' => '19,500.00'],
        ],
    ]);

    $pan = prepPan(PanStatus::InPreparation);

    $test = Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->assertSet('fromValues.section', 'Line B')
        ->assertSet('fromValues.position', 'Operator')
        ->assertSet('fromValues.basic', '19,500.00')
        ->assertSee($previous->reference); // the "Pre-generated from" link

    $test->set('date_hired', '2021-11-05')
        ->set('doe_from', '2026-08-01')
        ->call('save');

    $pan->refresh();
    expect($pan->previous_pan_id)->toBe($previous->id)
        ->and($pan->form->employment_status)->toBe(EmploymentStatus::Regular);
});

/*
|--------------------------------------------------------------------------
| Queue — scoping and closeout actions
|--------------------------------------------------------------------------
*/

test('the queue hides Manila rows from ordinary preparers but shows them to HR Heads', function () {
    prepPan(PanStatus::InPreparation, ['reference' => 'PAN-2026-91101']);
    prepPan(PanStatus::InPreparation, ['reference' => 'PAN-2026-91102', 'confidentiality_tag' => ConfidentialityTag::Manila]);
    prepPan(PanStatus::WithDivisionHead, ['reference' => 'PAN-2026-91103']); // not yet division-approved

    Livewire::test(Queue::class)
        ->assertSee('PAN-2026-91101')
        ->assertDontSee('PAN-2026-91102')
        ->assertDontSee('PAN-2026-91103');

    $this->actingAs(User::factory()->hrHead()->create());
    Livewire::test(Queue::class)->assertSee('PAN-2026-91102');
});

test('approved PANs are served then filed; unserved needs a reason', function () {
    $pan = prepPan(PanStatus::Approved);

    Livewire::test(Queue::class)->call('markServed', $pan->id);
    expect($pan->fresh()->status)->toBe(PanStatus::Served);

    Livewire::test(Queue::class)->call('filePan', $pan->id);
    $pan->refresh();
    expect($pan->status)->toBe(PanStatus::Filed)
        ->and($pan->filed_at)->not->toBeNull();

    $unserved = prepPan(PanStatus::Approved);
    Livewire::test(Queue::class)
        ->call('startReason', $unserved->id, 'mark_unserved')
        ->set('reason', 'AWOL')
        ->call('submitReason')
        ->assertHasNoErrors();

    expect($unserved->fresh()->status)->toBe(PanStatus::Unserved)
        ->and($unserved->returns()->sole()->action)->toBe('mark_unserved');
});

test('an ordinary preparer cannot serve or void a Manila PAN', function () {
    $manila = prepPan(PanStatus::Approved, ['confidentiality_tag' => ConfidentialityTag::Manila]);

    Livewire::test(Queue::class)
        ->call('markServed', $manila->id)
        ->assertForbidden();

    expect($manila->fresh()->status)->toBe(PanStatus::Approved);
});

/*
|--------------------------------------------------------------------------
| Update PAN — HR-originated cycles
|--------------------------------------------------------------------------
*/

test('Update PAN starts a new cycle directly at Awaiting Tag with origin hr', function () {
    Livewire::test(Employees::class)
        ->call('startUpdate', $this->employee->id)
        ->set('updateAction', 'wage-order')
        ->set('updateAttachments', [UploadedFile::fake()->create('wage_order_issuance.pdf', 200, 'application/pdf')])
        ->call('createHrPan')
        ->assertHasNoErrors();

    $pan = PanRequest::sole();
    expect($pan->origin)->toBe(PanOrigin::Hr)
        ->and($pan->status)->toBe(PanStatus::AwaitingTag)
        ->and($pan->requested_by)->toBeNull()
        ->and($pan->reference)->toBe('PAN-'.now()->year.'-00001')
        ->and($pan->employee_id)->toBe($this->employee->id)
        ->and($pan->attachments)->toHaveCount(1);
    Storage::assertExists($pan->attachments->first()->path);
});

test('Update PAN requires the action type and the PDF', function () {
    Livewire::test(Employees::class)
        ->call('startUpdate', $this->employee->id)
        ->call('createHrPan')
        ->assertHasErrors(['updateAction' => 'required', 'updateAttachments']);

    expect(PanRequest::count())->toBe(0);
});
