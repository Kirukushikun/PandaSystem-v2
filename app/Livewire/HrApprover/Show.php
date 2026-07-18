<?php

namespace App\Livewire\HrApprover;

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

    // Return-to-preparer reason modal
    public string $reason = '';

    public string $details = '';

    public function mount(string $pan): void
    {
        $this->panRequest = PanRequest::where('reference', $pan)
            ->with(['employee.department', 'requestedBy', 'form.preparedBy', 'returns.returnedBy'])
            ->firstOrFail();

        $this->authorize('view', $this->panRequest);
    }

    public function approve(): void
    {
        $this->authorize('approveHr', $this->panRequest);

        $this->panRequest->update([
            'status' => app(PanWorkflow::class)->apply($this->panRequest->status, 'approve_hr'),
            'hr_approver_id' => auth()->id(),
        ]);
        $this->js("showToast('{$this->panRequest->reference} approved — forwarded to the Final Approver.')");
        $this->redirectRoute('hr-approval.queue', navigate: true);
    }

    public function submitReason(): void
    {
        $this->authorize('returnToPreparer', $this->panRequest);

        $this->validate([
            'reason' => 'required|string|max:255',
            'details' => 'required_if:reason,Custom reason…|nullable|string|max:1000',
        ], ['details.required_if' => 'Describe the custom reason in the details field.']);

        $from = $this->panRequest->status;
        $to = app(PanWorkflow::class)->apply($from, 'return_to_preparer');

        $this->panRequest->update(['status' => $to]);
        $this->panRequest->returns()->create([
            'action' => 'return_to_preparer',
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $this->reason,
            'details' => $this->details ?: null,
            'returned_by' => auth()->id(),
        ]);

        $this->js("showToast('{$this->panRequest->reference} returned to the HR Preparer with your reason.')");
        $this->redirectRoute('hr-approval.queue', navigate: true);
    }

    public function render()
    {
        return view('livewire.hr-approver.show', [
            'pan' => $this->panRequest,
            'form' => $this->panRequest->form,
            'rows' => $this->panRequest->form?->displayRows() ?? [],
        ]);
    }
}
