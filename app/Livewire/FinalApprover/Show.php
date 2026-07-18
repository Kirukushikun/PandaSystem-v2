<?php

namespace App\Livewire\FinalApprover;

use App\Livewire\FinalApprover\Concerns\GivesFinalApproval;
use App\Models\PanRequest;
use App\Services\PanWorkflow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('View Request — PANDA')]
class Show extends Component
{
    use GivesFinalApproval;

    public PanRequest $panRequest;

    // Reject reason modal
    public bool $showModal = false;

    public string $reason = '';

    public string $details = '';

    public function mount(string $pan): void
    {
        $this->panRequest = PanRequest::where('reference', $pan)
            ->with(['employee.department', 'requestedBy', 'form.preparedBy', 'hrApprover', 'returns.returnedBy'])
            ->firstOrFail();

        $this->authorize('view', $this->panRequest);
    }

    public function approve(): void
    {
        $this->giveFinalApproval($this->panRequest);

        $this->js($this->panRequest->action_type->autoFinalizesToRegular()
            ? "showToast('Final approval given — status auto-finalized to Regular.')"
            : "showToast('Final approval given — {$this->panRequest->reference} moves on to serving.')");
        $this->redirectRoute('final-approval.queue', navigate: true);
    }

    public function submitReject(): void
    {
        $this->authorize('rejectFinal', $this->panRequest);

        $this->validate([
            'reason' => 'required|string|max:255',
            'details' => 'required_if:reason,Custom reason…|nullable|string|max:1000',
        ], ['details.required_if' => 'Describe the custom reason in the details field.']);

        $from = $this->panRequest->status;
        $to = app(PanWorkflow::class)->apply($from, 'reject_final');

        $this->panRequest->update(['status' => $to]);
        $this->panRequest->returns()->create([
            'action' => 'reject_final',
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $this->reason,
            'details' => $this->details ?: null,
            'returned_by' => auth()->id(),
        ]);

        $this->js("showToast('{$this->panRequest->reference} rejected — returned to HR Preparation with your reason.')");
        $this->redirectRoute('final-approval.queue', navigate: true);
    }

    public function render()
    {
        return view('livewire.final-approver.show', [
            'pan' => $this->panRequest,
            'form' => $this->panRequest->form,
            'rows' => $this->panRequest->form?->displayRows() ?? [],
        ]);
    }
}
