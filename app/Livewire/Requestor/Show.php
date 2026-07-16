<?php

namespace App\Livewire\Requestor;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('View Request — PANDA')]
class Show extends Component
{
    /** PAN reference from the route, e.g. PAN-2026-00347. Static sample body until the real build. */
    public string $pan;

    public function mount(string $pan)
    {
        $this->pan = $pan;
    }

    public function render()
    {
        return view('livewire.requestor.show');
    }
}
