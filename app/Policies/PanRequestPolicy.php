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

    private function owns(User $user, PanRequest $pan): bool
    {
        return $user->is_requestor && $pan->requested_by === $user->id;
    }
}
