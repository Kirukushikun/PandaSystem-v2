<?php

namespace App\Livewire\Maintenance;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Maintenance — PANDA')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.maintenance.index');
    }
}
