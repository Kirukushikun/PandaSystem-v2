<?php

namespace App\Livewire\DivisionHead;

use App\Models\PanRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('View Request — PANDA')]
class Show extends Component
{
    public string $pan;

    /** True when the request already has an HR-prepared PAN — the view then appends the
     *  PAN-details extension. Real build: derived from the pan_forms relation; scaffold:
     *  hardcoded to the mockup rows that link to the "+ PAN Details" variant. */
    public bool $hasPreparedPan;

    public function mount(string $pan)
    {
        // Per-record authorization already live (v1 direct-link lesson) even though
        // the body below still renders scaffold sample data until this module is wired.
        $panRequest = PanRequest::where('reference', $pan)->firstOrFail();
        $this->authorize('view', $panRequest);

        $this->pan = $pan;
        $this->hasPreparedPan = in_array($pan, ['PAN-2026-00339', 'PAN-2026-00311']);
    }

    public function render()
    {
        return view('livewire.division-head.show');
    }
}
