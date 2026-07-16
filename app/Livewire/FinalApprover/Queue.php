<?php

namespace App\Livewire\FinalApprover;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Final Approval Queue — PANDA')]
class Queue extends Component
{
    public function render()
    {
        return view('livewire.final-approver.queue');
    }
}
