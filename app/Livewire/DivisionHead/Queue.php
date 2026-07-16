<?php

namespace App\Livewire\DivisionHead;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Department Queue — PANDA')]
class Queue extends Component
{
    public function render()
    {
        return view('livewire.division-head.queue');
    }
}
