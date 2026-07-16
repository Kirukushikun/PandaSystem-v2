<?php

namespace App\Livewire\HrApprover;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('HR Approval Queue — PANDA')]
class Queue extends Component
{
    public function render()
    {
        return view('livewire.hr-approver.queue');
    }
}
