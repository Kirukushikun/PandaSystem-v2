<?php

namespace App\Livewire\DivisionHead;

use App\Enums\PanStatus;
use App\Models\PanRequest;
use App\Services\PanWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\ScopesDivisionPans;
use App\Livewire\Concerns\WithPerPage;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Department Queue — PANDA')]
class Queue extends Component
{
    use ScopesDivisionPans;
    use WithPagination;
    use WithPerPage;

    public string $search = '';

    public string $filter = 'action'; // action | all | completed

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    // Shared reason modal (Return to Requestor / Dispute — both demand a reason)
    public bool $showModal = false;

    public ?int $target = null;

    public string $modalAction = '';

    public string $reason = '';

    public string $details = '';

    public function approve(int $id): void
    {
        $pan = PanRequest::findOrFail($id);
        $this->authorize('approveDivision', $pan);

        $pan->update([
            'status' => app(PanWorkflow::class)->apply($pan->status, 'approve_division', $pan->confidentiality_tag),
            'division_head_id' => auth()->id(),
        ]);
        $this->js("showToast('{$pan->reference} approved — forwarded to HR Preparation.')");
    }

    public function confirmPrepared(int $id): void
    {
        $pan = PanRequest::findOrFail($id);
        $this->authorize('confirm', $pan);

        $pan->update(['status' => app(PanWorkflow::class)->apply($pan->status, 'confirm')]);
        $this->js("showToast('{$pan->reference} confirmed — forwarded to HR Approver.')");
    }

    /** Arms and opens the reason modal — server state, so re-renders can't shut it. */
    public function startReason(int $id, string $action): void
    {
        $this->target = $id;
        $this->modalAction = $action; // return_to_requestor | dispute
        $this->reason = '';
        $this->details = '';
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function submitReason(): void
    {
        $pan = PanRequest::findOrFail($this->target);
        $this->authorize($this->modalAction === 'dispute' ? 'dispute' : 'returnToRequestor', $pan);

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

        $this->js($this->modalAction === 'dispute'
            ? "showToast('{$pan->reference} disputed — sent back to HR Preparer.')"
            : "showToast('{$pan->reference} returned to the Requestor with your reason.')");
        $this->reset('showModal', 'target', 'modalAction', 'reason', 'details');
    }

    public function render()
    {
        $terminal = [PanStatus::Filed, PanStatus::Withdrawn, PanStatus::Voided, PanStatus::Unserved];
        $awaiting = [PanStatus::WithDivisionHead, PanStatus::ForConfirmation];

        $pans = $this->divisionScope()
            ->with(['employee', 'requestedBy'])
            ->when($this->search !== '', function (Builder $q) {
                $q->where(fn (Builder $q) => $q
                    ->where('reference', 'like', "%{$this->search}%")
                    ->orWhere('action_type', 'like', '%'.str_replace(' ', '-', strtolower($this->search)).'%')
                    ->orWhereHas('employee', fn (Builder $q) => $q->where('name', 'like', "%{$this->search}%")));
            })
            ->when($this->filter === 'action', fn (Builder $q) => $q->whereIn('status', $awaiting))
            ->when($this->filter === 'completed', fn (Builder $q) => $q->whereIn('status', $terminal))
            ->orderByDesc('id')
            ->paginate($this->perPage);

        // awaiting/later are pure status buckets — one grouped query covers both;
        // completed also filters on updated_at, so it keeps its own count.
        $byStatus = $this->divisionScope()->select('status')->selectRaw('count(*) as c')
            ->groupBy('status')->pluck('c', 'status');
        $bucket = fn (array $statuses) => collect($statuses)->sum(fn ($s) => $byStatus[$s->value] ?? 0);

        return view('livewire.division-head.queue', [
            'pans' => $pans,
            'modalPan' => $this->target ? PanRequest::find($this->target) : null,
            'stats' => [
                'awaiting' => $bucket($awaiting),
                'later' => $byStatus->sum() - $bucket([...$awaiting, ...$terminal]),
                'completed' => $this->divisionScope()->whereIn('status', $terminal)
                    ->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            ],
            'departments' => auth()->user()->headedDepartments->pluck('name')->implode(', '),
            'isDhHead' => auth()->user()->is_dh_head,
        ]);
    }
}
