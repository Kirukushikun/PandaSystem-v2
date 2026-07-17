<?php

namespace App\Livewire\Maintenance;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Reference Values — PANDA')]
class ReferenceValues extends Component
{
    /**
     * Live lists (scaffold state only). A value in use by employees or PANs
     * can't be deleted — enforced in the real build by policy + query.
     */
    public array $farms = [
        ['name' => 'San Rafael Farm',      'note' => '214 employees', 'inUse' => true],
        ['name' => 'Sta. Maria Feedmill',  'note' => '121 employees', 'inUse' => true],
        ['name' => 'Main Office',          'note' => '77 employees',  'inUse' => true],
        ['name' => 'Pampanga Grower Site', 'note' => '0 employees',   'inUse' => false],
    ];

    public array $depts = [
        ['name' => 'Broiler Operations',   'note' => '3 heads · 188 employees', 'inUse' => true],
        ['name' => 'Hatchery',             'note' => '1 head · 64 employees',   'inUse' => true],
        ['name' => 'Feedmill',             'note' => '2 heads · 96 employees',  'inUse' => true],
        ['name' => 'Sales & Distribution', 'note' => '1 head · 41 employees',   'inUse' => true],
        ['name' => 'Corporate Office',     'note' => '2 heads · 23 employees',  'inUse' => true],
        ['name' => 'Aqua Ventures',        'note' => '0 heads · 0 employees',   'inUse' => false],
    ];

    public string $newFarm = '';
    public string $newDept = '';

    public function addFarm(): void
    {
        $name = trim($this->newFarm);
        if ($name === '') {
            $this->js("showToast('Type a name first.')");
            return;
        }
        $this->farms[] = ['name' => $name, 'note' => '0 employees', 'inUse' => false];
        $this->newFarm = '';
        $this->js("showToast('Value added (UI scaffold — nothing is persisted yet).')");
    }

    public function addDept(): void
    {
        $name = trim($this->newDept);
        if ($name === '') {
            $this->js("showToast('Type a name first.')");
            return;
        }
        $this->depts[] = ['name' => $name, 'note' => '0 heads · 0 employees', 'inUse' => false];
        $this->newDept = '';
        $this->js("showToast('Value added (UI scaffold — nothing is persisted yet).')");
    }

    public function removeFarm(int $i): void
    {
        if (isset($this->farms[$i]) && ! $this->farms[$i]['inUse']) {
            array_splice($this->farms, $i, 1);
            $this->js("showToast('Value deleted (UI scaffold — nothing is persisted yet).')");
        }
    }

    public function removeDept(int $i): void
    {
        if (isset($this->depts[$i]) && ! $this->depts[$i]['inUse']) {
            array_splice($this->depts, $i, 1);
            $this->js("showToast('Value deleted (UI scaffold — nothing is persisted yet).')");
        }
    }

    public function render()
    {
        return view('livewire.maintenance.reference-values');
    }
}
