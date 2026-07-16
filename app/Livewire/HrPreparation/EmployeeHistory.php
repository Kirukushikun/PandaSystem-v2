<?php

namespace App\Livewire\HrPreparation;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('PAN History — PANDA')]
class EmployeeHistory extends Component
{
    /** Employee number from the route, e.g. EMP-10233. Static S. Lim sample until the real build. */
    public string $employee;

    public function mount(string $employee)
    {
        $this->employee = $employee;
    }

    public function render()
    {
        return view('livewire.hr-preparation.employee-history');
    }
}
