<?php

use App\Enums\PanOrigin;
use App\Enums\PanStatus;
use App\Exceptions\IllegalPanTransition;
use App\Services\PanWorkflow;

beforeEach(function () {
    $this->workflow = new PanWorkflow;
});

/*
|--------------------------------------------------------------------------
| Happy paths
|--------------------------------------------------------------------------
*/

test('the full requestor-origin journey runs Draft → Filed', function () {
    $status = $this->workflow->initialStatus(PanOrigin::Requestor);
    expect($status)->toBe(PanStatus::Draft);

    foreach ([
        'submit' => PanStatus::WithDivisionHead,
        'approve_division' => PanStatus::AwaitingTag,
        'tag' => PanStatus::InPreparation,
        'submit_for_confirmation' => PanStatus::ForConfirmation,
        'confirm' => PanStatus::ForHrApproval,
        'approve_hr' => PanStatus::ForFinalApproval,
        'approve_final' => PanStatus::Approved,
        'mark_served' => PanStatus::Served,
        'file' => PanStatus::Filed,
    ] as $action => $expected) {
        $status = $this->workflow->apply($status, $action);
        expect($status)->toBe($expected);
    }

    expect($status->isTerminal())->toBeTrue();
});

test('approve_division skips re-tagging when the PAN was already tagged before this trip through Division Head', function () {
    // e.g. tagged Manila once, HR Head returned it to the Requestor for a fix, requestor
    // resubmitted, DH Head approved again — the earlier tag decision shouldn't be re-asked.
    expect($this->workflow->apply(PanStatus::WithDivisionHead, 'approve_division', \App\Enums\ConfidentialityTag::Manila))
        ->toBe(PanStatus::InPreparation)
        ->and($this->workflow->apply(PanStatus::WithDivisionHead, 'approve_division', \App\Enums\ConfidentialityTag::Tarlac))
        ->toBe(PanStatus::InPreparation)
        ->and($this->workflow->apply(PanStatus::WithDivisionHead, 'approve_division', \App\Enums\ConfidentialityTag::Untagged))
        ->toBe(PanStatus::AwaitingTag)
        ->and($this->workflow->apply(PanStatus::WithDivisionHead, 'approve_division'))
        ->toBe(PanStatus::AwaitingTag);
});

test('an HR-origin PAN (Update PAN) skips Requestor and Division Head, entering at AwaitingTag', function () {
    $status = $this->workflow->initialStatus(PanOrigin::Hr);

    expect($status)->toBe(PanStatus::AwaitingTag)
        ->and($this->workflow->apply($status, 'tag'))->toBe(PanStatus::InPreparation);
});

/*
|--------------------------------------------------------------------------
| Every backward branch
|--------------------------------------------------------------------------
*/

test('the Division Head returns to the Requestor, who may resubmit', function () {
    $returned = $this->workflow->apply(PanStatus::WithDivisionHead, 'return_to_requestor');

    expect($returned)->toBe(PanStatus::ReturnedToRequestor)
        ->and($this->workflow->apply($returned, 'resubmit'))->toBe(PanStatus::WithDivisionHead);
});

test('the Requestor may withdraw a returned PAN instead of resubmitting', function () {
    $status = $this->workflow->apply(PanStatus::ReturnedToRequestor, 'withdraw');

    expect($status)->toBe(PanStatus::Withdrawn)
        ->and($status->isTerminal())->toBeTrue();
});

test('the Division Head disputes a prepared PAN back to the preparer', function () {
    expect($this->workflow->apply(PanStatus::ForConfirmation, 'dispute'))
        ->toBe(PanStatus::InPreparation);
});

test('the HR Approver returns ONE step back — to the preparer, never the requestor', function () {
    $returned = $this->workflow->apply(PanStatus::ForHrApproval, 'return_to_preparer');

    expect($returned)->toBe(PanStatus::ReturnedToPreparer)
        // resubmission goes straight back to HR Approval (documented assumption)
        ->and($this->workflow->apply($returned, 'resubmit_to_hr'))->toBe(PanStatus::ForHrApproval);
});

test('the Final Approver rejects all the way back to HR Preparation', function () {
    expect($this->workflow->apply(PanStatus::ForFinalApproval, 'reject_final'))
        ->toBe(PanStatus::InPreparation);
});

test('HR may void while awaiting tag, preparing, or resolving a return', function (PanStatus $from) {
    $status = $this->workflow->apply($from, 'void');

    expect($status)->toBe(PanStatus::Voided)
        ->and($status->isTerminal())->toBeTrue();
})->with([
    PanStatus::AwaitingTag,
    PanStatus::InPreparation,
    PanStatus::ReturnedToPreparer,
]);

test('an approved PAN may be marked unserved with a reason — terminal', function () {
    $status = $this->workflow->apply(PanStatus::Approved, 'mark_unserved');

    expect($status)->toBe(PanStatus::Unserved)
        ->and($status->isTerminal())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Illegal moves
|--------------------------------------------------------------------------
*/

test('applying an action from the wrong status throws', function (PanStatus $from, string $action) {
    $this->workflow->apply($from, $action);
})->throws(IllegalPanTransition::class)->with([
    'submitting an already-submitted PAN' => [PanStatus::WithDivisionHead, 'submit'],
    'division-approving a draft (skipping submission)' => [PanStatus::Draft, 'approve_division'],
    'tagging before division approval' => [PanStatus::WithDivisionHead, 'tag'],
    'final-approving before HR approval' => [PanStatus::ForHrApproval, 'approve_final'],
    'serving before final approval' => [PanStatus::ForFinalApproval, 'mark_served'],
    'filing before serving' => [PanStatus::Approved, 'file'],
    'voiding a draft (void is an HR action)' => [PanStatus::Draft, 'void'],
    'withdrawing a PAN that was not returned' => [PanStatus::WithDivisionHead, 'withdraw'],
]);

test('unknown actions throw', function () {
    $this->workflow->apply(PanStatus::Draft, 'teleport');
})->throws(IllegalPanTransition::class, 'Unknown PAN workflow action [teleport].');

test('terminal statuses allow no actions at all', function (PanStatus $terminal) {
    expect($terminal->isTerminal())->toBeTrue()
        ->and($this->workflow->actionsFrom($terminal))->toBeEmpty();
})->with([
    PanStatus::Unserved,
    PanStatus::Filed,
    PanStatus::Withdrawn,
    PanStatus::Voided,
]);

/*
|--------------------------------------------------------------------------
| The transition table itself
|--------------------------------------------------------------------------
*/

test('every non-terminal status offers at least one action (no dead ends)', function () {
    foreach (PanStatus::cases() as $status) {
        if (! $status->isTerminal()) {
            expect($this->workflow->actionsFrom($status))
                ->not->toBeEmpty("status [{$status->value}] is a dead end");
        }
    }
});

test('the available actions per status match the Glossary', function (PanStatus $from, array $expected) {
    expect($this->workflow->actionsFrom($from))->toEqualCanonicalizing($expected);
})->with([
    'Draft' => [PanStatus::Draft, ['submit']],
    'With Division Head' => [PanStatus::WithDivisionHead, ['approve_division', 'return_to_requestor', 'proxy_approve_dh']],
    'Returned to Requestor' => [PanStatus::ReturnedToRequestor, ['resubmit', 'withdraw']],
    'Awaiting Tag' => [PanStatus::AwaitingTag, ['tag', 'void']],
    'In Preparation' => [PanStatus::InPreparation, ['submit_for_confirmation', 'void', 'send_back_to_requestor']],
    'For Confirmation' => [PanStatus::ForConfirmation, ['confirm', 'dispute', 'proxy_approve_confirmation']],
    'Returned to Preparer' => [PanStatus::ReturnedToPreparer, ['resubmit_to_hr', 'void', 'send_back_to_requestor']],
    'For HR Approval' => [PanStatus::ForHrApproval, ['approve_hr', 'return_to_preparer']],
    'For Final Approval' => [PanStatus::ForFinalApproval, ['approve_final', 'reject_final']],
    'Approved' => [PanStatus::Approved, ['mark_served', 'mark_unserved']],
    'Served' => [PanStatus::Served, ['file']],
]);

test('every backward or destructive move demands a mandatory reason', function () {
    $reasonRequired = ['return_to_requestor', 'dispute', 'return_to_preparer', 'reject_final', 'void', 'mark_unserved', 'send_back_to_requestor', 'proxy_approve_dh', 'proxy_approve_confirmation'];

    foreach (array_keys(PanWorkflow::TRANSITIONS) as $action) {
        expect($this->workflow->requiresReason($action))
            ->toBe(in_array($action, $reasonRequired, true), "reason flag wrong for [{$action}]");
    }
});

test('every action names the stage permission a policy must check', function () {
    $stages = ['requestor', 'division_head', 'hr_preparer', 'hr_approver', 'final_approver', 'proxy_approver'];

    foreach (array_keys(PanWorkflow::TRANSITIONS) as $action) {
        expect($this->workflow->permissionFor($action))->toBeIn($stages);
    }
});

test('allows() agrees with apply()', function () {
    expect($this->workflow->allows(PanStatus::Draft, 'submit'))->toBeTrue()
        ->and($this->workflow->allows(PanStatus::Draft, 'approve_division'))->toBeFalse()
        ->and($this->workflow->allows(PanStatus::Draft, 'teleport'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Enum ↔ UI parity
|--------------------------------------------------------------------------
*/

test('the enum has exactly the 15 Glossary statuses, keyed like the status pills', function () {
    expect(array_column(PanStatus::cases(), 'value'))->toEqualCanonicalizing([
        'draft', 'with-division-head', 'returned-to-requestor', 'awaiting-tag',
        'in-preparation', 'for-confirmation', 'returned-to-preparer', 'for-hr-approval',
        'for-final-approval', 'approved', 'served', 'unserved', 'filed', 'withdrawn', 'voided',
    ]);
});

test('ongoing statuses (the employee-delete guard) exclude drafts and terminals', function () {
    $ongoing = array_filter(PanStatus::cases(), fn (PanStatus $s) => $s->isOngoing());

    expect($ongoing)->toHaveCount(10)
        ->and(PanStatus::Draft->isOngoing())->toBeFalse()
        ->and(PanStatus::Filed->isOngoing())->toBeFalse()
        ->and(PanStatus::WithDivisionHead->isOngoing())->toBeTrue()
        ->and(PanStatus::Served->isOngoing())->toBeTrue();
});
