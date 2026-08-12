<?php

namespace App\Livewire\ProxyApprover;

use App\Enums\ConfidentialityTag;
use App\Enums\PanStatus;
use App\Livewire\Concerns\FiltersPanRequests;
use App\Models\PanRequest;
use App\Models\ProxyApproverSetting;
use App\Notifications\PanActivity;
use App\Services\PanWorkflow;
use App\Services\ProxyApprovalEligibility;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Temporary, transparent override for Division-Head-gated PANs that have sat
 * stale beyond a configurable threshold (Maintenance → Proxy Approver). Manila
 * never appears here — see ProxyApprovalEligibility for the shared rule this
 * queue and PanRequestPolicy both consult, and PanReturn for the mandatory-
 * reason audit trail every action here leaves behind.
 */
#[Layout('layouts.app')]
#[Title('Proxy Approver — PANDA')]
class Queue extends Component
{
    use FiltersPanRequests;

    public string $search = '';

    // Shared reason modal — every proxy action requires a reason, unlike the
    // real Division Head's conditional (dispute/return-only) reason flow.
    public bool $showModal = false;

    public ?int $target = null;

    public string $modalAction = '';

    public string $reason = '';

    public string $details = '';

    protected function candidates()
    {
        return PanRequest::whereIn('status', [PanStatus::WithDivisionHead, PanStatus::ForConfirmation])
            ->where('confidentiality_tag', '!=', ConfidentialityTag::Manila)
            ->with(['employee.department', 'requestedBy', 'department'])
            ->when($this->search !== '', fn ($q) => $q
                ->where(fn ($q) => $q
                    ->where('reference', 'like', "%{$this->search}%")
                    ->orWhereHas('employee', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))))
            ->tap(fn ($q) => $this->applyPanFiltersAndSort($q))
            ->get();
    }

    protected function eligiblePans()
    {
        $eligibility = app(ProxyApprovalEligibility::class);

        return $this->candidates()->filter(fn (PanRequest $pan) => $eligibility->eligible($pan))->values();
    }

    /** Arms and opens the reason modal — server state, so re-renders can't shut it. */
    public function startReason(int $id, string $action): void
    {
        $this->target = $id;
        $this->modalAction = $action; // proxy_approve_dh | proxy_approve_confirmation
        $this->reason = '';
        $this->details = '';
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function submitReason(): void
    {
        $pan = PanRequest::findOrFail($this->target);
        $ability = $this->modalAction === 'proxy_approve_dh' ? 'proxyApproveDh' : 'proxyApproveConfirmation';
        $this->authorize($ability, $pan);

        $this->validate([
            'reason' => 'required|string|max:255',
            'details' => 'required_if:reason,Custom reason…|nullable|string|max:1000',
        ], ['details.required_if' => 'Describe the custom reason in the details field.']);

        $from = $pan->status;
        $to = app(PanWorkflow::class)->apply($from, $this->modalAction, $pan->confidentiality_tag);

        $pan->update(['status' => $to]);
        $pan->returns()->create([
            'action' => $this->modalAction,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $this->reason,
            'details' => $this->details ?: null,
            'returned_by' => auth()->id(),
        ]);

        $this->notifyRealDivisionHead($pan);

        $this->js("showToast('{$pan->reference} proxy-approved — forwarded on the Division Head\'s behalf.')");
        $this->reset('showModal', 'target', 'modalAction', 'reason', 'details');
    }

    /** Courtesy ping to whoever actually would have acted — the transparency half of this feature. */
    private function notifyRealDivisionHead(PanRequest $pan): void
    {
        $recipients = $pan->department->heads()->get()->reject(fn ($user) => $user->id === auth()->id());

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new PanActivity(
            'Proxy-approved on your behalf',
            "{$pan->reference} — {$pan->employee->name}. Reason: \"{$pan->returns()->latest('id')->first()?->reason}\".",
            $pan->reference,
            'Division Head',
        ));
    }

    public function render()
    {
        $pans = $this->eligiblePans();

        return view('livewire.proxy-approver.queue', [
            'pans' => $pans,
            'modalPan' => $this->target ? PanRequest::find($this->target) : null,
            'stats' => [
                'awaiting' => $pans->count(),
                'dh' => $pans->where('status', PanStatus::WithDivisionHead)->count(),
                'confirmation' => $pans->where('status', PanStatus::ForConfirmation)->count(),
            ],
            'thresholdDays' => ProxyApproverSetting::current()->threshold_days,
        ]);
    }
}
