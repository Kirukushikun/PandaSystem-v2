<?php

namespace App\Livewire\FinalApprover\Concerns;

use App\Enums\EmploymentStatus;
use App\Models\PanRequest;
use App\Services\PanWorkflow;

/**
 * The final sign-off itself, shared by the queue (single + bulk) and the Show
 * view. Regularization auto-finalizes the employee's status to "Regular",
 * overriding any tentative value set earlier (v1 rule). A Lateral Transfer /
 * Change of Position / Promotion that set a "New Department" moves the
 * employee's actual department record here too — confirmed 2026-09-16, after
 * PAN-2026-00061 showed nothing anywhere ever did this, so every subsequent
 * PAN kept inheriting a stale department indefinitely.
 */
trait GivesFinalApproval
{
    protected function giveFinalApproval(PanRequest $pan): void
    {
        $this->authorize('approveFinal', $pan);

        $pan->update([
            'status' => app(PanWorkflow::class)->apply($pan->status, 'approve_final'),
            'final_approver_id' => auth()->id(),
            'approved_at' => now(),
        ]);

        if ($pan->action_type->autoFinalizesToRegular()) {
            $pan->form?->update(['employment_status' => EmploymentStatus::Regular]);
        }

        // Only ever set for the three action types that show the field — see
        // ActionType::mayChangeDepartment() — and only when HR actually picked
        // one; most PANs of those types don't change department at all.
        if ($pan->form?->new_department_id !== null) {
            $pan->employee->update(['department_id' => $pan->form->new_department_id]);
        }
    }
}
