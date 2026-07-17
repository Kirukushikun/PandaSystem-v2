<?php

namespace App\Livewire\Maintenance;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Danger Zone — PANDA')]
class DangerZone extends Component
{
    /**
     * Adapted from data-wipe.html via the mockup: mode radio-cards → Preview Count →
     * type-the-exact-count confirm modal → queued job + toast. All live Livewire state;
     * counts are random like the mockup's. Real build: Admin-only + audited + queued jobs.
     */
    public const GROUPS = [
        'wipe' => [
            'title'  => 'Wipe PAN Records',
            'desc'   => 'Delete PAN requests together with their preparation details and attachments.',
            'label'  => 'Select Wipe Mode',
            'color'  => 'red',
            'icon'   => 'trash',
            'action' => 'Wipe PAN Records',
            'modes'  => ['all' => ['Wipe All', 'Delete every PAN record'], 'range' => ['Date Range', 'Delete within a date range'], 'year' => ['By Year', 'Delete all PANs for a year']],
            'badge'  => ' PAN records will be deleted',
        ],
        'attach' => [
            'title'  => 'Purge PAN Attachments',
            'desc'   => 'Delete supporting documents without removing the PAN records. Affected PANs will be marked as "Attachment Expired".',
            'label'  => 'Select Purge Mode',
            'color'  => 'amber',
            'icon'   => 'image',
            'action' => 'Purge Attachments',
            'modes'  => ['quarter' => ['By Quarter', 'Select a year and quarter (Q1–Q4)'], 'range' => ['Custom Range', 'Specify a custom date range']],
            'badge'  => ' PANs will have attachments purged',
        ],
        'dlog' => [
            'title'  => 'Purge Activity Logs',
            'desc'   => 'Delete access-log and audit-trail entries from the system.',
            'label'  => 'Select Purge Mode',
            'color'  => 'red',
            'icon'   => 'trash',
            'action' => 'Purge Activity Logs',
            'modes'  => ['all' => ['Purge All', 'Delete all log entries'], 'range' => ['Date Range', 'Delete within a date range'], 'year' => ['By Year', 'Delete all logs for a year']],
            'badge'  => ' log entries will be deleted',
        ],
    ];

    public array $modes = ['wipe' => null, 'attach' => null, 'dlog' => null];

    public array $counts = ['wipe' => null, 'attach' => null, 'dlog' => null];

    /** Quarter select stays disabled until a year is chosen (mockup behavior). */
    public string $attachYear = '';

    /** null = closed; otherwise [title, msg, required, button, toast]. */
    public ?array $confirm = null;

    public string $confirmInput = '';

    public function selectMode(string $grp, string $mode): void
    {
        $this->modes[$grp] = $mode;
        $this->counts[$grp] = null; // changing mode invalidates a previous preview
    }

    public function updatedAttachYear(): void
    {
        $this->counts['attach'] = null;
    }

    public function preview(string $grp): void
    {
        if (! $this->modes[$grp]) {
            $this->js("showToast('Select a mode first.')");
            return;
        }
        $this->counts[$grp] = random_int(100, 9099);
    }

    public function openConfirm(string $grp): void
    {
        if ($this->counts[$grp] === null) {
            $this->js("showToast('Run Preview Count first.')");
            return;
        }
        $n = $this->counts[$grp];
        $fmt = number_format($n);
        $noun = [
            'wipe'   => 'PAN records (with preparation details and attachments)',
            'attach' => 'PANs — records kept, marked "Attachment Expired"',
            'dlog'   => 'log entries',
        ][$grp];
        $this->confirmInput = '';
        $this->confirm = [
            'title'    => ['wipe' => 'Confirm Permanent Deletion', 'attach' => 'Confirm Attachment Purge', 'dlog' => 'Confirm Activity Log Purge'][$grp],
            'msg'      => "This will permanently delete <b>{$fmt}</b> {$noun}. This action <b>cannot be undone</b>.",
            'required' => (string) $n,
            'button'   => ['wipe' => "Queue Deletion of {$fmt} PANs", 'attach' => "Queue Purge of {$fmt} Attachments", 'dlog' => "Queue Purge of {$fmt} Entries"][$grp],
            'toast'    => "Job queued — {$fmt} " . ($grp === 'attach' ? 'attachment purges' : 'deletions') . ' will run in the background. (UI scaffold — nothing is queued yet.)',
        ];
    }

    public function closeConfirm(): void
    {
        $this->confirm = null;
        $this->confirmInput = '';
    }

    public function queueConfirmed(): void
    {
        if (! $this->confirm || $this->confirmInput !== $this->confirm['required']) {
            return;
        }
        $this->js('showToast(' . json_encode($this->confirm['toast']) . ')');
        $this->counts = ['wipe' => null, 'attach' => null, 'dlog' => null]; // mockup resets every badge
        $this->closeConfirm();
    }

    public function render()
    {
        return view('livewire.maintenance.danger-zone');
    }
}
