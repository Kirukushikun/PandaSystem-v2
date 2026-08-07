<?php

namespace App\Livewire\FinalApprover;

use App\Enums\PanStatus;
use App\Livewire\Concerns\FiltersPanRequests;
use App\Livewire\FinalApprover\Concerns\GivesFinalApproval;
use App\Models\PanRequest;
use App\Models\PanReturn;
use App\Services\PanWorkflow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Final Sign-off — PANDA')]
class Queue extends Component
{
    use FiltersPanRequests;
    use GivesFinalApproval;

    /** Bulk selection — PAN ids as strings (checkbox values dehydrate as strings). */
    public array $selected = [];

    // Reject reason modal; empty targets = "reject selected"
    public bool $showModal = false;

    public array $rejectTargets = [];

    public string $reason = '';

    public string $details = '';

    protected function queue()
    {
        return PanRequest::where('status', PanStatus::ForFinalApproval)
            ->with(['employee.department', 'form'])
            ->tap(fn ($q) => $this->applyPanFiltersAndSort($q))
            ->get();
    }

    public function toggleAll(): void
    {
        $all = $this->queue()->pluck('id')->map(fn (int $id) => (string) $id)->all();
        $this->selected = count($this->selected) === count($all) ? [] : $all;
    }

    /** "Select all of type…" — replaces the selection with every row of that action type. */
    public function selectType(string $type): void
    {
        if ($type === '') {
            return;
        }
        $this->selected = $this->queue()
            ->where('action_type.value', $type)
            ->pluck('id')->map(fn (int $id) => (string) $id)->values()->all();
    }

    public function approveOne(int $id): void
    {
        $pan = PanRequest::findOrFail($id);
        $this->giveFinalApproval($pan);

        $this->selected = array_values(array_diff($this->selected, [(string) $id]));
        $this->js("showToast('Final approval given — {$pan->reference} moves on to serving.')");
    }

    public function approveSelected(): void
    {
        if ($this->selected === []) {
            $this->js("showToast('Select at least one PAN first.')");

            return;
        }

        $pans = PanRequest::findMany($this->selected);
        foreach ($pans as $pan) {
            $this->giveFinalApproval($pan);
        }

        $count = $pans->count();
        $this->selected = [];
        $this->js("showToast('{$count} PAN(s) given final approval — Regularizations auto-finalized to Regular.')");
    }

    /** Arms the reject modal — for one row, or for the current selection when id is null. */
    public function startReject(?int $id = null): void
    {
        $this->rejectTargets = $id !== null ? [(string) $id] : $this->selected;
        $this->reason = '';
        $this->details = '';
        $this->resetErrorBag();

        if ($this->rejectTargets === []) {
            $this->js("showToast('Select at least one PAN first.')");

            return;
        }

        $this->showModal = true; // server state — re-renders can't shut it
    }

    public function submitReject(): void
    {
        if ($this->rejectTargets === []) {
            return;
        }

        $this->validate([
            'reason' => 'required|string|max:255',
            'details' => 'required_if:reason,Custom reason…|nullable|string|max:1000',
        ], ['details.required_if' => 'Describe the custom reason in the details field.']);

        $pans = PanRequest::findMany($this->rejectTargets);
        foreach ($pans as $pan) {
            $this->authorize('rejectFinal', $pan);

            $from = $pan->status;
            $to = app(PanWorkflow::class)->apply($from, 'reject_final');

            $pan->update(['status' => $to]);
            $pan->returns()->create([
                'action' => 'reject_final',
                'from_status' => $from,
                'to_status' => $to,
                'reason' => $this->reason,
                'details' => $this->details ?: null,
                'returned_by' => auth()->id(),
            ]);
        }

        $count = $pans->count();
        $this->selected = array_values(array_diff($this->selected, $this->rejectTargets));
        $this->js("showToast('{$count} PAN(s) rejected — returned to HR Preparation with your reason.')");
        $this->reset('showModal', 'rejectTargets', 'reason', 'details');
    }

    public function render()
    {
        $pans = $this->queue();

        return view('livewire.final-approver.queue', [
            'pans' => $pans,
            'typeCounts' => $pans->groupBy(fn (PanRequest $pan) => $pan->action_type->value)
                ->map->count(),
            'stats' => [
                'awaiting' => $pans->count(),
                'approved' => PanRequest::whereNotNull('final_approver_id')
                    ->whereIn('status', [PanStatus::Approved, PanStatus::Served, PanStatus::Filed, PanStatus::Unserved])
                    ->whereBetween('updated_at', [now()->startOfQuarter(), now()->endOfQuarter()])
                    ->count(),
                'rejected' => PanReturn::where('action', 'reject_final')
                    ->whereBetween('created_at', [now()->startOfQuarter(), now()->endOfQuarter()])
                    ->count(),
            ],
        ]);
    }
}
