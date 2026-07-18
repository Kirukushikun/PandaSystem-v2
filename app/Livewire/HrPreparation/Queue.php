<?php

namespace App\Livewire\HrPreparation;

use App\Enums\ConfidentialityTag;
use App\Enums\PanStatus;
use App\Models\PanRequest;
use App\Services\PanWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Preparation Queue — PANDA')]
class Queue extends Component
{
    public string $search = '';

    public string $filter = 'all'; // all | prepare | approval | serve

    // Shared reason modal (Void / Mark Unserved — both demand a reason)
    public bool $showModal = false;

    public ?int $target = null;

    public string $modalAction = '';

    public string $reason = '';

    public string $details = '';

    public function markServed(int $id): void
    {
        $pan = PanRequest::findOrFail($id);
        $this->authorize('markServed', $pan);

        $pan->update(['status' => app(PanWorkflow::class)->apply($pan->status, 'mark_served')]);
        $this->js("showToast('{$pan->reference} marked as Served.')");
    }

    public function filePan(int $id): void
    {
        $pan = PanRequest::findOrFail($id);
        $this->authorize('filePan', $pan);

        $pan->update([
            'status' => app(PanWorkflow::class)->apply($pan->status, 'file'),
            'filed_at' => now(),
        ]);
        $this->js("showToast('{$pan->reference} filed — the cycle is complete.')");
    }

    /** Arms and opens the reason modal — server state, so re-renders can't shut it. */
    public function startReason(int $id, string $action): void
    {
        $this->target = $id;
        $this->modalAction = $action; // void | mark_unserved
        $this->reason = '';
        $this->details = '';
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function submitReason(): void
    {
        $pan = PanRequest::findOrFail($this->target);
        $this->authorize($this->modalAction === 'void' ? 'void' : 'markUnserved', $pan);

        $this->validate([
            'reason' => 'required|string|max:255',
            'details' => 'required_if:reason,Custom reason…|nullable|string|max:1000',
        ], ['details.required_if' => 'Describe the custom reason in the details field.']);

        $from = $pan->status;
        $to = app(PanWorkflow::class)->apply($from, $this->modalAction);

        $pan->update(['status' => $to]);
        $pan->returns()->create([
            'action' => $this->modalAction,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $this->reason,
            'details' => $this->details ?: null,
            'returned_by' => auth()->id(),
        ]);

        $this->js($this->modalAction === 'void'
            ? "showToast('{$pan->reference} voided — kept on record with the reason.')"
            : "showToast('{$pan->reference} marked Unserved with the reason on record.')");
        $this->reset('showModal', 'target', 'modalAction', 'reason', 'details');
    }

    /**
     * Everything past division approval. Manila-tagged rows exist only for
     * HR Head preparers — ordinary preparers never see them (v1 lesson: this
     * is the policy's rule; the query just mirrors it for list scoping).
     */
    protected function scope(): Builder
    {
        return PanRequest::query()
            ->whereNotIn('status', [
                PanStatus::Draft->value,
                PanStatus::WithDivisionHead->value,
                PanStatus::ReturnedToRequestor->value,
                PanStatus::Withdrawn->value,
            ])
            ->when(! auth()->user()->is_hr_head, fn (Builder $q) => $q
                ->where('confidentiality_tag', '!=', ConfidentialityTag::Manila->value));
    }

    public function render()
    {
        $prepare = [PanStatus::AwaitingTag, PanStatus::InPreparation, PanStatus::ReturnedToPreparer];
        $approval = [PanStatus::ForConfirmation, PanStatus::ForHrApproval, PanStatus::ForFinalApproval];
        $serve = [PanStatus::Approved, PanStatus::Served];

        $pans = $this->scope()
            ->with(['employee.department', 'form'])
            ->when($this->search !== '', function (Builder $q) {
                $q->where(fn (Builder $q) => $q
                    ->where('reference', 'like', "%{$this->search}%")
                    ->orWhere('action_type', 'like', '%'.str_replace(' ', '-', strtolower($this->search)).'%')
                    ->orWhereHas('employee', fn (Builder $q) => $q->where('name', 'like', "%{$this->search}%")));
            })
            ->when($this->filter === 'prepare', fn (Builder $q) => $q->whereIn('status', $prepare))
            ->when($this->filter === 'approval', fn (Builder $q) => $q->whereIn('status', $approval))
            ->when($this->filter === 'serve', fn (Builder $q) => $q->whereIn('status', $serve))
            ->orderByDesc('id')
            ->get();

        return view('livewire.hr-preparation.queue', [
            'pans' => $pans,
            'modalPan' => $this->target ? PanRequest::find($this->target) : null,
            'stats' => [
                'prepare' => $this->scope()->whereIn('status', [PanStatus::AwaitingTag, PanStatus::InPreparation])->count(),
                'returned' => $this->scope()->where('status', PanStatus::ReturnedToPreparer)->count(),
                'serve' => $this->scope()->whereIn('status', $serve)->count(),
            ],
            'isHrHead' => auth()->user()->is_hr_head,
        ]);
    }
}
