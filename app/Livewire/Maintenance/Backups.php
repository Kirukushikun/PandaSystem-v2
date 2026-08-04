<?php

namespace App\Livewire\Maintenance;

use App\Services\BackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Config\Config as BackupConfig;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatusFactory;

/**
 * spatie/laravel-backup owns creation/retention (config/backup.php, scheduled daily
 * 18:00 in routes/console.php) to two destinations — the 'backups' disk and Google
 * Drive (only once GOOGLE_DRIVE_REFRESH_TOKEN is set). Both copies come from the
 * same backup:run invocation, so they always pair up by filename/size — "retained"
 * below counts pairs, not individual files. Manual runs, downloads (local copy
 * only), and restore from an uploaded dump all live here.
 */
#[Layout('layouts.app')]
#[Title('Backup & Restore — PANDA')]
class Backups extends Component
{
    use WithFileUploads;

    public bool $showRestore = false;

    public string $confirmInput = '';

    public $restoreFile = null; // uploaded .sql dump

    private const SUMMARY_CACHE_KEY = 'maintenance.backups.summary';

    public function runBackup(): void
    {
        try {
            Artisan::call('backup:run', ['--only-db' => true]);
            Cache::forget(self::SUMMARY_CACHE_KEY);
            $note = $this->driveConfigured() ? ' — synced to local and Google Drive.' : ' (local only — Drive not configured).';
            $this->js('showToast('.json_encode('Backup complete'.$note).')');
        } catch (\Throwable $e) {
            report($e);
            $this->js('showToast('.json_encode('Backup failed — '.$e->getMessage()).')');
        }
    }

    public function openRestore(): void
    {
        $this->validate(['restoreFile' => 'required|file|max:512000|extensions:sql,zip'], [
            'restoreFile.required' => 'Choose the backup (.sql or .zip) file to restore first.',
            'restoreFile.extensions' => 'Restore accepts a raw .sql dump or a backup .zip archive.',
        ], ['restoreFile' => 'backup file']);

        $this->confirmInput = '';
        $this->showRestore = true;
    }

    public function closeRestore(): void
    {
        $this->showRestore = false;
        $this->confirmInput = '';
    }

    public function runRestore(): void
    {
        if ($this->confirmInput !== 'RESTORE' || ! $this->restoreFile) {
            return;
        }

        $extension = $this->restoreFile->getClientOriginalExtension();
        $path = $this->restoreFile->storeAs(BackupService::RESTORE_UPLOAD_DIR, 'uploaded_'.now()->format('Y-m-d_His').'.'.$extension);

        try {
            app(BackupService::class)->restore($path);
            Cache::forget(self::SUMMARY_CACHE_KEY);
            $this->js("showToast('Restore complete — a pre-restore safety backup was taken first.')");
        } catch (\RuntimeException $e) {
            report($e);
            $this->js('showToast('.json_encode('Restore failed — '.$e->getMessage()).')');
        }

        $this->restoreFile = null;
        $this->closeRestore();
    }

    private function driveConfigured(): bool
    {
        return filled(config('filesystems.disks.google.refreshToken'));
    }

    /**
     * Listing 'google' hits the Drive API over the network. Livewire re-runs render() on
     * every round-trip — including every keystroke of wire:model.live="confirmInput" and
     * every click to open the modal — so without caching, typing "RESTORE" fires a live
     * Drive API call per character. Cached briefly and invalidated on the two actions that
     * actually change the backup list (runBackup, runRestore's pre-restore safety backup).
     */
    private function summary(): array
    {
        return Cache::remember(self::SUMMARY_CACHE_KEY, 30, function () {
            $local = BackupDestination::create('backups', '')->fresh()->backups();
            $driveFiles = $this->driveConfigured()
                ? BackupDestination::create('google', '')->fresh()->backups()->map(fn ($b) => basename($b->path()))->all()
                : [];

            $backups = $local->map(fn ($b) => [
                'file' => basename($b->path()),
                'size' => (int) $b->sizeInBytes(),
                'at' => $b->date()->timestamp,
                'onDrive' => in_array(basename($b->path()), $driveFiles, true),
            ])->values()->all();

            $latest = $backups[0] ?? null;

            $health = 'No backups';
            $healthTone = 'bad';
            if ($latest !== null) {
                $statuses = BackupDestinationStatusFactory::createForMonitorConfig(app(BackupConfig::class)->monitoredBackups);
                $healthy = $statuses->every(fn ($status) => $status->isHealthy());
                $health = $healthy ? 'Healthy' : 'Unhealthy';
                $healthTone = $healthy ? 'ok' : 'bad';
            }

            return ['backups' => $backups, 'latest' => $latest, 'health' => $health, 'healthTone' => $healthTone];
        });
    }

    public function render()
    {
        ['backups' => $backups, 'latest' => $latest, 'health' => $health, 'healthTone' => $healthTone] = $this->summary();

        return view('livewire.maintenance.backups', [
            'backups' => array_slice($backups, 0, 6),
            'driveConfigured' => $this->driveConfigured(),
            'stats' => [
                'health' => $health,
                'healthTone' => $healthTone,
                'retained' => count($backups),
                'size' => $latest ? round($latest['size'] / 1048576, 1).' MB' : '—',
            ],
        ]);
    }
}
