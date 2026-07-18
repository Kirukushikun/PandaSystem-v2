<?php

use App\Enums\EmploymentStatus;
use App\Enums\PanStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * The capstone: one Regularization walks the ENTIRE chain through the real
 * Livewire components, one role per account — exactly the from-scratch
 * simulation the seeded quick-fill accounts run in the browser.
 */
test('a PAN travels submit → division → prepare → confirm → HR → final → serve → file', function () {
    Storage::fake();

    $department = Department::factory()->create(['name' => 'Poultry']);
    $employee = Employee::factory()->create(['department_id' => $department->id]);

    $requestor = User::factory()->requestor()->create();
    $requestor->requestorDepartments()->attach($department);
    $head = User::factory()->divisionHead()->create();
    $head->headedDepartments()->attach($department);
    $preparer = User::factory()->hrPreparer()->create();
    $hrApprover = User::factory()->hrApprover()->create();
    $finalApprover = User::factory()->finalApprover()->create();

    // 1 — Requestor submits with the PDF
    $this->actingAs($requestor);
    Livewire::test(App\Livewire\Requestor\Form::class)
        ->set('employee_id', $employee->id)
        ->set('action_type', 'regularization')
        ->set('justification', 'Completed the probationary period with a passing evaluation.')
        ->set('attachment', UploadedFile::fake()->create('evaluation.pdf', 150, 'application/pdf'))
        ->call('submit')->assertHasNoErrors();

    $pan = PanRequest::sole();
    expect($pan->status)->toBe(PanStatus::WithDivisionHead)
        ->and($head->notifications()->count())->toBe(1); // the head was pinged

    // 2 — Division Head approves
    $this->actingAs($head);
    Livewire::test(App\Livewire\DivisionHead\Queue::class)->call('approve', $pan->id);
    expect($pan->fresh()->status)->toBe(PanStatus::AwaitingTag);

    // 3 — HR Preparer tags Tarlac and prepares the paperwork
    $this->actingAs($preparer);
    Livewire::test(App\Livewire\HrPreparation\PrepareForm::class, ['pan' => $pan->reference])
        ->set('tag', 'tarlac')->call('applyTag')
        ->set('date_hired', '2026-01-05')
        ->set('doe_from', '2026-08-01')
        ->set('toValues.position', 'Poultry Caretaker II')
        ->set('toValues.leavecredits', 'SL - 1.25 | VL - 1.25')
        ->set('toValues.basic', '18,900.00')
        ->call('submit')->assertHasNoErrors();

    $pan->refresh();
    expect($pan->status)->toBe(PanStatus::ForConfirmation)
        ->and($pan->form->employment_status)->toBe(EmploymentStatus::Probationary);

    // 4 — Division Head confirms the prepared PAN
    $this->actingAs($head);
    Livewire::test(App\Livewire\DivisionHead\Queue::class)->call('confirmPrepared', $pan->id);
    expect($pan->fresh()->status)->toBe(PanStatus::ForHrApproval);

    // 5 — HR Approver approves
    $this->actingAs($hrApprover);
    Livewire::test(App\Livewire\HrApprover\Queue::class)->call('approve', $pan->id);
    expect($pan->fresh()->status)->toBe(PanStatus::ForFinalApproval);

    // 6 — Final Approver signs off: Regularization auto-finalizes to Regular
    $this->actingAs($finalApprover);
    Livewire::test(App\Livewire\FinalApprover\Queue::class)->call('approveOne', $pan->id);
    $pan->refresh();
    expect($pan->status)->toBe(PanStatus::Approved)
        ->and($pan->form->fresh()->employment_status)->toBe(EmploymentStatus::Regular)
        ->and($pan->final_approver_id)->toBe($finalApprover->id);

    // 7 — the prepared PAN prints (all three copies, policy-gated)
    $this->get('/pan/'.$pan->reference.'/print')->assertOk()->assertSee('PAYROLL COPY');

    // 8 — HR serves and files; the requestor learns the cycle is complete
    $this->actingAs($preparer);
    Livewire::test(App\Livewire\HrPreparation\Queue::class)
        ->call('markServed', $pan->id)
        ->call('filePan', $pan->id);

    $pan->refresh();
    expect($pan->status)->toBe(PanStatus::Filed)
        ->and($pan->filed_at)->not->toBeNull()
        ->and($requestor->notifications()->pluck('data')->pluck('title'))->toContain('Filed — cycle complete');

    // 9 — every participant is on the record
    expect($pan->division_head_id)->toBe($head->id)
        ->and($pan->hr_preparer_id)->toBe($preparer->id)
        ->and($pan->hr_approver_id)->toBe($hrApprover->id);
});
