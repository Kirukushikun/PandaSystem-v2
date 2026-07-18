<?php

namespace App\Enums;

/**
 * The definitive PAN state list — one status column, one enum (never split into
 * "approval status" + "serving status"; illegal combinations must be unrepresentable).
 *
 * Backing values are the x-status-pill keys from the UI scaffold, so components can
 * pass $pan->status->value straight to <x-status-pill :status="...">.
 * Labels/tones mirror the Glossary, which is the spec's transition table.
 */
enum PanStatus: string
{
    case Draft = 'draft';
    case WithDivisionHead = 'with-division-head';
    case ReturnedToRequestor = 'returned-to-requestor';
    case AwaitingTag = 'awaiting-tag';
    case InPreparation = 'in-preparation';
    case ForConfirmation = 'for-confirmation';
    case ReturnedToPreparer = 'returned-to-preparer';
    case ForHrApproval = 'for-hr-approval';
    case ForFinalApproval = 'for-final-approval';
    case Approved = 'approved';
    case Served = 'served';
    case Unserved = 'unserved';
    case Filed = 'filed';
    case Withdrawn = 'withdrawn';
    case Voided = 'voided';

    /** Human label, exactly as the Glossary/pills show it. */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::WithDivisionHead => 'With Division Head',
            self::ReturnedToRequestor => 'Returned — for correction',
            self::AwaitingTag => 'Awaiting tag & preparation',
            self::InPreparation => 'HR Preparation',
            self::ForConfirmation => 'For DH Confirmation',
            self::ReturnedToPreparer => 'Returned by HR Approver',
            self::ForHrApproval => 'For HR Approval',
            self::ForFinalApproval => 'For Final Approval',
            self::Approved => 'Approved — for serving',
            self::Served => 'Served',
            self::Unserved => 'Unserved',
            self::Filed => 'Filed',
            self::Withdrawn => 'Withdrawn',
            self::Voided => 'Voided',
        };
    }

    /**
     * Terminal states: the PAN never transitions again. (Filed can spawn a follow-up
     * PAN, but that is a NEW record chained via previous_pan_id, not a transition.)
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Unserved, self::Filed, self::Withdrawn, self::Voided], true);
    }

    /**
     * "Ongoing" = blocks employee deletion: submitted and not yet closed out.
     * Drafts don't block (v1 lesson — enforce in policy + query, not UI).
     */
    public function isOngoing(): bool
    {
        return $this !== self::Draft && ! $this->isTerminal();
    }
}
