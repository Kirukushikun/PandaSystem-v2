<?php

namespace App\Livewire\HrPreparation;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Prepare PAN — PANDA')]
class PrepareForm extends Component
{
    public string $pan;

    /** Simulation controls, ported from the mockup. In the real build the role comes from the
     *  signed-in account, and tagging Manila as a normal preparer redirects them out and revokes
     *  their View access; here we only lock/unlock the form sections. */
    public string $role = 'normal';   // normal | head
    public string $tag = 'none';      // none | tarlac | manila

    /** Previous-PAN "See more" inline summary. */
    public bool $showPrev = false;

    /** Dynamic allowance rows of the Action Reference (labels only — values are typed in the UI). */
    public array $allowances = [];
    public string $allowanceType = 'Communication Allowance';

    public const ALLOWANCE_TYPES = [
        'Communication Allowance', 'Meal Allowance', 'Living Allowance',
        'Transportation Allowance', 'Clothing Allowance', 'Fuel Allowance',
        'Management Allowance', 'Developmental Assignment Allowance',
        'Professional Allowance', 'Interim Allowance', 'Training Allowance',
        'Mancom Allowance', 'Allowance (generic)',
    ];

    public function mount(string $pan)
    {
        $this->pan = $pan;
    }

    public function setRole(string $role)
    {
        $this->role = $role;
    }

    public function togglePrev()
    {
        $this->showPrev = ! $this->showPrev;
    }

    public function addAllowance()
    {
        $this->allowances[] = $this->allowanceType;
    }

    public function removeAllowance(int $index)
    {
        unset($this->allowances[$index]);
        $this->allowances = array_values($this->allowances);
    }

    /** The four tag/role outcomes from the mockup's simulation. */
    public function lockState(): array
    {
        if ($this->tag === 'none') {
            return [true, 'note info', 'i',
                'Locked — tag the request (Tarlac or Manila) before preparation can begin. Any preparer may apply the initial tag.'];
        }
        if ($this->tag === 'manila' && $this->role === 'normal') {
            return [true, 'note manila', '●',
                '<b>Locked — tagged Manila.</b>&nbsp;Only an HR Head Preparer can view and prepare this request. In the actual project you are returned to the list and your View button becomes permanently inactive.'];
        }
        if ($this->tag === 'manila') {
            return [false, 'note manila', '●',
                '<b>Tagged Manila</b>&nbsp;— unlocked for you as HR Head Preparer. The DH Head (not the regular Division Head) will act at the confirmation stage.'];
        }
        if ($this->role === 'head') {
            return [false, 'note info', 'i',
                'Tagged Tarlac (routine) — unlocked. By process a normal HR Preparer completes routine PANs, but as HR Head you can still view or edit it.'];
        }
        return [false, 'note info', 'i',
            'Tagged Tarlac (routine) — unlocked. Continue with Employment Details and the Action Reference.'];
    }

    public function render()
    {
        [$locked, $noteClass, $noteIcon, $noteMsg] = $this->lockState();

        return view('livewire.hr-preparation.prepare-form', compact('locked', 'noteClass', 'noteIcon', 'noteMsg'));
    }
}
