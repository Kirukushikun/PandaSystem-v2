<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('User Accounts — PANDA')]
class Users extends Component
{
    public function render()
    {
        return view('livewire.admin.users');
    }
}
