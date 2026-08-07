<?php

namespace App\Livewire\Requestor;

use App\Enums\PanStatus;
use App\Models\PanRequest;
use App\Services\PanWorkflow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\FiltersPanRequests;
use App\Livewire\Concerns\WithPerPage;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('My PAN Requests — PANDA')]
class Index extends Component
{
    use FiltersPanRequests;
    use WithPagination;
    use WithPerPage;

    public string $search = '';

    public string $filter = 'all'; // all | progress | completed

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function deleteDraft(int $id): void
    {
        $pan = PanRequest::findOrFail($id);
        $this->authorize('delete', $pan);

        $pan->delete();
        $this->js("showToast('Draft {$pan->reference} deleted.')");
    }

    public function withdraw(int $id): void
    {
        $pan = PanRequest::findOrFail($id);
        $this->authorize('withdraw', $pan);

        $pan->update(['status' => app(PanWorkflow::class)->apply($pan->status, 'withdraw')]);
        $this->js("showToast('{$pan->reference} withdrawn — kept on record.')");
    }

    public function render()
    {
        $base = PanRequest::where('requested_by', auth()->id());

        $pans = (clone $base)
            ->with('employee.department')
            ->when($this->search !== '', function ($q) {
                $q->where(fn ($q) => $q
                    ->where('reference', 'like', "%{$this->search}%")
                    ->orWhere('action_type', 'like', '%'.str_replace(' ', '-', strtolower($this->search)).'%')
                    ->orWhereHas('employee', fn ($q) => $q->where('name', 'like', "%{$this->search}%")));
            })
            ->when($this->filter === 'progress', fn ($q) => $q->ongoing())
            ->when($this->filter === 'completed', fn ($q) => $q->whereIn('status', [
                PanStatus::Filed, PanStatus::Withdrawn, PanStatus::Voided, PanStatus::Unserved,
            ]))
            ->tap(fn ($q) => $this->applyPanFiltersAndSort($q))
            ->paginate($this->perPage);

        // One grouped query instead of four counts; the buckets are summed in PHP.
        $byStatus = (clone $base)->select('status')->selectRaw('count(*) as c')
            ->groupBy('status')->pluck('c', 'status');
        $bucket = fn (array $statuses) => collect($statuses)->sum(fn ($s) => $byStatus[$s->value] ?? 0);
        $terminal = [PanStatus::Filed, PanStatus::Withdrawn, PanStatus::Voided, PanStatus::Unserved];

        return view('livewire.requestor.index', [
            'pans' => $pans,
            'stats' => [
                'total' => $byStatus->sum(),
                'progress' => $byStatus->sum() - $bucket([PanStatus::Draft, ...$terminal]),
                'returned' => $byStatus[PanStatus::ReturnedToRequestor->value] ?? 0,
                'completed' => $bucket($terminal),
            ],
            'departments' => auth()->user()->requestorDepartments->pluck('name')->implode(', '),
        ]);
    }
}
