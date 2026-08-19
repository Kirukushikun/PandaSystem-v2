<?php

use App\Enums\ConfidentialityTag;
use App\Enums\PanStatus;
use App\Livewire\HrPreparation\Show;
use App\Models\Employee;
use App\Models\PanForm;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Temporary quick-fix: filling a still-blank "From" value and/or a missing
| Division Head from the View page, for PANs that predate the fixes made to
| the live workflow. Deliberately narrow: only action_reference.*.from and
| division_head_id — no allowance rows, no pan_returns entry, no touching
| "To" or any already-filled "From"/division_head_id.
|--------------------------------------------------------------------------
*/

function preparedPan(array $referenceRows, array $extra = []): PanRequest
{
    $pan = PanRequest::factory()->status(PanStatus::ForConfirmation)->create([
        'employee_id' => Employee::factory()->create()->id,
        ...$extra,
    ]);
    PanForm::factory()->create([
        'pan_request_id' => $pan->id,
        'action_reference' => $referenceRows,
    ]);

    return $pan->fresh();
}

test('a PAN with a blank "From" field offers the quick-fix panel to its preparer population', function () {
    $preparer = User::factory()->hrPreparer()->create();
    $pan = preparedPan([
        ['field' => 'section', 'from' => '—', 'to' => 'Farrowing'],
        ['field' => 'position', 'from' => 'Farm Helper', 'to' => 'Farm Helper'],
    ]);

    Livewire::actingAs($preparer)->test(Show::class, ['pan' => $pan->reference])
        ->assertSee('Missing print detail(s)', false)
        ->assertSee('Fill in missing values');
});

test('a PAN with no blank "From" fields shows nothing', function () {
    $preparer = User::factory()->hrPreparer()->create();
    $pan = preparedPan([
        ['field' => 'section', 'from' => 'Farrowing', 'to' => 'Nursery'],
    ]);

    Livewire::actingAs($preparer)->test(Show::class, ['pan' => $pan->reference])
        ->assertDontSee('Missing print detail(s)', false);
});

test('saving fills only the blank field, leaves everything else untouched, and logs nothing', function () {
    $preparer = User::factory()->hrPreparer()->create();
    $pan = preparedPan([
        ['field' => 'section', 'from' => '—', 'to' => 'Farrowing'],
        ['field' => 'head', 'from' => '—', 'to' => 'John Doe'],
        ['field' => 'position', 'from' => 'Farm Helper', 'to' => 'Farm Helper'],
    ]);

    Livewire::actingAs($preparer)->test(Show::class, ['pan' => $pan->reference])
        ->call('startQuickFix')
        ->set('emptyFromValues.section', 'Nursery')
        // "head" left blank on purpose — partial fill must be allowed.
        ->call('saveQuickFix')
        ->assertHasNoErrors();

    $rows = collect($pan->fresh()->form->action_reference);
    expect($rows->firstWhere('field', 'section')['from'])->toBe('Nursery')
        ->and($rows->firstWhere('field', 'section')['to'])->toBe('Farrowing') // "to" untouched
        ->and($rows->firstWhere('field', 'head')['from'])->toBe('—') // left blank, stays blank
        ->and($rows->firstWhere('field', 'position')['from'])->toBe('Farm Helper') // already filled, untouched
        ->and($pan->returns()->count())->toBe(0); // no audit log entry
});

test('a hand-crafted attempt to overwrite an already-filled non-Place/Position "From" is ignored', function () {
    // Place/Position are deliberately always-overwritable (see the next test) — this
    // one covers every other fixed field, which stays blank-only.
    $preparer = User::factory()->hrPreparer()->create();
    $pan = preparedPan([
        ['field' => 'section', 'from' => '—', 'to' => 'Farrowing'],
        ['field' => 'joblevel', 'from' => 'JL2', 'to' => 'JL2'],
    ]);

    Livewire::actingAs($preparer)->test(Show::class, ['pan' => $pan->reference])
        ->call('startQuickFix')
        ->set('emptyFromValues.joblevel', 'Tampered Value') // "joblevel" was never blank
        ->call('saveQuickFix')
        ->assertHasNoErrors();

    expect(collect($pan->fresh()->form->action_reference)->firstWhere('field', 'joblevel')['from'])
        ->toBe('JL2');
});

test('Place of Assignment and Position are always offered, pre-filled, and can be corrected even though never blank', function () {
    $preparer = User::factory()->hrPreparer()->create();
    $pan = preparedPan([
        ['field' => 'place', 'from' => 'BFC', 'to' => 'BFC'],
        ['field' => 'position', 'from' => 'Farm Helper', 'to' => 'Farm Helper'],
    ]);

    Livewire::actingAs($preparer)->test(Show::class, ['pan' => $pan->reference])
        ->assertSee('Missing print detail(s)', false) // shows even though nothing is blank
        ->call('startQuickFix')
        ->assertSet('emptyFromValues.place', 'BFC') // pre-filled with the current value
        ->assertSet('emptyFromValues.position', 'Farm Helper')
        ->set('emptyFromValues.place', 'RH')
        ->set('emptyFromValues.position', 'Farm Operator')
        ->call('saveQuickFix')
        ->assertHasNoErrors();

    $rows = collect($pan->fresh()->form->action_reference);
    expect($rows->firstWhere('field', 'place')['from'])->toBe('RH')
        ->and($rows->firstWhere('field', 'place')['to'])->toBe('BFC') // "to" untouched
        ->and($rows->firstWhere('field', 'position')['from'])->toBe('Farm Operator');
});

test('a Manila PAN is restricted to HR Head, same as normal preparation', function () {
    // A plain (non-head) preparer can't even open a Manila PAN's View page at all
    // (the existing view() policy) — assert the underlying ability directly rather
    // than rendering a component the account isn't allowed to view in the first place.
    $preparer = User::factory()->hrPreparer()->create();
    $hrHead = User::factory()->hrHead()->create();
    $pan = preparedPan(
        [['field' => 'section', 'from' => '—', 'to' => 'Farrowing']],
        ['confidentiality_tag' => ConfidentialityTag::Manila]
    );

    expect($preparer->can('patchMissingPrintDetails', $pan))->toBeFalse()
        ->and($hrHead->can('patchMissingPrintDetails', $pan))->toBeTrue();

    Livewire::actingAs($hrHead)->test(Show::class, ['pan' => $pan->reference])
        ->assertSee('Missing print detail(s)', false)
        ->call('startQuickFix')
        ->assertHasNoErrors();
});

test('a PAN past confirmation with no division head offers a dropdown of the department\'s heads', function () {
    $department = App\Models\Department::factory()->create();
    $head = User::factory()->divisionHead()->create();
    $head->headedDepartments()->attach($department);
    $preparer = User::factory()->hrPreparer()->create();

    $pan = preparedPan(
        [['field' => 'section', 'from' => 'Farrowing', 'to' => 'Nursery']],
        ['status' => PanStatus::ForHrApproval, 'department_id' => $department->id, 'division_head_id' => null]
    );

    Livewire::actingAs($preparer)->test(Show::class, ['pan' => $pan->reference])
        ->assertSee('Missing print detail(s)', false)
        ->call('startQuickFix')
        ->assertSee('Recommended By')
        ->assertSee($head->name)
        ->set('selectedDivisionHead', (string) $head->id)
        ->call('saveQuickFix')
        ->assertHasNoErrors();

    expect($pan->fresh()->division_head_id)->toBe($head->id);
});

test('a PAN already carrying a division head never offers the dropdown, even past confirmation', function () {
    $department = App\Models\Department::factory()->create();
    $head = User::factory()->divisionHead()->create();
    $head->headedDepartments()->attach($department);
    $preparer = User::factory()->hrPreparer()->create();

    $pan = preparedPan(
        [['field' => 'section', 'from' => 'Farrowing', 'to' => 'Nursery']],
        ['status' => PanStatus::ForHrApproval, 'department_id' => $department->id, 'division_head_id' => $head->id]
    );

    Livewire::actingAs($preparer)->test(Show::class, ['pan' => $pan->reference])
        ->assertDontSee('Missing print detail(s)', false);
});

test('a hand-crafted attempt to overwrite an already-recorded division head is ignored', function () {
    $department = App\Models\Department::factory()->create();
    $head = User::factory()->divisionHead()->create();
    $impersonator = User::factory()->divisionHead()->create();
    $head->headedDepartments()->attach($department);
    $preparer = User::factory()->hrPreparer()->create();

    $pan = preparedPan(
        [['field' => 'section', 'from' => '—', 'to' => 'Nursery']], // keep the panel reachable via a blank From
        ['status' => PanStatus::ForHrApproval, 'department_id' => $department->id, 'division_head_id' => $head->id]
    );

    Livewire::actingAs($preparer)->test(Show::class, ['pan' => $pan->reference])
        ->call('startQuickFix')
        ->set('selectedDivisionHead', (string) $impersonator->id)
        ->call('saveQuickFix')
        ->assertHasNoErrors();

    expect($pan->fresh()->division_head_id)->toBe($head->id);
});

test('a PAN still awaiting confirmation never offers the dropdown — null is still legitimate', function () {
    $preparer = User::factory()->hrPreparer()->create();
    $pan = preparedPan([['field' => 'section', 'from' => 'Farrowing', 'to' => 'Nursery']]); // default status: ForConfirmation

    Livewire::actingAs($preparer)->test(Show::class, ['pan' => $pan->reference])
        ->assertDontSee('Missing print detail(s)', false);
});

test('a requestor cannot reach the quick-fix action at all', function () {
    $requestor = User::factory()->requestor()->create();
    $pan = preparedPan([['field' => 'section', 'from' => '—', 'to' => 'Farrowing']]);

    $this->actingAs($requestor)->get('/preparation/'.$pan->reference)->assertForbidden();
});
