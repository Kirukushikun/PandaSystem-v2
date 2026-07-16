<?php

namespace App\Livewire\Requestor;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('New PAN Request — PANDA')]
class Form extends Component
{
    public function render()
    {
        return view('livewire.requestor.form');
    }
}
