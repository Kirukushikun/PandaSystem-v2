<?php

namespace App\Livewire\Maintenance;

use App\Services\BackupService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Nightly mysqldump backups (01:00, newest 14 kept) + manual runs, downloads,
 * and restore from an uploaded dump — type RESTORE, safety backup taken first.
 */
#[Layout('layouts.app')]
#[Title('Backup & Restore — PANDA')]
class Backups extends Component
{
    use WithFileUploads;

    public bool $showRestore = false;

    public string $confirmInput = '';

    public $restoreFile = null; // uploaded .sql dump

    public function runBackup(): void
    {
        try {
            $file = app(BackupService::class)->run();
            $this->js('showToast('.json_encode('Backup complete: '.basename($file)).')');
        } catch (\RuntimeException $e) {
            report($e);
            $this->js('showToast('.json_encode('Backup failed — '.$e->getMessage()).')');
        }
    }

    public function openRestore(): void
    {
        $this->validate(['restoreFile' => 'required|file|max:512000'], [
            'restoreFile.required' => 'Choose the backup (.sql) file to restore first.',
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

        $path = $this->restoreFile->storeAs(BackupService::DIR, 'uploaded_'.now()->format('Y-m-d_His').'.sql');

        try {
            app(BackupService::class)->restore($path);
            $this->js("showToast('Restore complete — a pre-restore safety backup was taken first.')");
        } catch (\RuntimeException $e) {
            report($e);
            $this->js('showToast('.json_encode('Restore failed — '.$e->getMessage()).')');
        }

        $this->restoreFile = null;
        $this->closeRestore();
    }

    public function render()
    {
        $backups = app(BackupService::class)->list();
        $latest = $backups[0] ?? null;

        return view('livewire.maintenance.backups', [
            'backups' => array_slice($backups, 0, 6),
            'stats' => [
                'health' => $latest === null ? 'No backups' : ($latest['at'] >= now()->subDay()->timestamp ? 'Healthy' : 'Stale'),
                'healthTone' => $latest !== null && $latest['at'] >= now()->subDay()->timestamp ? 'ok' : 'bad',
                'retained' => count($backups),
                'size' => $latest ? round($latest['size'] / 1048576, 1).' MB' : '—',
            ],
        ]);
    }
}
