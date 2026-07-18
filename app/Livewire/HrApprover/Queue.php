<?php

namespace App\Livewire\HrApprover;

use App\Enums\PanStatus;
use App\Models\PanRequest;
use App\Services\PanWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('HR Approval Queue — PANDA')]
class Queue extends Component
{
    public string $search = '';

    public string $filter = 'awaiting'; // awaiting | later

    // Return-to-preparer reason modal
    public ?int $target = null;

    public string $reason = '';

    public string $details = '';

    public function approve(int $id): void
    {
        $pan = PanRequest::findOrFail($id);
        $this->authorize('approveHr', $pan);

        $pan->update([
            'status' => app(PanWorkflow::class)->apply($pan->status, 'approve_hr'),
            'hr_approver_id' => auth()->id(),
        ]);
        $this->js("showToast('{$pan->reference} approved — forwarded to the Final Approver.')");
    }

    /** Arms the reason modal for a row; the modal itself is opened by data-modal-open. */
    public function startReason(int $id): void
    {
        $this->target = $id;
        $this->reason = '';
        $this->details = '';
        $this->resetErrorBag();
    }

    public function submitReason(): void
    {
        $pan = PanRequest::findOrFail($this->target);
        $this->authorize('returnToPreparer', $pan);

        $this->validate([
            'reason' => 'required|string|max:255',
            'details' => 'required_if:reason,Custom reason…|nullable|string|max:1000',
        ], ['details.required_if' => 'Describe the custom reason in the details field.']);

        $from = $pan->status;
        $to = app(PanWorkflow::class)->apply($from, 'return_to_preparer');

        $pan->update(['status' => $to]);
        $pan->returns()->create([
            'action' => 'return_to_preparer',
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $this->reason,
            'details' => $this->details ?: null,
            'returned_by' => auth()->id(),
        ]);

        $this->js("document.getElementById('reason-modal')?.classList.remove('on')");
        $this->js("showToast('{$pan->reference} returned to the HR Preparer with your reason.')");
        $this->reset('target', 'reason', 'details');
    }

    public function render()
    {
        $later = [PanStatus::ForFinalApproval, PanStatus::Approved, PanStatus::Served, PanStatus::Filed];

        $pans = PanRequest::query()
            ->with(['employee.department', 'form.preparedBy'])
            ->when($this->filter === 'awaiting', fn (Builder $q) => $q->where('status', PanStatus::ForHrApproval))
            ->when($this->filter === 'later', fn (Builder $q) => $q->whereIn('status', $later))
            ->when($this->search !== '', function (Builder $q) {
                $q->where(fn (Builder $q) => $q
                    ->where('reference', 'like', "%{$this->search}%")
                    ->orWhere('action_type', 'like', '%'.str_replace(' ', '-', strtolower($this->search)).'%')
                    ->orWhereHas('employee', fn (Builder $q) => $q->where('name', 'like', "%{$this->search}%")));
            })
            ->orderByDesc('id')
            ->get();

        return view('livewire.hr-approver.queue', [
            'pans' => $pans,
            'modalPan' => $this->target ? PanRequest::find($this->target) : null,
            'stats' => [
                'awaiting' => PanRequest::where('status', PanStatus::ForHrApproval)->count(),
                'withFinal' => PanRequest::where('status', PanStatus::ForFinalApproval)->count(),
                'approved' => PanRequest::whereIn('status', $later)
                    ->whereNotNull('hr_approver_id')
                    ->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count(),
            ],
        ]);
    }
}
