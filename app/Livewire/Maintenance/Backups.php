<?php

namespace App\Livewire\Maintenance;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Backup & Restore — PANDA')]
class Backups extends Component
{
    /** Type-RESTORE confirm modal (fully Livewire-driven, unlike the static x-modal). */
    public bool $showRestore = false;

    public string $confirmInput = '';

    public function runBackup(): void
    {
        $this->js("showToast('Manual backup started — it will appear in the list when complete. (UI scaffold — nothing actually runs.)')");
    }

    public function openRestore(): void
    {
        $this->confirmInput = '';
        $this->showRestore = true;
    }

    public function closeRestore(): void
    {
        $this->showRestore = false;
        $this->confirmInput = '';
    }

    public function queueRestore(): void
    {
        if ($this->confirmInput !== 'RESTORE') {
            return;
        }
        $this->closeRestore();
        $this->js("showToast('Restore job queued — the system will be briefly unavailable. (UI scaffold — nothing is queued yet.)')");
    }

    public function render()
    {
        return view('livewire.maintenance.backups');
    }
}
