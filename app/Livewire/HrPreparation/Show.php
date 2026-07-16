<?php

namespace App\Livewire\HrPreparation;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('View Request — PANDA')]
class Show extends Component
{
    /** For HR Preparer (and the approver roles) this view is ALWAYS Request + PAN Details —
     *  requests in these queues have already been processed, so there is no request-only variant. */
    public string $pan;

    public function mount(string $pan)
    {
        $this->pan = $pan;
    }

    public function render()
    {
        return view('livewire.hr-preparation.show');
    }
}
