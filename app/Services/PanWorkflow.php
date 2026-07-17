<?php

namespace App\Services;

use App\Enums\PanOrigin;
use App\Enums\PanStatus;
use App\Exceptions\IllegalPanTransition;

/**
 * THE state machine. Owns every PAN transition; throws on illegal ones.
 * Transcribed from the Glossary (the spec's definitive state table).
 *
 * This class is pure: it decides *whether* a move is legal and *where* it lands.
 * WHO may perform it is expressed as a stage-permission key ('by') that policies
 * check against the user's boolean columns; side effects (auto-Regular, pan_returns
 * rows, audit entries) belong to the callers/services layered on top.
 *
 * Documented assumptions (flag to the domain owner if wrong):
 * - resubmit_to_hr: the HR Approver returns ONE step back, so the preparer's
 *   resubmission goes straight back to For HR Approval (no second DH confirmation).
 * - reject_final: the Final Approver rejects "all the way back to HR Preparation",
 *   so the PAN lands in InPreparation (the reason lives in pan_returns).
 */
class PanWorkflow
{
    /**
     * action => [from statuses, to status, stage permission, reason required?]
     *
     * @var array<string, array{from: PanStatus[], to: PanStatus, by: string, reason: bool}>
     */
    public const TRANSITIONS = [
        // Requestor stage
        'submit' => ['from' => [PanStatus::Draft], 'to' => PanStatus::WithDivisionHead, 'by' => 'requestor', 'reason' => false],
        'resubmit' => ['from' => [PanStatus::ReturnedToRequestor], 'to' => PanStatus::WithDivisionHead, 'by' => 'requestor', 'reason' => false],
        'withdraw' => ['from' => [PanStatus::ReturnedToRequestor], 'to' => PanStatus::Withdrawn, 'by' => 'requestor', 'reason' => false],

        // Division Head stage (DH Head when Manila — policy concern, not workflow)
        'approve_division' => ['from' => [PanStatus::WithDivisionHead], 'to' => PanStatus::AwaitingTag, 'by' => 'division_head', 'reason' => false],
        'return_to_requestor' => ['from' => [PanStatus::WithDivisionHead], 'to' => PanStatus::ReturnedToRequestor, 'by' => 'division_head', 'reason' => true],

        // HR Preparation stage (HR Head when Manila — policy concern)
        'tag' => ['from' => [PanStatus::AwaitingTag], 'to' => PanStatus::InPreparation, 'by' => 'hr_preparer', 'reason' => false],
        'submit_for_confirmation' => ['from' => [PanStatus::InPreparation], 'to' => PanStatus::ForConfirmation, 'by' => 'hr_preparer', 'reason' => false],
        'resubmit_to_hr' => ['from' => [PanStatus::ReturnedToPreparer], 'to' => PanStatus::ForHrApproval, 'by' => 'hr_preparer', 'reason' => false],
        'void' => ['from' => [PanStatus::AwaitingTag, PanStatus::InPreparation, PanStatus::ReturnedToPreparer], 'to' => PanStatus::Voided, 'by' => 'hr_preparer', 'reason' => true],

        // DH Confirmation stage
        'confirm' => ['from' => [PanStatus::ForConfirmation], 'to' => PanStatus::ForHrApproval, 'by' => 'division_head', 'reason' => false],
        'dispute' => ['from' => [PanStatus::ForConfirmation], 'to' => PanStatus::InPreparation, 'by' => 'division_head', 'reason' => true],

        // HR Approver stage (returns ONE step back — to the preparer, never the requestor)
        'approve_hr' => ['from' => [PanStatus::ForHrApproval], 'to' => PanStatus::ForFinalApproval, 'by' => 'hr_approver', 'reason' => false],
        'return_to_preparer' => ['from' => [PanStatus::ForHrApproval], 'to' => PanStatus::ReturnedToPreparer, 'by' => 'hr_approver', 'reason' => true],

        // Final Approver stage (single or bulk; Regularization auto-finalizes to "Regular")
        'approve_final' => ['from' => [PanStatus::ForFinalApproval], 'to' => PanStatus::Approved, 'by' => 'final_approver', 'reason' => false],
        'reject_final' => ['from' => [PanStatus::ForFinalApproval], 'to' => PanStatus::InPreparation, 'by' => 'final_approver', 'reason' => true],

        // Serving & closeout (back with HR Preparation)
        'mark_served' => ['from' => [PanStatus::Approved], 'to' => PanStatus::Served, 'by' => 'hr_preparer', 'reason' => false],
        'mark_unserved' => ['from' => [PanStatus::Approved], 'to' => PanStatus::Unserved, 'by' => 'hr_preparer', 'reason' => true],
        'file' => ['from' => [PanStatus::Served], 'to' => PanStatus::Filed, 'by' => 'hr_preparer', 'reason' => false],
    ];

    /** Where a PAN enters the workflow. HR-originated PANs skip Requestor + Division Head. */
    public function initialStatus(PanOrigin $origin): PanStatus
    {
        return match ($origin) {
            PanOrigin::Requestor => PanStatus::Draft,
            PanOrigin::Hr => PanStatus::AwaitingTag,
        };
    }

    /**
     * Apply an action to a status. Returns the new status; throws on illegal moves.
     */
    public function apply(PanStatus $from, string $action): PanStatus
    {
        $transition = self::TRANSITIONS[$action] ?? throw IllegalPanTransition::unknownAction($action);

        if (! in_array($from, $transition['from'], true)) {
            throw IllegalPanTransition::notAllowedFrom($action, $from);
        }

        return $transition['to'];
    }

    public function allows(PanStatus $from, string $action): bool
    {
        return isset(self::TRANSITIONS[$action])
            && in_array($from, self::TRANSITIONS[$action]['from'], true);
    }

    /**
     * Every action available from a status.
     * v1 lesson: each of these MUST have a reachable control in the UI.
     *
     * @return string[]
     */
    public function actionsFrom(PanStatus $from): array
    {
        return array_keys(array_filter(
            self::TRANSITIONS,
            fn (array $t) => in_array($from, $t['from'], true)
        ));
    }

    /** The stage-permission key policies must check for this action. */
    public function permissionFor(string $action): string
    {
        $transition = self::TRANSITIONS[$action] ?? throw IllegalPanTransition::unknownAction($action);

        return $transition['by'];
    }

    /** Whether the action demands a mandatory reason (recorded in pan_returns / audit). */
    public function requiresReason(string $action): bool
    {
        $transition = self::TRANSITIONS[$action] ?? throw IllegalPanTransition::unknownAction($action);

        return $transition['reason'];
    }
}
