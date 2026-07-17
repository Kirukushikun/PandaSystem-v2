<?php

namespace App\Livewire\FinalApprover;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Final Sign-off — PANDA')]
class Queue extends Component
{
    /**
     * Mockup sample rows — live state, so approving actually removes them from the
     * queue and the empty state becomes reachable. Real build: the queue query.
     */
    public array $rows = [
        ['ref' => 'PAN-2026-00335', 'name' => 'G. Padilla', 'dept' => 'Sales & Distribution', 'type' => 'Regularization', 'eff' => 'Aug 1, 2026',  'pay' => '19,000 → 21,500'],
        ['ref' => 'PAN-2026-00327', 'name' => 'B. Estrada', 'dept' => 'Hatchery',             'type' => 'Regularization', 'eff' => 'Aug 1, 2026',  'pay' => '18,200 → 20,400'],
        ['ref' => 'PAN-2026-00325', 'name' => 'F. Domingo', 'dept' => 'Feedmill',             'type' => 'Regularization', 'eff' => 'Aug 1, 2026',  'pay' => '18,900 → 21,100'],
        ['ref' => 'PAN-2026-00322', 'name' => 'S. Lim',     'dept' => 'Feedmill',             'type' => 'Wage Order',     'eff' => 'Jul 15, 2026', 'pay' => '610/day → 645/day'],
        ['ref' => 'PAN-2026-00318', 'name' => 'E. Garcia',  'dept' => 'Broiler Operations',   'type' => 'Promotion',      'eff' => 'Aug 16, 2026', 'pay' => '28,100 → 31,600'],
    ];

    /** Bulk selection state — the mockup shows the first three Regularizations preselected. */
    public array $selected = ['PAN-2026-00335', 'PAN-2026-00327', 'PAN-2026-00325'];

    public function toggleAll()
    {
        $all = array_column($this->rows, 'ref');
        $this->selected = count($this->selected) === count($all) ? [] : $all;
    }

    /** "Select all of type…" — replaces the selection with every row of that action type. */
    public function selectType(string $type)
    {
        if ($type === '') {
            return;
        }
        $this->selected = array_column(
            array_filter($this->rows, fn ($row) => $row['type'] === $type),
            'ref'
        );
    }

    public function approveSelected()
    {
        $count = count($this->selected);
        if ($count === 0) {
            $this->js("showToast('Select at least one PAN first.')");

            return;
        }
        $this->rows = array_values(array_filter($this->rows, fn ($row) => ! in_array($row['ref'], $this->selected)));
        $this->selected = [];
        $this->js("showToast('{$count} PAN(s) approved and cleared from the queue (UI scaffold — nothing is persisted yet). Regularizations auto-finalize status to Regular.')");
    }

    /** Approving one row directly (the row's filled verb) also clears it. */
    public function approveOne(string $ref)
    {
        $this->rows = array_values(array_filter($this->rows, fn ($row) => $row['ref'] !== $ref));
        $this->selected = array_values(array_diff($this->selected, [$ref]));
        $this->js("showToast('Final approval given — {$ref} cleared from the queue (UI scaffold — nothing is persisted yet).')");
    }

    public function render()
    {
        return view('livewire.final-approver.queue');
    }
}
