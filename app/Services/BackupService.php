<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Restoring a database dump into MySQL — the one piece spatie/laravel-backup doesn't
 * provide itself. Creating, listing, and pruning backups (local + Google Drive) is
 * entirely Spatie's job now — config/backup.php, scheduled in routes/console.php.
 * This only imports an uploaded .sql file back in, after taking a fresh safety
 * backup first.
 */
class BackupService
{
    public const RESTORE_UPLOAD_DIR = 'restore-uploads';

    /**
     * Overwrites the database from an uploaded dump — after a safety backup. Accepts
     * either a raw .sql file or one of spatie/laravel-backup's own .zip archives (the
     * kind the "Recent backups" list produces), so a previously downloaded backup can
     * be fed straight back in without manual unzipping first.
     */
    public function restore(string $path): void
    {
        if (! Storage::exists($path)) {
            throw new RuntimeException('Backup file not found.');
        }

        $extractedTemp = null;
        $sqlPath = str_ends_with($path, '.zip')
            ? ($extractedTemp = $this->extractDumpFromZip($path))
            : Storage::path($path);

        Artisan::call('backup:run', ['--only-db' => true]);

        $db = config('database.connections.mysql');
        $result = Process::input(fopen($sqlPath, 'r'))->run(command: [
            $this->binary('mysql'),
            '--host='.$db['host'], '--port='.$db['port'], '--user='.$db['username'],
            '--password='.$db['password'], $db['database'],
        ]);

        Storage::delete($path);
        if ($extractedTemp) {
            @unlink($extractedTemp);
        }

        if ($result->failed()) {
            throw new RuntimeException('restore failed: '.trim($result->errorOutput()));
        }
    }

    /** @return string path to a temporary extracted .sql file — caller must unlink it */
    private function extractDumpFromZip(string $path): string
    {
        $zip = new \ZipArchive;
        if ($zip->open(Storage::path($path)) !== true) {
            throw new RuntimeException('Could not open the uploaded backup archive.');
        }

        $dumpEntry = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_starts_with($name, 'db-dumps/') && str_ends_with($name, '.sql')) {
                $dumpEntry = $name;
                break;
            }
        }

        if ($dumpEntry === null) {
            $zip->close();
            throw new RuntimeException('No database dump found inside the uploaded archive.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'panda_restore_').'.sql';
        copy('zip://'.Storage::path($path).'#'.$dumpEntry, $tempPath);
        $zip->close();

        return $tempPath;
    }

    /**
     * Resolves mysqldump/mysql deterministically instead of trusting PATH at
     * request time — the web server process (Apache/PHP-FPM, a Docker
     * container) often doesn't see the same PATH a terminal does, and a
     * production image may not ship the mysql-client tools at all.
     *
     * Order: explicit DB_DUMP_BINARY_PATH env → PATH lookup → Laragon's
     * bundled MySQL (Windows dev fallback only).
     */
    private function binary(string $name): string
    {
        $configuredDir = config('database.connections.mysql.dump.dump_binary_path');
        if ($configuredDir) {
            $exe = rtrim($configuredDir, '/\\').DIRECTORY_SEPARATOR.$name.(PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
            if (is_file($exe)) {
                return $exe;
            }
        }

        $lookup = PHP_OS_FAMILY === 'Windows' ? ['where.exe', $name] : ['which', $name];
        $found = Process::run($lookup);
        if ($found->successful() && trim($found->output()) !== '') {
            return trim(explode("\n", trim($found->output()))[0]);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $candidates = glob('C:/laragon/bin/mysql/*/bin/'.$name.'.exe') ?: [];
            if ($candidates !== []) {
                return end($candidates);
            }
        }

        throw new RuntimeException(
            "{$name} not found. Set DB_DUMP_BINARY_PATH in .env to the directory containing it ".
            '(e.g. /usr/bin on Ubuntu, once mysql-client is installed), or add it to PATH.'
        );
    }
}
