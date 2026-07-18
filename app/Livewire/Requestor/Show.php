<?php

namespace App\Livewire\Requestor;

use App\Enums\PanStatus;
use App\Models\PanRequest;
use App\Services\PanWorkflow;
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
            ->with(['employee.department', 'returns.returnedBy'])
            ->firstOrFail();

        $this->authorize('view', $this->panRequest);
    }

    public function withdraw(): void
    {
        $this->authorize('withdraw', $this->panRequest);

        $this->panRequest->update([
            'status' => app(PanWorkflow::class)->apply($this->panRequest->status, 'withdraw'),
        ]);
        $this->js("showToast('{$this->panRequest->reference} withdrawn — kept on record.')");
        $this->redirectRoute('requests.index', navigate: true);
    }

    /** The requestor-facing journey strip: [stages, current label|null]. */
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

        return view('livewire.requestor.show', [
            'stages' => $stages,
            'current' => $current,
            'departments' => auth()->user()->requestorDepartments->pluck('name')->implode(', '),
        ]);
    }
}
