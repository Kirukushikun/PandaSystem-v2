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
    public bool $showModal = false;

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

    /** Arms and opens the reason modal — server state, so re-renders can't shut it. */
    public function startReason(int $id): void
    {
        $this->target = $id;
        $this->reason = '';
        $this->details = '';
        $this->resetErrorBag();
        $this->showModal = true;
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

        $this->js("showToast('{$pan->reference} returned to the HR Preparer with your reason.')");
        $this->reset('showModal', 'target', 'reason', 'details');
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

        // Three stats, one aggregate pass (conditions are mixed, so CASE sums not GROUP BY).
        $laterIn = implode(',', array_fill(0, count($later), '?'));
        $stats = PanRequest::query()->selectRaw("
            sum(case when status = ? then 1 else 0 end) as awaiting,
            sum(case when status = ? then 1 else 0 end) as with_final,
            sum(case when status in ({$laterIn}) and hr_approver_id is not null
                     and updated_at between ? and ? then 1 else 0 end) as approved
        ", [
            PanStatus::ForHrApproval->value,
            PanStatus::ForFinalApproval->value,
            ...array_map(fn ($s) => $s->value, $later),
            now()->startOfMonth(), now()->endOfMonth(),
        ])->toBase()->first();

        return view('livewire.hr-approver.queue', [
            'pans' => $pans,
            'modalPan' => $this->target ? PanRequest::find($this->target) : null,
            'stats' => [
                'awaiting' => (int) $stats->awaiting,
                'withFinal' => (int) $stats->with_final,
                'approved' => (int) $stats->approved,
            ],
        ]);
    }
}
