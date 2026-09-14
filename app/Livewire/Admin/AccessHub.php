<?php

namespace App\Livewire\Admin;

use App\Models\AccessHubConnection;
use App\Services\AccessHubService;
use App\Services\AccessHubSyncService;
use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * Preview-then-apply sync from the Access Hub — project-overview/hub-integration-guide.md.
 * Never writes on its own; every create/update/revoke is a row the admin explicitly ticked.
 * Embedded inside User Accounts' "Sync from Hub" modal (resources/views/livewire/admin/users.blade.php)
 * — this is an extension of that roster screen, not a standalone page, so it has no
 * route/nav entry of its own.
 */
class AccessHub extends Component
{
    public bool $showEnrollModal = false;

    public string $enrollCode = '';

    public ?string $enrollError = null;

    /** compare() result, or null before the first run / after a failed fetch. */
    public ?array $preview = null;

    /** Set alongside a null $preview — 'not_enrolled' | 'unreachable' | 'unauthorized'. */
    public ?string $fetchError = null;

    public array $selectedNew = [];

    public array $selectedChanged = [];

    public ?Carbon $lastSyncedAt = null;

    public bool $enrolled = false;

    public function mount(): void
    {
        $connection = AccessHubConnection::current();
        $this->enrolled = $connection->isEnrolled();
        $this->lastSyncedAt = $connection->last_synced_at;
    }

    public function openEnroll(): void
    {
        $this->enrollError = null;
        $this->showEnrollModal = true;
    }

    public function enroll(): void
    {
        $this->validate(['enrollCode' => 'required|string']);

        if (! app(AccessHubService::class)->enroll($this->enrollCode)) {
            $this->enrollError = 'Could not enroll — check the code and try again.';

            return;
        }

        $this->showEnrollModal = false;
        $this->enrollCode = '';
        $this->enrollError = null;
        $this->enrolled = true;
        $this->runSync();
    }

    public function resetConnection(): void
    {
        AccessHubConnection::current()->update([
            'client_id' => null,
            'client_secret' => null,
            'last_synced_at' => null,
        ]);
        $this->enrolled = false;
        $this->preview = null;
        $this->fetchError = null;
        $this->lastSyncedAt = null;
        $this->js("showToast('Access Hub connection reset.')");
    }

    public function runSync(): void
    {
        if (! $this->enrolled) {
            $this->openEnroll();

            return;
        }

        $sync = app(AccessHubSyncService::class);
        $result = $sync->compare();

        if ($result === null) {
            $this->preview = null;
            $this->fetchError = $sync->lastFailureReason();

            return;
        }

        $this->preview = $result;
        $this->fetchError = null;
        $this->lastSyncedAt = AccessHubConnection::current()->last_synced_at;
        $this->selectedNew = [];
        $this->selectedChanged = [];
    }

    public function apply(): void
    {
        if ($this->preview === null) {
            return;
        }

        app(AccessHubSyncService::class)->apply($this->preview, $this->selectedNew, $this->selectedChanged);
        $this->js("showToast('Access Hub changes applied.')");
        $this->runSync(); // refresh the preview against the new local state
    }

    public function render()
    {
        return view('livewire.admin.access-hub');
    }
}
