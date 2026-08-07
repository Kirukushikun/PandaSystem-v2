<?php

namespace App\Livewire\HrPreparation;

use App\Enums\ConfidentialityTag;
use App\Enums\PanStatus;
use App\Models\PanRequest;
use App\Services\PanWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\FiltersPanRequests;
use App\Livewire\Concerns\WithPerPage;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Preparation Queue — PANDA')]
class Queue extends Component
{
    use FiltersPanRequests;
    use WithPagination;
    use WithPerPage;

    public string $search = '';

    public string $filter = 'all'; // all | prepare | approval | serve

    public string $tagFilter = 'all'; // all | manila | tarlac | untagged

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTagFilter(): void
    {
        $this->resetPage();
    }

    /** Overrides FiltersPanRequests' version to also reset tagFilter, which lives here, not the trait. */
    public function clearPanFilters(): void
    {
        $this->tagFilter = 'all';
        $this->sort = 'newest';
        $this->actionTypeFilter = null;
        $this->departmentFilter = null;
        $this->resetPage();
    }

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

        $pan->update([
            'status' => app(PanWorkflow::class)->apply($pan->status, 'mark_served'),
            'served_at' => now(),
        ]);
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
                ->where('confidentiality_tag', '!=', ConfidentialityTag::Manila->value))
            // 'untagged' here means the enum's Untagged case — AwaitingTag PANs, since
            // tagging is what moves them out of that status in the first place.
            ->when($this->tagFilter !== 'all', fn (Builder $q) => $q
                ->where('confidentiality_tag', $this->tagFilter))
            // WHERE-only (type of action, department) — safe to share with the grouped
            // stats query below; sort is an ORDER BY and is applied separately, only to
            // the paginated list, so it never risks tripping GROUP BY over a column
            // that isn't part of it.
            ->tap(fn (Builder $q) => $this->applyPanFilters($q));
    }

    public function render()
    {
        $prepare = [PanStatus::AwaitingTag, PanStatus::InPreparation, PanStatus::ReturnedToPreparer];
        $approval = [PanStatus::ForConfirmation, PanStatus::ForHrApproval, PanStatus::ForFinalApproval];
        $serve = [PanStatus::Approved, PanStatus::Served];

        $pans = $this->scope()
            ->with(['employee.department', 'form', 'latestReturn'])
            ->when($this->search !== '', function (Builder $q) {
                $q->where(fn (Builder $q) => $q
                    ->where('reference', 'like', "%{$this->search}%")
                    ->orWhere('action_type', 'like', '%'.str_replace(' ', '-', strtolower($this->search)).'%')
                    ->orWhereHas('employee', fn (Builder $q) => $q->where('name', 'like', "%{$this->search}%")));
            })
            ->when($this->filter === 'prepare', fn (Builder $q) => $q->whereIn('status', $prepare))
            ->when($this->filter === 'approval', fn (Builder $q) => $q->whereIn('status', $approval))
            ->when($this->filter === 'serve', fn (Builder $q) => $q->whereIn('status', $serve))
            ->tap(fn (Builder $q) => $this->applyPanSort($q))
            ->paginate($this->perPage);

        // All three stats are status buckets over the same scope — one grouped query.
        $byStatus = $this->scope()->select('status')->selectRaw('count(*) as c')
            ->groupBy('status')->pluck('c', 'status');
        $bucket = fn (array $statuses) => collect($statuses)->sum(fn ($s) => $byStatus[$s->value] ?? 0);

        // InPreparation also catches DH-disputed and Final-Approver-rejected PANs, which
        // are really "returned" too — pull those out of the plain-prepare count so the
        // stat matches the pill the row actually shows.
        $bounced = $this->scope()
            ->where('status', PanStatus::InPreparation)
            ->whereHas('latestReturn', fn (Builder $q) => $q
                ->where('to_status', PanStatus::InPreparation)
                ->whereIn('action', ['dispute', 'reject_final']))
            ->count();

        return view('livewire.hr-preparation.queue', [
            'pans' => $pans,
            'modalPan' => $this->target ? PanRequest::find($this->target) : null,
            'stats' => [
                'prepare' => $bucket([PanStatus::AwaitingTag, PanStatus::InPreparation]) - $bounced,
                'returned' => $bucket([PanStatus::ReturnedToPreparer]) + $bounced,
                'serve' => $bucket($serve),
            ],
            'isHrHead' => auth()->user()->is_hr_head,
        ]);
    }
}
