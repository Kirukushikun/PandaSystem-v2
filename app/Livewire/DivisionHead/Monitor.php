<?php

namespace App\Livewire\DivisionHead;

use App\Enums\PanStatus;
use App\Livewire\Concerns\ScopesDivisionPans;
use App\Livewire\Concerns\WithPerPage;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Read-only lifecycle view of every PAN under this head's departments (or every
 * Manila PAN for a DH Head) — the "keep monitoring after I've approved it" screen.
 * Deliberately separate from Queue: Queue stays an action list, this stays a
 * browse list. No decision methods live here on purpose.
 */
#[Layout('layouts.app')]
#[Title('Monitor Department — PANDA')]
class Monitor extends Component
{
    use ScopesDivisionPans;
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

    public function render()
    {
        $terminal = [PanStatus::Filed, PanStatus::Withdrawn, PanStatus::Voided, PanStatus::Unserved];

        $pans = $this->divisionScope()
            ->with(['employee', 'requestedBy'])
            ->when($this->search !== '', function (Builder $q) {
                $q->where(fn (Builder $q) => $q
                    ->where('reference', 'like', "%{$this->search}%")
                    ->orWhere('action_type', 'like', '%'.str_replace(' ', '-', strtolower($this->search)).'%')
                    ->orWhereHas('employee', fn (Builder $q) => $q->where('name', 'like', "%{$this->search}%")));
            })
            ->when($this->filter === 'progress', fn (Builder $q) => $q->whereNotIn('status', $terminal))
            ->when($this->filter === 'completed', fn (Builder $q) => $q->whereIn('status', $terminal))
            ->orderByDesc('id')
            ->paginate($this->perPage);

        $byStatus = $this->divisionScope()->select('status')->selectRaw('count(*) as c')
            ->groupBy('status')->pluck('c', 'status');
        $bucket = fn (array $statuses) => collect($statuses)->sum(fn ($s) => $byStatus[$s->value] ?? 0);

        return view('livewire.division-head.monitor', [
            'pans' => $pans,
            'stats' => [
                'total' => $byStatus->sum(),
                'progress' => $byStatus->sum() - $bucket($terminal),
                'completed' => $bucket($terminal),
            ],
            'departments' => auth()->user()->headedDepartments->pluck('name')->implode(', '),
            'isDhHead' => auth()->user()->is_dh_head,
        ]);
    }
}
