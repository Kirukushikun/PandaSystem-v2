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

test('a PAN whose Division Head approval was proxy-approved pre-fills and requires remarks', function () {
    $pan = prepPan(PanStatus::InPreparation);
    $pan->returns()->create([
        'action' => 'proxy_approve_dh',
        'from_status' => PanStatus::WithDivisionHead,
        'to_status' => PanStatus::AwaitingTag,
        'reason' => 'The approval waiting period took too long',
        'returned_by' => User::factory()->proxyApprover()->create()->id,
    ]);

    $test = Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->assertSet('remarks', 'Division Head approval was proxy-approved — The approval waiting period took too long');

    $test->set('date_hired', '2023-02-02')
        ->set('doe_from', '2026-08-16')
        ->set('remarks', '')
        ->call('save')
        ->assertHasErrors(['remarks']);

    $test->set('remarks', 'Confirmed with HR Head — proceeding as-is.')
        ->call('save')
        ->assertHasNoErrors();

    expect($pan->fresh()->form->remarks)->toBe('Confirmed with HR Head — proceeding as-is.');
});

test('a normal PAN never requires remarks', function () {
    $pan = prepPan(PanStatus::InPreparation);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->assertSet('remarks', '')
        ->set('date_hired', '2023-02-02')
        ->set('doe_from', '2026-08-16')
        ->call('save')
        ->assertHasNoErrors();
});

test('a newly-created employee with no previous PAN gets inputtable "From" fields instead of stuck hyphens', function () {
    // HR concern: Section/Head/Job Level/Basic have nothing to carry over for a
    // fresh hire's first PAN — the "—" placeholder used to be the final answer,
    // with no way to record the employee's actual current values at all. Place
    // and Position are still seeded from the Employee record (never blank), but
    // are editable inputs too — populated, not locked — so print always has
    // real "From" values even on someone's very first PAN. (All "From" fields
    // are inputtable regardless of carry-over now — see the next test.)
    $pan = prepPan(PanStatus::InPreparation);

    $test = Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->assertSee('wire:model="fromValues.section"', false)
        ->assertSee('wire:model="fromValues.place"', false)
        ->assertSee('wire:model="fromValues.head"', false)
        ->assertSee('wire:model="fromValues.position"', false)
        ->assertSee('wire:model="fromValues.joblevel"', false)
        ->assertSee('wire:model="fromValues.basic"', false)
        // Place/Position arrive pre-filled from the Employee record, not blank.
        ->assertSet('fromValues.place', $pan->employee->farm->name)
        ->assertSet('fromValues.position', $pan->employee->position)
        ->assertSet('fromValues.section', '');

    $test->set('fromValues.section', 'Farrowing')
        ->set('fromValues.head', 'Juan Dela Cruz')
        ->set('fromValues.joblevel', 'JL1')
        ->set('fromValues.basic', '15,000.00')
        ->set('date_hired', '2026-08-01')
        ->set('doe_from', '2026-08-16')
        ->call('save')
        ->assertHasNoErrors();

    $rows = collect($pan->fresh()->form->action_reference);
    expect($rows->firstWhere('field', 'section')['from'])->toBe('Farrowing')
        ->and($rows->firstWhere('field', 'head')['from'])->toBe('Juan Dela Cruz')
        ->and($rows->firstWhere('field', 'joblevel')['from'])->toBe('JL1')
        ->and($rows->firstWhere('field', 'basic')['from'])->toBe('15,000.00')
        ->and($rows->firstWhere('field', 'place')['from'])->toBe($pan->employee->farm->name)
        ->and($rows->firstWhere('field', 'position')['from'])->toBe($pan->employee->position);
});

test('"From" stays editable even once a previous PAN exists, pre-filled with the carried-over value', function () {
    // Every "From" field is inputtable regardless of carry-over — HR can correct
    // a carried value (e.g. it was itself wrong) instead of being stuck with it.
    $previous = prepPan(PanStatus::Filed, ['filed_at' => now()->subMonths(2), 'approved_at' => now()->subMonths(2)]);
    PanForm::factory()->create([
        'pan_request_id' => $previous->id,
        'action_reference' => [
            ['field' => 'section', 'from' => '—', 'to' => 'Farrowing'],
            ['field' => 'place', 'from' => 'BFC', 'to' => 'BFC'],
            ['field' => 'head', 'from' => '—', 'to' => 'John Doe'],
            ['field' => 'position', 'from' => 'Farm Helper', 'to' => 'Farm Helper'],
            ['field' => 'joblevel', 'from' => '—', 'to' => 'JL2'],
            ['field' => 'basic', 'from' => '—', 'to' => '15,000.00'],
        ],
    ]);
    $pan = prepPan(PanStatus::InPreparation, ['previous_pan_id' => $previous->id]);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->assertSee('wire:model="fromValues.section"', false)
        ->assertSet('fromValues.section', 'Farrowing') // pre-filled with the carried value
        ->assertSet('fromValues.position', 'Farm Helper');
});

test('reopening for revision reloads a "To" value even when it was deliberately left equal to "From"', function () {
    // Real reported bug: Position (and Place) are the only fixed fields with a real,
    // non-blank "From" — a preparer who confirms it unchanged by retyping the same
    // text back into "To" would find hydrateForm() had silently blanked it back out
    // on reopen, reading as "my data didn't save" even though it saved correctly.
    $pan = prepPan(PanStatus::InPreparation);
    PanForm::factory()->create([
        'pan_request_id' => $pan->id,
        'action_reference' => [
            ['field' => 'section', 'from' => '—', 'to' => 'Farrowing'],
            ['field' => 'place', 'from' => 'BFC', 'to' => 'BFC'], // deliberately unchanged
            ['field' => 'head', 'from' => '—', 'to' => 'John Doe'],
            ['field' => 'position', 'from' => 'Farm Helper', 'to' => 'Farm Helper'], // deliberately unchanged
            ['field' => 'joblevel', 'from' => '—', 'to' => 'JL2'],
            ['field' => 'basic', 'from' => '—', 'to' => '15,000.00'],
        ],
    ]);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->assertSet('toValues.position', 'Farm Helper')
        ->assertSet('toValues.place', 'BFC')
        ->assertSet('toValues.section', 'Farrowing');
});

test('effectivity and date hired are required; Wage Orders also demand the wage number', function () {
    $pan = prepPan(PanStatus::InPreparation, ['action_type' => 'wage-order']);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->call('submit')
        ->assertHasErrors(['date_hired', 'doe_from', 'wage_no']);
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

test('submitting the prepare form with missing fields highlights them and toasts', function () {
    $pan = prepPan(PanStatus::InPreparation);

    $test = Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->set('date_hired', '')
        ->set('doe_from', '')
        ->call('submit')
        ->assertHasErrors(['date_hired', 'doe_from'])
        ->assertSeeHtml('border-color:var(--red)');

    expect(collect($test->effects['xjs'] ?? [])->pluck('expression')->implode(' '))
        ->toContain('showToast')
        ->toContain('highlighted field');
});

test('fixing a highlighted prepare-form field clears its own error immediately', function () {
    $pan = prepPan(PanStatus::InPreparation);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->set('date_hired', '')
        ->set('doe_from', '')
        ->call('submit')
        ->assertHasErrors(['date_hired', 'doe_from'])
        ->set('date_hired', '2026-01-15')
        ->assertHasNoErrors('date_hired')
        ->assertHasErrors('doe_from');
});

test('voiding from the form demands a reason and keeps the record', function () {
    $pan = prepPan(PanStatus::InPreparation);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->call('startReason', 'void')
        ->call('submitReason')
        ->assertHasErrors(['reason' => 'required']);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->call('startReason', 'void')
        ->set('reason', 'Duplicate request')
        ->call('submitReason')
        ->assertHasNoErrors();

    expect($pan->fresh()->status)->toBe(PanStatus::Voided)
        ->and($pan->returns()->sole()->action)->toBe('void');
});

test('the preparer can send a PAN back to the Requestor — e.g. missing documents', function () {
    $requestor = User::factory()->requestor()->create();
    $requestor->requestorDepartments()->attach($this->employee->department_id);
    $pan = prepPan(PanStatus::InPreparation, ['requested_by' => $requestor->id]);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->call('startReason', 'send_back_to_requestor')
        ->call('submitReason')
        ->assertHasErrors(['reason' => 'required']);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->call('startReason', 'send_back_to_requestor')
        ->set('reason', 'Missing supporting document(s)')
        ->call('submitReason')
        ->assertHasNoErrors();

    expect($pan->fresh()->status)->toBe(PanStatus::ReturnedToRequestor)
        ->and($pan->returns()->sole()->action)->toBe('send_back_to_requestor')
        ->and($requestor->notifications()->sole()->data['title'])->toBe('Returned to you');

    // Resubmitting from here re-enters at Division Head, same as the DH's own return.
    $this->actingAs($requestor);
    Livewire::test(App\Livewire\Requestor\Form::class, ['pan' => $pan->reference])
        ->set('justification', 'Documents attached as requested.')
        ->set('newAttachments', [UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf')])
        ->call('submit')
        ->assertHasNoErrors();
    expect($pan->fresh()->status)->toBe(PanStatus::WithDivisionHead);
});

test('sending back to the Requestor is only available from InPreparation/ReturnedToPreparer, and only to who prepares it', function () {
    $forConfirmation = prepPan(PanStatus::ForConfirmation);
    Livewire::test(PrepareForm::class, ['pan' => $forConfirmation->reference])->assertForbidden();

    $returnedToPreparer = prepPan(PanStatus::ReturnedToPreparer);
    Livewire::test(PrepareForm::class, ['pan' => $returnedToPreparer->reference])
        ->call('startReason', 'send_back_to_requestor')
        ->set('reason', 'Missing supporting document(s)')
        ->call('submitReason')
        ->assertHasNoErrors();
    expect($returnedToPreparer->fresh()->status)->toBe(PanStatus::ReturnedToRequestor);
});

test('the preparer can upload documents on the Requestor\'s behalf, and remove them', function () {
    Storage::fake();
    $pan = prepPan(PanStatus::InPreparation);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->set('newAttachments', [UploadedFile::fake()->create('sent_via_viber.pdf', 150, 'application/pdf')])
        ->call('uploadAttachments')
        ->assertHasNoErrors();

    $pan->refresh();
    expect($pan->attachments)->toHaveCount(1);
    $attachment = $pan->attachments->sole();
    Storage::assertExists($attachment->path);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->call('removeAttachment', $attachment->id);

    Storage::assertMissing($attachment->path);
    expect($pan->attachments()->count())->toBe(0);
});

test('the preparer cannot exceed 3 attachments total', function () {
    Storage::fake();
    $pan = prepPan(PanStatus::InPreparation);
    \App\Models\PanAttachment::factory()->count(2)->create(['pan_request_id' => $pan->id]);

    $pdf = fn ($name) => UploadedFile::fake()->create($name, 100, 'application/pdf');

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->set('newAttachments', [$pdf('a.pdf'), $pdf('b.pdf')])
        ->call('uploadAttachments')
        ->assertHasErrors('newAttachments');
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

test('a first-time PAN defaults to Regular, not Probationary, unless the action is Regularization', function () {
    // HR-reported bug: a brand-new employee's very first PAN unconditionally
    // defaulted to Probationary regardless of action type — wrong for anything
    // that isn't actually a Regularization (e.g. Salary Alignment).
    $salaryAlignment = prepPan(PanStatus::InPreparation, ['action_type' => 'salary-alignment']);
    Livewire::test(PrepareForm::class, ['pan' => $salaryAlignment->reference])
        ->assertSet('employment_status', EmploymentStatus::Regular->value);

    $regularization = prepPan(PanStatus::InPreparation, ['action_type' => 'regularization']);
    Livewire::test(PrepareForm::class, ['pan' => $regularization->reference])
        ->assertSet('employment_status', EmploymentStatus::Probationary->value);
});

test('employment status is an editable dropdown — HR can override the default and it persists', function () {
    $pan = prepPan(PanStatus::InPreparation, ['action_type' => 'salary-alignment']);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->assertSet('employment_status', EmploymentStatus::Regular->value)
        ->set('employment_status', EmploymentStatus::Casual->value)
        ->set('date_hired', '2026-08-01')
        ->set('doe_from', '2026-08-16')
        ->call('save')
        ->assertHasNoErrors();

    expect($pan->fresh()->form->employment_status)->toBe(EmploymentStatus::Casual);
});

test('a newer Approved-but-unfiled PAN outranks an older Filed one for carry-over', function () {
    // Reproduces a real production case: two tranches of the same wage order — the
    // 1st is Filed and closed out, the 2nd got Final Approver sign-off months later
    // but was never served/filed. The 2nd tranche's values are the real current
    // ones (Approved has no reject/void path) and must win.
    $filed = prepPan(PanStatus::Filed, ['filed_at' => now()->subMonths(4), 'approved_at' => now()->subMonths(4)]);
    PanForm::factory()->create([
        'pan_request_id' => $filed->id,
        'action_reference' => [['field' => 'basic', 'from' => '15,816.67', 'to' => '16,425.00']],
    ]);

    $approved = prepPan(PanStatus::Approved, ['approved_at' => now()->subDays(1)]);
    PanForm::factory()->create([
        'pan_request_id' => $approved->id,
        'action_reference' => [['field' => 'basic', 'from' => '16,425.00', 'to' => '17,337.50']],
    ]);

    $pan = prepPan(PanStatus::InPreparation);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->assertSet('fromValues.basic', '17,337.50')
        ->assertSee($approved->reference)
        ->assertDontSee($filed->reference);
});

test('allowance rows carry over from the previous PAN, not just the six fixed fields', function () {
    // HR-reported bug: fromValuesFor() only ever seeded section/place/head/position/
    // joblevel/basic — any Communication/Meal/etc. Allowance row silently never
    // carried over, so HR had to retype every recurring allowance on every PAN.
    $previous = prepPan(PanStatus::Filed, ['filed_at' => now()->subMonths(2), 'approved_at' => now()->subMonths(2)]);
    PanForm::factory()->create([
        'pan_request_id' => $previous->id,
        'action_reference' => [
            ['field' => 'basic', 'from' => '16,425.00', 'to' => '16,425.00'],
            ['field' => 'Communication Allowance', 'from' => '200.00', 'to' => '200.00'],
        ],
    ]);

    $pan = prepPan(PanStatus::InPreparation);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->assertSet('allowances', [
            ['label' => 'Communication Allowance', 'from' => '200.00', 'to' => ''],
        ]);
});

test('a freshly-added allowance row has an inputtable "From" — nothing to carry it over from', function () {
    $pan = prepPan(PanStatus::InPreparation);

    $test = Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->call('addAllowance')
        ->assertSee('wire:model="allowances.0.from"', false)
        ->set('allowances.0.from', '150.00')
        ->set('allowances.0.to', '200.00')
        ->set('date_hired', '2026-08-01')
        ->set('doe_from', '2026-08-16')
        ->call('save')
        ->assertHasNoErrors();

    $row = collect($pan->fresh()->form->action_reference)->firstWhere('field', 'Communication Allowance');
    expect($row['from'])->toBe('150.00')
        ->and($row['to'])->toBe('200.00');
});

test('a carried-over allowance row\'s "From" is editable too, pre-filled with the carried value', function () {
    $previous = prepPan(PanStatus::Filed, ['filed_at' => now()->subMonths(2), 'approved_at' => now()->subMonths(2)]);
    PanForm::factory()->create([
        'pan_request_id' => $previous->id,
        'action_reference' => [
            ['field' => 'Communication Allowance', 'from' => '200.00', 'to' => '200.00'],
        ],
    ]);
    $pan = prepPan(PanStatus::InPreparation);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->assertSee('wire:model="allowances.0.from"', false)
        ->assertSet('allowances.0.from', '200.00');
});

test('an Unserved PAN is never used for carry-over, even if it is the most recent', function () {
    // PanWorkflow has no transition out of Unserved — nothing ever confirms the
    // change actually reached the employee, so it must never seed the next PAN.
    $filed = prepPan(PanStatus::Filed, ['filed_at' => now()->subMonths(4), 'approved_at' => now()->subMonths(4)]);
    PanForm::factory()->create([
        'pan_request_id' => $filed->id,
        'action_reference' => [['field' => 'basic', 'from' => '15,816.67', 'to' => '16,425.00']],
    ]);

    $unserved = prepPan(PanStatus::Unserved, ['approved_at' => now()->subDays(1)]);
    PanForm::factory()->create([
        'pan_request_id' => $unserved->id,
        'action_reference' => [['field' => 'basic', 'from' => '16,425.00', 'to' => '99,999.00']],
    ]);

    $pan = prepPan(PanStatus::InPreparation);

    Livewire::test(PrepareForm::class, ['pan' => $pan->reference])
        ->assertSet('fromValues.basic', '16,425.00')
        ->assertSee($filed->reference)
        ->assertDontSee($unserved->reference);
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

test('PAN history / "Start Follow-up PAN" route resolves by employee_no, not id', function () {
    // Regression: EmployeeHistory has a public `Employee $employee` property, and
    // the route wildcard was also named {employee} — Livewire's implicit
    // route-model-binding-by-property-name treated the URL segment as a numeric
    // id before mount() ever ran, 404ing on any real employee_no like "EMP-1001".
    $employee = Employee::factory()->create();
    PanRequest::factory()->status(PanStatus::Filed)->create(['employee_id' => $employee->id]);

    $this->actingAs($this->preparer)
        ->get('/employees/'.$employee->employee_no.'/pans')
        ->assertOk()
        ->assertSee($employee->name);
});

/*
|--------------------------------------------------------------------------
| Bounce-back clarity — InPreparation is a shared landing spot for a DH
| dispute and a Final Approver rejection; the queue must say which.
|--------------------------------------------------------------------------
*/

test('the queue tells apart a fresh InPreparation PAN from a disputed one from a rejected one', function () {
    $fresh = prepPan(PanStatus::InPreparation, ['reference' => 'PAN-2026-70001']);

    $disputed = prepPan(PanStatus::InPreparation, ['reference' => 'PAN-2026-70002']);
    $disputed->returns()->create([
        'action' => 'dispute', 'from_status' => PanStatus::ForConfirmation, 'to_status' => PanStatus::InPreparation,
        'reason' => 'Wrong basic pay', 'returned_by' => $this->preparer->id,
    ]);

    $rejected = prepPan(PanStatus::InPreparation, ['reference' => 'PAN-2026-70003']);
    $rejected->returns()->create([
        'action' => 'reject_final', 'from_status' => PanStatus::ForFinalApproval, 'to_status' => PanStatus::InPreparation,
        'reason' => 'Missing signature', 'returned_by' => $this->preparer->id,
    ]);

    Livewire::test(Queue::class)
        ->assertSeeHtml('HR Preparation') // the fresh one keeps the plain pill
        ->assertSee('Disputed — by Division Head')
        ->assertSee('Rejected — by Final Approver');
});
