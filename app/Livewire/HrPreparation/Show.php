<?php

namespace App\Livewire\HrPreparation;

use App\Enums\PanStatus;
use App\Models\PanRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('View Request — PANDA')]
class Show extends Component
{
    public PanRequest $panRequest;

    public function mount(string $pan): void
    {
        $this->panRequest = PanRequest::where('reference', $pan)
            ->with(['employee.department', 'requestedBy', 'form.preparedBy', 'returns.returnedBy'])
            ->firstOrFail();

        $this->authorize('view', $this->panRequest);
    }

    /** Same journey strip as the other Show views — one truth for stage progress. */
    public function tracker(): array
    {
        $stages = ['Submitted', 'Division Head', 'HR Preparation', 'DH Confirmation', 'HR Approval', 'Final Approval', 'Served', 'Filed'];

        $current = match ($this->panRequest->status) {
            PanStatus::Draft, PanStatus::ReturnedToRequestor => 'Submitted',
            PanStatus::WithDivisionHead => 'Division Head',
            PanStatus::AwaitingTag, PanStatus::InPreparation, PanStatus::ReturnedToPreparer => 'HR Preparation',
            PanStatus::ForConfirmation => 'DH Confirmation',
            PanStatus::ForHrApproval => 'HR Approval',
            PanStatus::ForFinalApproval => 'Final Approval',
            PanStatus::Approved, PanStatus::Served => 'Served',
            PanStatus::Filed => '*',
            PanStatus::Withdrawn, PanStatus::Voided, PanStatus::Unserved => null,
        };

        return [$stages, $current];
    }

    public function render()
    {
        [$stages, $current] = $this->tracker();

        return view('livewire.hr-preparation.show', [
            'pan' => $this->panRequest,
            'form' => $this->panRequest->form,
            'rows' => $this->panRequest->form?->displayRows() ?? [],
            'stages' => $stages,
            'current' => $current,
            'isHrHead' => auth()->user()->is_hr_head,
        ]);
    }
}
