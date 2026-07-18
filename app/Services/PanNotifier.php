<?php

namespace App\Services;

use App\Enums\ConfidentialityTag;
use App\Enums\PanStatus;
use App\Models\PanRequest;
use App\Models\User;
use App\Notifications\PanActivity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * "Who acts next gets pinged." Runs on every status change (PanRequestObserver):
 * picks the recipients the new status is waiting on — respecting the Manila
 * routing (DH Head, HR Head) — plus the courtesy pings back to the originator.
 * The actor never notifies themself.
 */
class PanNotifier
{
    public function statusChanged(PanRequest $pan, PanStatus $from, PanStatus $to): void
    {
        // Tagging is the preparer's own move into their own queue — nobody to tell.
        if ($from === PanStatus::AwaitingTag && $to === PanStatus::InPreparation) {
            return;
        }

        [$recipients, $title, $context] = $this->route($pan, $to);

        $recipients = $recipients
            ->filter()
            ->unique('id')
            ->reject(fn (User $user) => $user->id === auth()->id());

        if ($recipients->isEmpty()) {
            return;
        }

        $body = "{$pan->reference} — {$pan->employee->name}, {$pan->action_type->label()}.";
        if ($this->isBackward($to)) {
            $return = $pan->returns()->latest('id')->first();
            if ($return !== null) {
                $body .= " Reason: \"{$return->reason}\".";
            }
        }

        Notification::send($recipients, new PanActivity($title, $body, $pan->reference, $context));
    }

    /** @return array{0: Collection, 1: string, 2: string} */
    private function route(PanRequest $pan, PanStatus $to): array
    {
        $manila = $pan->confidentiality_tag === ConfidentialityTag::Manila;
        $preparer = collect([$pan->hrPreparer ?? $pan->form?->preparedBy]);

        return match ($to) {
            PanStatus::WithDivisionHead => [$pan->department->heads()->get(), 'Awaiting your decision', 'Division Head'],
            PanStatus::AwaitingTag => [User::where('is_hr_preparer', true)->get(), 'Ready for tagging', 'HR Preparation'],
            PanStatus::InPreparation => [$preparer, 'Sent back for rework', 'HR Preparation'],
            PanStatus::ReturnedToRequestor => [collect([$pan->requestedBy]), 'Returned to you', 'Requestor'],
            PanStatus::ForConfirmation => [
                $manila ? User::where('is_dh_head', true)->get()
                        : ($pan->divisionHead ? collect([$pan->divisionHead]) : $pan->department->heads()->get()),
                'For your confirmation (HR-prepared)', 'Division Head',
            ],
            PanStatus::ReturnedToPreparer => [$preparer, 'Returned for resolution', 'HR Preparation'],
            PanStatus::ForHrApproval => [User::where('is_hr_approver', true)->get(), 'For HR approval', 'HR Approver'],
            PanStatus::ForFinalApproval => [User::where('is_final_approver', true)->get(), 'For final sign-off', 'Final Approver'],
            PanStatus::Approved => [$preparer, 'Approved — ready to serve', 'HR Preparation'],
            PanStatus::Filed => [collect([$pan->requestedBy]), 'Filed — cycle complete', 'Requestor'],
            // Draft, Served, Withdrawn, Voided, Unserved: nobody is waiting on these.
            default => [collect(), '', ''],
        };
    }

    private function isBackward(PanStatus $to): bool
    {
        return in_array($to, [
            PanStatus::ReturnedToRequestor, PanStatus::ReturnedToPreparer, PanStatus::InPreparation,
        ], true);
    }
}
