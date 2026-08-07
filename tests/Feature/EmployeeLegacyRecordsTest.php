<?php

use App\Models\Employee;
use App\Models\EmployeeAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

/*
|--------------------------------------------------------------------------
| Visibility — HR Head and Final Approver only, never a plain HR Preparer
|--------------------------------------------------------------------------
*/

test('a plain HR Preparer sees no Legacy Records panel at all on Employee History', function () {
    $employee = Employee::factory()->create();
    EmployeeAttachment::factory()->create(['employee_id' => $employee->id]);
    $preparer = User::factory()->hrPreparer()->create();

    $this->actingAs($preparer)
        ->get('/employees/'.$employee->employee_no.'/pans')
        ->assertOk()
        ->assertDontSee('Legacy Records');
});

test('HR Head sees the Legacy Records panel with upload controls', function () {
    $employee = Employee::factory()->create();
    $hrHead = User::factory()->hrHead()->create();

    $this->actingAs($hrHead)
        ->get('/employees/'.$employee->employee_no.'/pans')
        ->assertOk()
        ->assertSee('Legacy Records')
        ->assertSee('Upload Legacy Record');
});

test('Final Approver sees the Legacy Records panel without upload controls', function () {
    $employee = Employee::factory()->create();
    $finalApprover = User::factory()->finalApprover()->create();

    $this->actingAs($finalApprover)
        ->get('/final-approval/employees/'.$employee->employee_no.'/pans')
        ->assertOk()
        ->assertSee('Legacy Records')
        ->assertDontSee('Upload Legacy Record');
});

/*
|--------------------------------------------------------------------------
| Upload / remove — HR Head only, always Manila
|--------------------------------------------------------------------------
*/

test('HR Head can upload a legacy record and it is saved as Manila', function () {
    $employee = Employee::factory()->create();
    $hrHead = User::factory()->hrHead()->create();
    $file = UploadedFile::fake()->create('old-pan.pdf', 500, 'application/pdf');

    Livewire::actingAs($hrHead)
        ->test(App\Livewire\HrPreparation\EmployeeHistory::class, ['employeeNo' => $employee->employee_no])
        ->set('newLegacyRecords', [$file])
        ->call('uploadLegacyRecords')
        ->assertHasNoErrors();

    $record = EmployeeAttachment::where('employee_id', $employee->id)->firstOrFail();
    expect($record->confidentiality_tag)->toBe(App\Enums\ConfidentialityTag::Manila);
    expect($record->original_name)->toBe('old-pan.pdf');
    Storage::disk('local')->assertExists($record->path);
});

test('a plain HR Preparer cannot call the upload action directly', function () {
    $employee = Employee::factory()->create();
    $preparer = User::factory()->hrPreparer()->create();
    $file = UploadedFile::fake()->create('sneaky.pdf', 100, 'application/pdf');

    Livewire::actingAs($preparer)
        ->test(App\Livewire\HrPreparation\EmployeeHistory::class, ['employeeNo' => $employee->employee_no])
        ->set('newLegacyRecords', [$file])
        ->call('uploadLegacyRecords')
        ->assertForbidden();

    expect(EmployeeAttachment::where('employee_id', $employee->id)->count())->toBe(0);
});

test('HR Head can remove a legacy record', function () {
    $employee = Employee::factory()->create();
    $hrHead = User::factory()->hrHead()->create();
    Storage::disk('local')->put('employees/test/legacy/keep-me.pdf', 'x');
    $record = EmployeeAttachment::factory()->create([
        'employee_id' => $employee->id,
        'path' => 'employees/test/legacy/keep-me.pdf',
    ]);

    Livewire::actingAs($hrHead)
        ->test(App\Livewire\HrPreparation\EmployeeHistory::class, ['employeeNo' => $employee->employee_no])
        ->call('removeLegacyRecord', $record->id);

    expect(EmployeeAttachment::find($record->id))->toBeNull();
    Storage::disk('local')->assertMissing('employees/test/legacy/keep-me.pdf');
});

test('a Final Approver cannot call remove — it is view-only for that role', function () {
    $employee = Employee::factory()->create();
    $finalApprover = User::factory()->finalApprover()->create();
    $record = EmployeeAttachment::factory()->create(['employee_id' => $employee->id]);

    Livewire::actingAs($finalApprover)
        ->test(App\Livewire\FinalApprover\EmployeeHistory::class, ['employeeNo' => $employee->employee_no])
        ->call('removeLegacyRecord', $record->id)
        ->assertForbidden();

    expect(EmployeeAttachment::find($record->id))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Download route — scoped through the employee relation
|--------------------------------------------------------------------------
*/

test('an employee_attachment id belonging to a different employee cannot be reached via this employee_no', function () {
    $employee = Employee::factory()->create();
    $otherEmployee = Employee::factory()->create();
    $foreignAttachment = EmployeeAttachment::factory()->create(['employee_id' => $otherEmployee->id]);
    $hrHead = User::factory()->hrHead()->create();

    $this->actingAs($hrHead)
        ->get('/employees/'.$employee->employee_no.'/legacy-records/'.$foreignAttachment->id)
        ->assertNotFound();
});

test('the legacy record download route 403s for a plain HR Preparer', function () {
    $employee = Employee::factory()->create();
    $record = EmployeeAttachment::factory()->create(['employee_id' => $employee->id]);
    $preparer = User::factory()->hrPreparer()->create();

    $this->actingAs($preparer)
        ->get('/employees/'.$employee->employee_no.'/legacy-records/'.$record->id)
        ->assertForbidden();
});

test('the legacy record download route works for HR Head and Final Approver', function () {
    $employee = Employee::factory()->create();
    Storage::disk('local')->put('employees/test/legacy/downloadable.pdf', 'contents');
    $record = EmployeeAttachment::factory()->create([
        'employee_id' => $employee->id,
        'path' => 'employees/test/legacy/downloadable.pdf',
    ]);

    $this->actingAs(User::factory()->hrHead()->create())
        ->get('/employees/'.$employee->employee_no.'/legacy-records/'.$record->id)
        ->assertOk();

    $this->actingAs(User::factory()->finalApprover()->create())
        ->get('/employees/'.$employee->employee_no.'/legacy-records/'.$record->id)
        ->assertOk();
});
