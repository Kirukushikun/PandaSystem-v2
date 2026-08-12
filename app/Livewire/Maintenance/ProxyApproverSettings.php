<?php

namespace App\Livewire\Maintenance;

use App\Models\ProxyApproverSetting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The kill switch + staleness threshold for the temporary Proxy Approver
 * override (see ProxyApprovalEligibility). Sits in Maintenance so the feature
 * can be dialed back once backlogs clear, without a code change.
 */
#[Layout('layouts.app')]
#[Title('Proxy Approver — PANDA')]
class ProxyApproverSettings extends Component
{
    public bool $enabled = true;

    public int $thresholdDays = 14;

    public function mount(): void
    {
        $settings = ProxyApproverSetting::current();
        $this->enabled = $settings->enabled;
        $this->thresholdDays = $settings->threshold_days;
    }

    public function save(): void
    {
        $this->validate([
            'thresholdDays' => 'required|integer|min:1|max:90',
        ], [], ['thresholdDays' => 'threshold (days)']);

        ProxyApproverSetting::current()->update([
            'enabled' => $this->enabled,
            'threshold_days' => $this->thresholdDays,
        ]);

        $this->js("showToast('Proxy Approver settings saved.')");
    }

    public function render()
    {
        return view('livewire.maintenance.proxy-approver-settings');
    }
}
