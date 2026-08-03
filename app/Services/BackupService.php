<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Local MySQL backups via mysqldump into storage/app/backups. Deliberately
 * lean (no spatie/laravel-backup): one internal MySQL database on one box.
 * Restore always takes a safety backup first.
 */
class BackupService
{
    public const DIR = 'backups';

    public const KEEP = 14; // nightly retention

    public function run(string $suffix = ''): string
    {
        $db = config('database.connections.mysql');
        $file = self::DIR.'/panda_'.now()->format('Y-m-d_His').($suffix ? "_{$suffix}" : '').'.sql';

        Storage::makeDirectory(self::DIR);

        $result = Process::run(command: [
            $this->binary('mysqldump'),
            '--host='.$db['host'], '--port='.$db['port'], '--user='.$db['username'],
            '--password='.$db['password'], '--result-file='.Storage::path($file),
            '--routines', '--single-transaction', $db['database'],
        ]);

        if ($result->failed() || ! Storage::exists($file)) {
            Storage::delete($file);
            throw new RuntimeException('mysqldump failed: '.trim($result->errorOutput()));
        }

        $this->prune();

        return $file;
    }

    /** Overwrites the database from a dump — after taking a safety backup. */
    public function restore(string $path): void
    {
        if (! Storage::exists($path)) {
            throw new RuntimeException('Backup file not found.');
        }

        $this->run('pre-restore-safety');

        $db = config('database.connections.mysql');
        $result = Process::input(fopen(Storage::path($path), 'r'))->run(command: [
            $this->binary('mysql'),
            '--host='.$db['host'], '--port='.$db['port'], '--user='.$db['username'],
            '--password='.$db['password'], $db['database'],
        ]);

        if ($result->failed()) {
            throw new RuntimeException('restore failed: '.trim($result->errorOutput()));
        }
    }

    /** @return array<int, array{file: string, size: int, at: int}> newest first */
    public function list(): array
    {
        return collect(Storage::files(self::DIR))
            ->filter(fn (string $file) => str_ends_with($file, '.sql'))
            ->map(fn (string $file) => [
                'file' => $file,
                'size' => Storage::size($file),
                'at' => Storage::lastModified($file),
            ])
            ->sortByDesc('at')->values()->all();
    }

    private function prune(): void
    {
        foreach (array_slice($this->list(), self::KEEP) as $old) {
            Storage::delete($old['file']);
        }
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
