<?php

namespace App\Livewire\FinalApprover\Concerns;

use App\Enums\EmploymentStatus;
use App\Models\PanRequest;
use App\Services\PanWorkflow;

/**
 * The final sign-off itself, shared by the queue (single + bulk) and the Show
 * view. Regularization auto-finalizes the employee's status to "Regular",
 * overriding any tentative value set earlier (v1 rule).
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
    }
}
