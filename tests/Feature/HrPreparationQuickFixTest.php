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
| Temporary quick-fix: filling a still-blank "From" value from the View page,
| for PANs that got stuck with a dash before the no-previous-PAN default fix.
| Deliberately narrow: only action_reference.*.from — no allowance rows, no
| pan_returns entry, no touching "To" or any already-filled "From".
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
        ->assertSee('Missing "From" value(s)', false)
        ->assertSee('Fill in missing values');
});

test('a PAN with no blank "From" fields shows nothing', function () {
    $preparer = User::factory()->hrPreparer()->create();
    $pan = preparedPan([
        ['field' => 'section', 'from' => 'Farrowing', 'to' => 'Nursery'],
    ]);

    Livewire::actingAs($preparer)->test(Show::class, ['pan' => $pan->reference])
        ->assertDontSee('Missing "From" value(s)', false);
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

test('a hand-crafted attempt to overwrite an already-filled "From" is ignored', function () {
    $preparer = User::factory()->hrPreparer()->create();
    $pan = preparedPan([
        ['field' => 'section', 'from' => '—', 'to' => 'Farrowing'],
        ['field' => 'position', 'from' => 'Farm Helper', 'to' => 'Farm Helper'],
    ]);

    Livewire::actingAs($preparer)->test(Show::class, ['pan' => $pan->reference])
        ->call('startQuickFix')
        ->set('emptyFromValues.position', 'Tampered Value') // "position" was never blank
        ->call('saveQuickFix')
        ->assertHasNoErrors();

    expect(collect($pan->fresh()->form->action_reference)->firstWhere('field', 'position')['from'])
        ->toBe('Farm Helper');
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

    expect($preparer->can('patchEmptyFromValues', $pan))->toBeFalse()
        ->and($hrHead->can('patchEmptyFromValues', $pan))->toBeTrue();

    Livewire::actingAs($hrHead)->test(Show::class, ['pan' => $pan->reference])
        ->assertSee('Missing "From" value(s)', false)
        ->call('startQuickFix')
        ->assertHasNoErrors();
});

test('a requestor cannot reach the quick-fix action at all', function () {
    $requestor = User::factory()->requestor()->create();
    $pan = preparedPan([['field' => 'section', 'from' => '—', 'to' => 'Farrowing']]);

    $this->actingAs($requestor)->get('/preparation/'.$pan->reference)->assertForbidden();
});
