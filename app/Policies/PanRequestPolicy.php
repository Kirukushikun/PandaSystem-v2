<?php

namespace App\Policies;

use App\Enums\ConfidentialityTag;
use App\Enums\PanStatus;
use App\Models\PanRequest;
use App\Models\User;

/**
 * The single source of truth for who may see or act on a PAN — checked on EVERY
 * entry point (list scoping, show, attachment download, print), never by hiding
 * buttons. This is what closes v1's direct-link hole.
 *
 * Manila rules: at the division stages only the DH Head (any department) acts;
 * at HR Preparation only HR Head preparers; HR/Final approval have no
 * confidentiality distinction.
 */
class PanRequestPolicy
{
    public function view(User $user, PanRequest $pan): bool
    {
        // Drafts exist only for their author — they're in nobody's queue yet.
        if ($pan->status === PanStatus::Draft) {
            return $this->owns($user, $pan);
        }

        if ($this->owns($user, $pan)) {
            return true;
        }

        if ($pan->confidentiality_tag === ConfidentialityTag::Manila) {
            // Confidential: DH Head (division stages, all departments) or HR Head preparer.
            if ($user->is_dh_head || $user->is_hr_head) {
                return true;
            }
        } else {
            // Routine/untagged: the department's own head(s), and any HR preparer.
            if ($user->is_division_head && $user->headedDepartments()->whereKey($pan->department_id)->exists()) {
                return true;
            }
            if ($user->is_hr_preparer) {
                return true;
            }
        }

        // No confidentiality distinction at the approval stages.
        return $user->is_hr_approver || $user->is_final_approver;
    }

    public function create(User $user): bool
    {
        return $user->is_requestor;
    }

    /** Editing the request body/attachment: own drafts and own returned PANs. */
    public function update(User $user, PanRequest $pan): bool
    {
        return $this->owns($user, $pan)
            && in_array($pan->status, [PanStatus::Draft, PanStatus::ReturnedToRequestor], true);
    }

    /** Drafts may be deleted outright; anything submitted is a record. */
    public function delete(User $user, PanRequest $pan): bool
    {
        return $this->owns($user, $pan) && $pan->status === PanStatus::Draft;
    }

    public function submit(User $user, PanRequest $pan): bool
    {
        return $this->owns($user, $pan) && $pan->status === PanStatus::Draft;
    }

    public function resubmit(User $user, PanRequest $pan): bool
    {
        return $this->owns($user, $pan) && $pan->status === PanStatus::ReturnedToRequestor;
    }

    public function withdraw(User $user, PanRequest $pan): bool
    {
        return $this->owns($user, $pan) && $pan->status === PanStatus::ReturnedToRequestor;
    }

    // Division stages — the deciding head depends on the tag: Manila PANs belong to
    // the DH Head (any department), everything else to the department's own head(s).

    public function approveDivision(User $user, PanRequest $pan): bool
    {
        return $pan->status === PanStatus::WithDivisionHead && $this->headsStageFor($user, $pan);
    }

    public function returnToRequestor(User $user, PanRequest $pan): bool
    {
        return $pan->status === PanStatus::WithDivisionHead && $this->headsStageFor($user, $pan);
    }

    public function confirm(User $user, PanRequest $pan): bool
    {
        return $pan->status === PanStatus::ForConfirmation && $this->headsStageFor($user, $pan);
    }

    public function dispute(User $user, PanRequest $pan): bool
    {
        return $pan->status === PanStatus::ForConfirmation && $this->headsStageFor($user, $pan);
    }

    // HR Preparation stages — Manila PANs may only be worked by HR Head preparers;
    // routine ones by any preparer. Tagging happens BEFORE confidentiality exists,
    // so any preparer may apply the initial tag (what happens next depends on it).

    public function tag(User $user, PanRequest $pan): bool
    {
        return $pan->status === PanStatus::AwaitingTag && $user->is_hr_preparer;
    }

    public function prepare(User $user, PanRequest $pan): bool
    {
        return in_array($pan->status, [PanStatus::InPreparation, PanStatus::ReturnedToPreparer], true)
            && $this->preparesFor($user, $pan);
    }

    public function void(User $user, PanRequest $pan): bool
    {
        return in_array($pan->status, [PanStatus::AwaitingTag, PanStatus::InPreparation, PanStatus::ReturnedToPreparer], true)
            && $this->preparesFor($user, $pan);
    }

    public function markServed(User $user, PanRequest $pan): bool
    {
        return $pan->status === PanStatus::Approved && $this->preparesFor($user, $pan);
    }

    public function markUnserved(User $user, PanRequest $pan): bool
    {
        return $pan->status === PanStatus::Approved && $this->preparesFor($user, $pan);
    }

    public function filePan(User $user, PanRequest $pan): bool
    {
        return $pan->status === PanStatus::Served && $this->preparesFor($user, $pan);
    }

    /** Starting a PAN directly at HR ("Update PAN", origin='hr'). */
    public function createHr(User $user): bool
    {
        return $user->is_hr_preparer;
    }

    // Approval stages — no confidentiality distinction here (spec rule): any
    // HR/Final approver acts on any PAN sitting at their stage.

    public function approveHr(User $user, PanRequest $pan): bool
    {
        return $pan->status === PanStatus::ForHrApproval && $user->is_hr_approver;
    }

    public function returnToPreparer(User $user, PanRequest $pan): bool
    {
        return $pan->status === PanStatus::ForHrApproval && $user->is_hr_approver;
    }

    public function approveFinal(User $user, PanRequest $pan): bool
    {
        return $pan->status === PanStatus::ForFinalApproval && $user->is_final_approver;
    }

    public function rejectFinal(User $user, PanRequest $pan): bool
    {
        return $pan->status === PanStatus::ForFinalApproval && $user->is_final_approver;
    }

    private function preparesFor(User $user, PanRequest $pan): bool
    {
        if ($pan->confidentiality_tag === ConfidentialityTag::Manila) {
            return $user->is_hr_head;
        }

        return $user->is_hr_preparer;
    }

    private function headsStageFor(User $user, PanRequest $pan): bool
    {
        if ($pan->confidentiality_tag === ConfidentialityTag::Manila) {
            return $user->is_dh_head;
        }

        return $user->is_division_head
            && $user->headedDepartments()->whereKey($pan->department_id)->exists();
    }

    private function owns(User $user, PanRequest $pan): bool
    {
        return $user->is_requestor && $pan->requested_by === $user->id;
    }
}
