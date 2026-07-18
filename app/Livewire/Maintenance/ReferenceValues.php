<?php

namespace App\Livewire\Maintenance;

use App\Models\Department;
use App\Models\Farm;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The option lists the rest of the system draws from. A value in use — by
 * employees, users, PANs, or department assignments — can't be deleted;
 * the isInUse() guards on the models are the authority, not the button.
 */
#[Layout('layouts.app')]
#[Title('Reference Values — PANDA')]
class ReferenceValues extends Component
{
    public string $newFarm = '';

    public string $newDept = '';

    public function addFarm(): void
    {
        $this->validate(['newFarm' => 'required|string|max:80|unique:farms,name'], [], ['newFarm' => 'farm name']);

        Farm::create(['name' => trim($this->newFarm)]);
        $this->newFarm = '';
        $this->js("showToast('Farm added.')");
    }

    public function addDept(): void
    {
        $this->validate(['newDept' => 'required|string|max:80|unique:departments,name'], [], ['newDept' => 'department name']);

        Department::create(['name' => trim($this->newDept)]);
        $this->newDept = '';
        $this->js("showToast('Department added.')");
    }

    public function removeFarm(int $id): void
    {
        $farm = Farm::findOrFail($id);
        if ($farm->isInUse()) {
            $this->js("showToast('{$farm->name} is in use — it cannot be deleted.')");

            return;
        }

        $farm->delete();
        $this->js("showToast('{$farm->name} deleted.')");
    }

    public function removeDept(int $id): void
    {
        $department = Department::findOrFail($id);
        if ($department->isInUse()) {
            $this->js("showToast('{$department->name} is in use — it cannot be deleted.')");

            return;
        }

        $department->delete();
        $this->js("showToast('{$department->name} deleted.')");
    }

    public function render()
    {
        return view('livewire.maintenance.reference-values', [
            'farms' => Farm::withCount('employees')->orderBy('name')->get(),
            'departments' => Department::withCount(['employees', 'heads'])->orderBy('name')->get(),
        ]);
    }
}
