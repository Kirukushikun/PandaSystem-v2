<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class RunBackup extends Command
{
    protected $signature = 'panda:backup';

    protected $description = 'Dump the MySQL database into storage/app/backups (retains the newest 14)';

    public function handle(BackupService $backups): int
    {
        $file = $backups->run();
        $this->info("Backup written: {$file}");

        return self::SUCCESS;
    }
}
