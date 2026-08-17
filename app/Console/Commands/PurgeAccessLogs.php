<?php

namespace App\Console\Commands;

use App\Models\AccessLog;
use Illuminate\Console\Command;

/**
 * Scheduled counterpart to Danger Zone's manual "Purge Activity Logs" — keeps
 * the access log from growing unbounded without requiring an admin to run the
 * destructive purge by hand. Only ever deletes on age; never touches the
 * pan_returns-derived audit trail (Logs.php), which has no separate store to purge.
 */
class PurgeAccessLogs extends Command
{
    protected $signature = 'panda:purge-access-logs {--days=30 : Delete entries older than this many days}';

    protected $description = 'Delete access-log entries older than the retention window';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $deleted = AccessLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("{$deleted} access-log entry(ies) older than {$days} day(s) deleted.");

        return self::SUCCESS;
    }
}
