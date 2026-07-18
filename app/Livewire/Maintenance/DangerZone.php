<?php

namespace App\Livewire\Maintenance;

use App\Models\AccessLog;
use App\Models\PanRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Destructive housekeeping, for real: mode radio-cards → Preview Count →
 * type-the-exact-count confirm → the deletion runs. Preview and execution
 * share one query builder so the typed count is the true blast radius.
 */
#[Layout('layouts.app')]
#[Title('Danger Zone — PANDA')]
class DangerZone extends Component
{
    public const GROUPS = [
        'wipe' => [
            'title' => 'Wipe PAN Records',
            'desc' => 'Delete PAN requests together with their preparation details and attachments.',
            'label' => 'Select Wipe Mode',
            'color' => 'red',
            'icon' => 'trash',
            'action' => 'Wipe PAN Records',
            'modes' => ['all' => ['Wipe All', 'Delete every PAN record'], 'range' => ['Date Range', 'Delete within a date range'], 'year' => ['By Year', 'Delete all PANs for a year']],
            'badge' => ' PAN records will be deleted',
        ],
        'attach' => [
            'title' => 'Purge PAN Attachments',
            'desc' => 'Delete supporting documents without removing the PAN records.',
            'label' => 'Select Purge Mode',
            'color' => 'amber',
            'icon' => 'image',
            'action' => 'Purge Attachments',
            'modes' => ['quarter' => ['By Quarter', 'Select a year and quarter (Q1–Q4)'], 'range' => ['Custom Range', 'Specify a custom date range']],
            'badge' => ' PANs will have attachments purged',
        ],
        'dlog' => [
            'title' => 'Purge Activity Logs',
            'desc' => 'Delete access-log entries from the system.',
            'label' => 'Select Purge Mode',
            'color' => 'red',
            'icon' => 'trash',
            'action' => 'Purge Activity Logs',
            'modes' => ['all' => ['Purge All', 'Delete all log entries'], 'range' => ['Date Range', 'Delete within a date range'], 'year' => ['By Year', 'Delete all logs for a year']],
            'badge' => ' log entries will be deleted',
        ],
    ];

    public array $modes = ['wipe' => null, 'attach' => null, 'dlog' => null];

    public array $counts = ['wipe' => null, 'attach' => null, 'dlog' => null];

    /** Per-group filter inputs. */
    public array $from = ['wipe' => '', 'attach' => '', 'dlog' => ''];

    public array $to = ['wipe' => '', 'attach' => '', 'dlog' => ''];

    public array $year = ['wipe' => '', 'dlog' => ''];

    public string $attachYear = '';

    public string $attachQuarter = '';

    /** null = closed; otherwise [grp, title, msg, required, button]. */
    public ?array $confirm = null;

    public string $confirmInput = '';

    public function selectMode(string $grp, string $mode): void
    {
        $this->modes[$grp] = $mode;
        $this->counts[$grp] = null; // changing mode invalidates a previous preview
    }

    public function updated(string $property): void
    {
        // A filter change invalidates previews — the typed count must match reality.
        if (preg_match('/^(from|to|year)\./', $property) || in_array($property, ['attachYear', 'attachQuarter'], true)) {
            $this->counts = array_map(fn () => null, $this->counts);
        }
    }

    /** The one query both Preview and the execution use. Null = inputs incomplete. */
    protected function query(string $grp): ?Builder
    {
        $mode = $this->modes[$grp];
        if ($mode === null) {
            return null;
        }

        $query = match ($grp) {
            'wipe' => PanRequest::query(),
            'attach' => PanRequest::query()->whereNotNull('attachment_path'),
            'dlog' => AccessLog::query(),
        };

        if ($mode === 'all') {
            return $query;
        }

        if ($mode === 'range') {
            if ($this->from[$grp] === '' || $this->to[$grp] === '') {
                return null;
            }

            return $query->whereBetween('created_at', [$this->from[$grp].' 00:00:00', $this->to[$grp].' 23:59:59']);
        }

        if ($mode === 'year') {
            return $this->year[$grp] === '' ? null : $query->whereYear('created_at', $this->year[$grp]);
        }

        // quarter (attachments only)
        if ($this->attachYear === '' || $this->attachQuarter === '') {
            return null;
        }
        $start = now()->setDate((int) $this->attachYear, ((int) $this->attachQuarter - 1) * 3 + 1, 1)->startOfDay();

        return $query->whereBetween('created_at', [$start, $start->copy()->addMonths(3)]);
    }

    public function preview(string $grp): void
    {
        $query = $this->query($grp);
        if ($query === null) {
            $this->js("showToast('Select a mode and complete its inputs first.')");

            return;
        }

        $this->counts[$grp] = $query->count();
    }

    public function openConfirm(string $grp): void
    {
        if ($this->counts[$grp] === null) {
            $this->js("showToast('Run Preview Count first.')");

            return;
        }
        if ($this->counts[$grp] === 0) {
            $this->js("showToast('Nothing matches — there is nothing to delete.')");

            return;
        }

        $n = $this->counts[$grp];
        $fmt = number_format($n);
        $noun = [
            'wipe' => 'PAN records (with preparation details and attachments)',
            'attach' => 'PAN attachments — the records are kept',
            'dlog' => 'log entries',
        ][$grp];
        $this->confirmInput = '';
        $this->confirm = [
            'grp' => $grp,
            'title' => ['wipe' => 'Confirm Permanent Deletion', 'attach' => 'Confirm Attachment Purge', 'dlog' => 'Confirm Activity Log Purge'][$grp],
            'msg' => "This will permanently delete <b>{$fmt}</b> {$noun}. This action <b>cannot be undone</b>.",
            'required' => (string) $n,
            'button' => ['wipe' => "Delete {$fmt} PANs", 'attach' => "Purge {$fmt} Attachments", 'dlog' => "Purge {$fmt} Entries"][$grp],
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

        $grp = $this->confirm['grp'];
        $query = $this->query($grp);
        if ($query === null) {
            $this->closeConfirm();

            return;
        }

        $deleted = match ($grp) {
            'wipe' => $this->wipePans($query),
            'attach' => $this->purgeAttachments($query),
            'dlog' => $query->delete(),
        };

        $this->js('showToast('.json_encode(number_format($deleted).' '.($grp === 'attach' ? 'attachment(s) purged.' : 'record(s) deleted.')).')');
        $this->counts = ['wipe' => null, 'attach' => null, 'dlog' => null];
        $this->closeConfirm();
    }

    private function wipePans(Builder $query): int
    {
        $count = 0;
        $query->withTrashed()->chunkById(100, function ($pans) use (&$count) {
            foreach ($pans as $pan) {
                Storage::deleteDirectory('pans/'.$pan->reference);
                $pan->forceDelete(); // forms + returns cascade at the DB level
                $count++;
            }
        });

        return $count;
    }

    private function purgeAttachments(Builder $query): int
    {
        $count = 0;
        $query->chunkById(100, function ($pans) use (&$count) {
            foreach ($pans as $pan) {
                Storage::deleteDirectory('pans/'.$pan->reference);
                $pan->update(['attachment_path' => null]);
                $count++;
            }
        });

        return $count;
    }

    public function render()
    {
        return view('livewire.maintenance.danger-zone');
    }
}
