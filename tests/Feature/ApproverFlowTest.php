<?php

use App\Enums\EmploymentStatus;
use App\Enums\PanStatus;
use App\Livewire\FinalApprover\Queue as FinalQueue;
use App\Livewire\FinalApprover\Show as FinalShow;
use App\Livewire\HrApprover\Queue as HrQueue;
use App\Models\PanForm;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| HR Approver
|--------------------------------------------------------------------------
*/

describe('HR Approver', function () {
    beforeEach(function () {
        $this->approver = User::factory()->hrApprover()->create();
        $this->actingAs($this->approver);
    });

    test('approving forwards to the Final Approver and records the HR approver', function () {
        $pan = PanRequest::factory()->status(PanStatus::ForHrApproval)->create();

        Livewire::test(HrQueue::class)->call('approve', $pan->id);

        $pan->refresh();
        expect($pan->status)->toBe(PanStatus::ForFinalApproval)
            ->and($pan->hr_approver_id)->toBe($this->approver->id);
    });

    test('returning goes ONE step back to the preparer with the reason on record', function () {
        $pan = PanRequest::factory()->status(PanStatus::ForHrApproval)->create();

        Livewire::test(HrQueue::class)
            ->call('startReason', $pan->id)
            ->call('submitReason')
            ->assertHasErrors(['reason' => 'required']);

        Livewire::test(HrQueue::class)
            ->call('startReason', $pan->id)
            ->set('reason', 'Wage number mismatch')
            ->call('submitReason')
            ->assertHasNoErrors();

        expect($pan->fresh()->status)->toBe(PanStatus::ReturnedToPreparer)
            ->and($pan->returns()->sole())
            ->action->toBe('return_to_preparer')
            ->returned_by->toBe($this->approver->id);
    });

    test('the queue separates awaiting from further-along PANs', function () {
        PanRequest::factory()->status(PanStatus::ForHrApproval)->create(['reference' => 'PAN-2026-92101']);
        PanRequest::factory()->status(PanStatus::ForFinalApproval)->create(['reference' => 'PAN-2026-92102']);
        PanRequest::factory()->status(PanStatus::InPreparation)->create(['reference' => 'PAN-2026-92103']);

        Livewire::test(HrQueue::class)
            ->assertSee('PAN-2026-92101')
            ->assertDontSee('PAN-2026-92102')
            ->assertDontSee('PAN-2026-92103')
            ->set('filter', 'later')
            ->assertSee('PAN-2026-92102')
            ->assertDontSee('PAN-2026-92101');
    });

    test('acting on a PAN that is not at HR approval is forbidden', function () {
        $pan = PanRequest::factory()->status(PanStatus::ForFinalApproval)->create();

        Livewire::test(HrQueue::class)
            ->call('approve', $pan->id)
            ->assertForbidden();
    });
});

/*
|--------------------------------------------------------------------------
| Final Approver
|--------------------------------------------------------------------------
*/

describe('Final Approver', function () {
    beforeEach(function () {
        $this->approver = User::factory()->finalApprover()->create();
        $this->actingAs($this->approver);
    });

    test('single approval moves the PAN to Approved and records the final approver', function () {
        $pan = PanRequest::factory()->status(PanStatus::ForFinalApproval)
            ->create(['action_type' => 'promotion']);

        Livewire::test(FinalQueue::class)->call('approveOne', $pan->id);

        $pan->refresh();
        expect($pan->status)->toBe(PanStatus::Approved)
            ->and($pan->final_approver_id)->toBe($this->approver->id);
    });

    test('approving a Regularization auto-finalizes employment status to Regular', function () {
        $pan = PanRequest::factory()->status(PanStatus::ForFinalApproval)
            ->create(['action_type' => 'regularization']);
        $form = PanForm::factory()->create([
            'pan_request_id' => $pan->id,
            'employment_status' => EmploymentStatus::Probationary,
        ]);

        Livewire::test(FinalShow::class, ['pan' => $pan->reference])
            ->call('approve')
            ->assertRedirect(route('final-approval.queue'));

        expect($pan->fresh()->status)->toBe(PanStatus::Approved)
            ->and($form->fresh()->employment_status)->toBe(EmploymentStatus::Regular);
    });

    test('a non-Regularization approval leaves the prepared employment status alone', function () {
        $pan = PanRequest::factory()->status(PanStatus::ForFinalApproval)
            ->create(['action_type' => 'wage-order']);
        $form = PanForm::factory()->create([
            'pan_request_id' => $pan->id,
            'employment_status' => EmploymentStatus::Probationary,
        ]);

        Livewire::test(FinalQueue::class)->call('approveOne', $pan->id);

        expect($form->fresh()->employment_status)->toBe(EmploymentStatus::Probationary);
    });

    test('bulk approval clears every selected PAN', function () {
        $pans = PanRequest::factory()->count(3)->status(PanStatus::ForFinalApproval)
            ->create(['action_type' => 'regularization']);

        Livewire::test(FinalQueue::class)
            ->set('selected', $pans->pluck('id')->map(fn ($id) => (string) $id)->all())
            ->call('approveSelected');

        expect(PanRequest::where('status', PanStatus::Approved)->count())->toBe(3);
    });

    test('select-all-of-type targets only that action type', function () {
        $reg = PanRequest::factory()->count(2)->status(PanStatus::ForFinalApproval)
            ->create(['action_type' => 'regularization']);
        PanRequest::factory()->status(PanStatus::ForFinalApproval)
            ->create(['action_type' => 'promotion']);

        $expected = $reg->pluck('id')->map(fn ($id) => (string) $id)->sort()->values()->all();

        Livewire::test(FinalQueue::class)
            ->call('selectType', 'regularization')
            ->assertSet('selected', fn (array $selected) => collect($selected)->sort()->values()->all() === $expected);
    });

    test('rejecting demands a reason and sends the PAN back to In Preparation', function () {
        $pan = PanRequest::factory()->status(PanStatus::ForFinalApproval)->create();

        Livewire::test(FinalQueue::class)
            ->call('startReject', $pan->id)
            ->call('submitReject')
            ->assertHasErrors(['reason' => 'required']);

        Livewire::test(FinalQueue::class)
            ->call('startReject', $pan->id)
            ->set('reason', 'Values need revision')
            ->call('submitReject')
            ->assertHasNoErrors();

        expect($pan->fresh()->status)->toBe(PanStatus::InPreparation)
            ->and($pan->returns()->sole()->action)->toBe('reject_final');
    });

    test('bulk rejection writes one pan_returns row per PAN', function () {
        $pans = PanRequest::factory()->count(2)->status(PanStatus::ForFinalApproval)->create();

        Livewire::test(FinalQueue::class)
            ->set('selected', $pans->pluck('id')->map(fn ($id) => (string) $id)->all())
            ->call('startReject')
            ->set('reason', 'Needs supporting document')
            ->call('submitReject')
            ->assertHasNoErrors();

        expect(PanRequest::where('status', PanStatus::InPreparation)->count())->toBe(2)
            ->and(App\Models\PanReturn::where('action', 'reject_final')->count())->toBe(2);
    });

    test('the queue lists only PANs awaiting final approval', function () {
        PanRequest::factory()->status(PanStatus::ForFinalApproval)->create(['reference' => 'PAN-2026-93101']);
        PanRequest::factory()->status(PanStatus::Approved)->create(['reference' => 'PAN-2026-93102']);

        Livewire::test(FinalQueue::class)
            ->assertSee('PAN-2026-93101')
            ->assertDontSee('PAN-2026-93102');
    });

    test('an HR approver cannot give final approval', function () {
        $pan = PanRequest::factory()->status(PanStatus::ForFinalApproval)->create();
        $this->actingAs(User::factory()->hrApprover()->create());

        Livewire::test(FinalQueue::class)
            ->call('approveOne', $pan->id)
            ->assertForbidden();
    });
});
