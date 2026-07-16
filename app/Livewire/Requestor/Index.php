<?php

namespace App\Livewire\Requestor;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('My PAN Requests — PANDA')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.requestor.index');
    }
}
