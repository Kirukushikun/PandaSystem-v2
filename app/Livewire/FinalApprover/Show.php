<?php

namespace App\Livewire\FinalApprover;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('View Request — PANDA')]
class Show extends Component
{
    /** Always Request + PAN Details at this stage — everything here is prepared and HR-approved. */
    public string $pan;

    public function mount(string $pan)
    {
        $this->pan = $pan;
    }

    public function render()
    {
        return view('livewire.final-approver.show');
    }
}
