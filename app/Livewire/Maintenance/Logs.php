<?php

namespace App\Livewire\Maintenance;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Logs & Audit — PANDA')]
class Logs extends Component
{
    public function render()
    {
        return view('livewire.maintenance.logs');
    }
}
