<?php

use App\Enums\PanStatus;
use App\Livewire\Requestor\Form;
use App\Livewire\Requestor\Index;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake();

    $this->department = Department::factory()->create();
    $this->requestor = User::factory()->requestor()->create();
    $this->requestor->requestorDepartments()->attach($this->department);
    $this->employee = Employee::factory()->create(['department_id' => $this->department->id]);

    $this->actingAs($this->requestor);
});

test('a draft saves without an attachment and gets a real reference number', function () {
    Livewire::test(Form::class)
        ->set('employee_id', $this->employee->id)
        ->set('action_type', 'regularization')
        ->call('saveDraft')
        ->assertHasNoErrors()
        ->assertRedirect(route('requests.index'));

    $pan = PanRequest::sole();
    expect($pan->reference)->toBe('PAN-'.now()->year.'-00001')
        ->and($pan->status)->toBe(PanStatus::Draft)
        ->and($pan->requested_by)->toBe($this->requestor->id)
        ->and($pan->submitted_at)->toBeNull();
});

test('submitting requires the PDF; with one, the PAN moves to the Division Head on a private disk', function () {
    $test = Livewire::test(Form::class)
        ->set('employee_id', $this->employee->id)
        ->set('action_type', 'promotion')
        ->set('justification', 'Recommended after the last performance review cycle.')
        ->call('submit')
        ->assertHasErrors(['attachment' => 'required']);

    $test->set('attachment', UploadedFile::fake()->create('evaluation.pdf', 300, 'application/pdf'))
        ->call('submit')
        ->assertHasNoErrors();

    $pan = PanRequest::sole();
    expect($pan->status)->toBe(PanStatus::WithDivisionHead)
        ->and($pan->submitted_at)->not->toBeNull();
    Storage::assertExists($pan->attachment_path);
});

test('an employee outside the requestor\'s departments is rejected', function () {
    $foreign = Employee::factory()->create(); // different department

    Livewire::test(Form::class)
        ->set('employee_id', $foreign->id)
        ->set('action_type', 'promotion')
        ->call('saveDraft')
        ->assertHasErrors('employee_id');
});

test('editing a colleague\'s draft is forbidden', function () {
    $foreignPan = PanRequest::factory()->status(PanStatus::Draft)->create();

    Livewire::test(Form::class, ['pan' => $foreignPan->reference])
        ->assertForbidden();
});

test('a returned PAN can be resubmitted and goes back to the Division Head', function () {
    $pan = PanRequest::factory()
        ->status(PanStatus::ReturnedToRequestor)
        ->create([
            'requested_by' => $this->requestor->id,
            'employee_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'attachment_path' => 'pans/existing.pdf',
        ]);

    Livewire::test(Form::class, ['pan' => $pan->reference])
        ->set('justification', 'Attachment replaced as requested by the Division Head.')
        ->set('attachment', UploadedFile::fake()->create('replacement.pdf', 200, 'application/pdf'))
        ->call('submit')
        ->assertHasNoErrors();

    expect($pan->fresh()->status)->toBe(PanStatus::WithDivisionHead);
});

test('drafts can be deleted and returned PANs withdrawn from the list', function () {
    $draft = PanRequest::factory()->status(PanStatus::Draft)
        ->create(['requested_by' => $this->requestor->id, 'employee_id' => $this->employee->id, 'department_id' => $this->department->id]);
    $returned = PanRequest::factory()->status(PanStatus::ReturnedToRequestor)
        ->create(['requested_by' => $this->requestor->id, 'employee_id' => $this->employee->id, 'department_id' => $this->department->id]);

    Livewire::test(Index::class)
        ->call('deleteDraft', $draft->id)
        ->call('withdraw', $returned->id);

    expect(PanRequest::find($draft->id))->toBeNull() // soft-deleted
        ->and($returned->fresh()->status)->toBe(PanStatus::Withdrawn);
});

test('a submitted PAN cannot be deleted or re-submitted by its owner', function () {
    $submitted = PanRequest::factory()->status(PanStatus::WithDivisionHead)
        ->create(['requested_by' => $this->requestor->id, 'employee_id' => $this->employee->id, 'department_id' => $this->department->id]);

    Livewire::test(Index::class)
        ->call('deleteDraft', $submitted->id)
        ->assertForbidden();

    expect($submitted->fresh()->trashed())->toBeFalse();
});

test('the index lists only the signed-in requestor\'s PANs', function () {
    PanRequest::factory()->status(PanStatus::WithDivisionHead)
        ->create(['requested_by' => $this->requestor->id, 'employee_id' => $this->employee->id, 'department_id' => $this->department->id, 'reference' => 'PAN-2026-11111']);
    PanRequest::factory()->status(PanStatus::WithDivisionHead)->create(['reference' => 'PAN-2026-22222']);

    Livewire::test(Index::class)
        ->assertSee('PAN-2026-11111')
        ->assertDontSee('PAN-2026-22222');
});

test('the attachment downloads for the owner via the policy-gated route', function () {
    Livewire::test(Form::class)
        ->set('employee_id', $this->employee->id)
        ->set('action_type', 'wage-order')
        ->set('justification', 'Wage order compliance for the current period.')
        ->set('attachment', UploadedFile::fake()->create('wage_order.pdf', 120, 'application/pdf'))
        ->call('submit');

    $pan = PanRequest::sole();

    $this->get('/pan/'.$pan->reference.'/attachment')
        ->assertOk()
        ->assertDownload('wage_order.pdf');
});
