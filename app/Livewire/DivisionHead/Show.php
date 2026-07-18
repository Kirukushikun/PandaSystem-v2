<?php

namespace App\Livewire\DivisionHead;

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

    // Reason modal (Return to Requestor while awaiting decision; Dispute while confirming)
    public string $reason = '';

    public string $details = '';

    public function mount(string $pan): void
    {
        $this->panRequest = PanRequest::where('reference', $pan)
            ->with(['employee.department', 'requestedBy', 'form.preparedBy'])
            ->firstOrFail();

        $this->authorize('view', $this->panRequest);
    }

    public function approve(): void
    {
        $this->authorize('approveDivision', $this->panRequest);

        $this->panRequest->update([
            'status' => app(PanWorkflow::class)->apply($this->panRequest->status, 'approve_division'),
            'division_head_id' => auth()->id(),
        ]);
        $this->js("showToast('{$this->panRequest->reference} approved — forwarded to HR Preparation.')");
        $this->redirectRoute('division.queue', navigate: true);
    }

    public function confirmPrepared(): void
    {
        $this->authorize('confirm', $this->panRequest);

        $this->panRequest->update([
            'status' => app(PanWorkflow::class)->apply($this->panRequest->status, 'confirm'),
        ]);
        $this->js("showToast('{$this->panRequest->reference} confirmed — forwarded to HR Approver.')");
        $this->redirectRoute('division.queue', navigate: true);
    }

    public function submitReason(): void
    {
        $action = $this->panRequest->status === PanStatus::ForConfirmation ? 'dispute' : 'return_to_requestor';
        $this->authorize($action === 'dispute' ? 'dispute' : 'returnToRequestor', $this->panRequest);

        $this->validate([
            'reason' => 'required|string|max:255',
            'details' => 'required_if:reason,Custom reason…|nullable|string|max:1000',
        ], ['details.required_if' => 'Describe the custom reason in the details field.']);

        $from = $this->panRequest->status;
        $to = app(PanWorkflow::class)->apply($from, $action);

        $this->panRequest->update(['status' => $to]);
        $this->panRequest->returns()->create([
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $this->reason,
            'details' => $this->details ?: null,
            'returned_by' => auth()->id(),
        ]);

        $this->js($action === 'dispute'
            ? "showToast('{$this->panRequest->reference} disputed — sent back to HR Preparer.')"
            : "showToast('{$this->panRequest->reference} returned to the Requestor with your reason.')");
        $this->redirectRoute('division.queue', navigate: true);
    }

    /**
     * action_reference JSON → x-pan.prepared-details rows. Fixed fields get their
     * display labels; unknown fields (dynamic allowances) render their own name.
     */
    public function referenceRows(): array
    {
        return array_map(fn (array $row) => [
            'label' => match ($row['field']) {
                'section' => 'Section',
                'place' => 'Place of Assignment',
                'head' => 'Immediate Head',
                'position' => 'Position',
                'joblevel' => 'Job Level',
                'basic' => 'Basic Pay',
                'leavecredits' => 'Leave Credits',
                default => $row['field'],
            },
            'from' => $row['field'] === 'basic' ? '₱ '.$row['from'] : $row['from'],
            'to' => $row['field'] === 'basic' ? '₱ '.$row['to'] : $row['to'],
            'chg' => $row['from'] !== $row['to'],
            'num' => $row['field'] === 'basic',
        ], $this->panRequest->form->action_reference ?? []);
    }

    /** Same journey strip as the Requestor's Show — one truth for stage progress. */
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

        return view('livewire.division-head.show', [
            'pan' => $this->panRequest,
            'form' => $this->panRequest->form,
            'rows' => $this->referenceRows(),
            'stages' => $stages,
            'current' => $current,
            'departments' => auth()->user()->is_dh_head
                ? 'DH Head — all departments'
                : auth()->user()->headedDepartments->pluck('name')->implode(', '),
        ]);
    }
}
