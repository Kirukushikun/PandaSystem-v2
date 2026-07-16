<?php

namespace App\Livewire\HrApprover;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('View Request — PANDA')]
class Show extends Component
{
    /** Always Request + PAN Details at this stage — everything in the queue is HR-prepared. */
    public string $pan;

    public function mount(string $pan)
    {
        $this->pan = $pan;
    }

    public function render()
    {
        return view('livewire.hr-approver.show');
    }
}
