<?php

namespace App\Livewire\Requestor;

use App\Enums\PanStatus;
use App\Models\PanRequest;
use App\Services\PanWorkflow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('My PAN Requests — PANDA')]
class Index extends Component
{
    public string $search = '';

    public string $filter = 'all'; // all | progress | completed

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
            ->orderByDesc('id')
            ->get();

        return view('livewire.requestor.index', [
            'pans' => $pans,
            'stats' => [
                'total' => (clone $base)->count(),
                'progress' => (clone $base)->ongoing()->count(),
                'returned' => (clone $base)->where('status', PanStatus::ReturnedToRequestor)->count(),
                'completed' => (clone $base)->whereIn('status', [
                    PanStatus::Filed, PanStatus::Withdrawn, PanStatus::Voided, PanStatus::Unserved,
                ])->count(),
            ],
            'departments' => auth()->user()->requestorDepartments->pluck('name')->implode(', '),
        ]);
    }
}
