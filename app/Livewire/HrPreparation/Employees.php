<?php

namespace App\Livewire\HrPreparation;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Employees — PANDA')]
class Employees extends Component
{
    public function render()
    {
        return view('livewire.hr-preparation.employees');
    }
}
