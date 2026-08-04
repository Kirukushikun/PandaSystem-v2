<?php

namespace App\Console\Commands;

use App\Models\PanAttachment;
use App\Models\PanRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Second pass of the v1 -> v2 migration, resolving ImportLegacyPans' deliberately-skipped
 * pan_attachments (see that class's docblock, risk #12). requests.json already carries each
 * row's supporting_file_url/supporting_file_name (ExportForV2::exportRequests()); what was
 * missing was the physical file, which only exists on v1's *production* disk — this local dev
 * copy's storage/app/public/supporting_files never had them. Pulled down as a tarball from
 * v1 prod (storage/app/public/supporting_files + storage/app/employee_attachments) into
 * PandaSystem/storage/app/legacy-export/attachments-prod/. Of the 167 requests.json rows with
 * a supporting_file_url, only 86 files actually exist in that pull — the other 81 were already
 * gone from v1 prod's own disk before this migration (pre-existing data loss, not caused here).
 * Employee-level attachments (v1's separate `employee_attachments` table, 1 row) have no home
 * in v2 — there's no employee-attachment feature/model here — so that one file is left alone.
 */
class ImportLegacyAttachments extends Command
{
    protected $signature = 'panda:import-legacy-attachments
        {--path= : Directory containing v1\'s exported JSON (defaults to the sibling PandaSystem repo\'s legacy-export folder)}
        {--files= : Directory the production supporting_files were extracted into (defaults to <path>/attachments-prod/storage/app/public/supporting_files)}
        {--dry-run : Report what would happen without writing anything}';

    protected $description = 'Attach recovered v1 production supporting-document files to their imported PANs';

    public function handle(): int
    {
        $dir = $this->option('path') ?? base_path('../PandaSystem/storage/app/legacy-export');
        $filesDir = $this->option('files') ?? $dir.'/attachments-prod/storage/app/public/supporting_files';
        $dryRun = (bool) $this->option('dry-run');

        if (! File::exists($dir.'/requests.json')) {
            $this->error("No requests.json found under: {$dir}");

            return self::FAILURE;
        }

        if (! File::isDirectory($filesDir)) {
            $this->error("No extracted attachments directory found at: {$filesDir}");

            return self::FAILURE;
        }

        $requests = json_decode(File::get($dir.'/requests.json'), true);
        $pansByLegacyId = PanRequest::whereNotNull('legacy_id')->get()->keyBy('legacy_id');

        $stats = ['attached' => 0, 'skipped_file_missing' => 0, 'skipped_no_pan' => 0, 'skipped_already_attached' => 0];
        $missing = [];

        foreach ($requests as $r) {
            if (empty($r['supporting_file_url'])) {
                continue;
            }

            $pan = $pansByLegacyId->get($r['legacy_id']);
            if (! $pan) {
                $stats['skipped_no_pan']++;

                continue;
            }

            if ($pan->attachments()->where('original_name', $r['supporting_file_name'])->exists()) {
                $stats['skipped_already_attached']++;

                continue;
            }

            $sourcePath = $filesDir.'/'.basename($r['supporting_file_url']);
            if (! File::exists($sourcePath)) {
                $stats['skipped_file_missing']++;
                $missing[] = "{$r['legacy_reference']} ({$r['supporting_file_name']})";

                continue;
            }

            if ($dryRun) {
                $stats['attached']++;

                continue;
            }

            $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
            $storedName = Str::random(40).($extension ? ".{$extension}" : '');
            $destination = "pans/{$pan->reference}/{$storedName}";

            File::ensureDirectoryExists(dirname(storage_path('app/private/'.$destination)));
            File::copy($sourcePath, storage_path('app/private/'.$destination));

            PanAttachment::create([
                'pan_request_id' => $pan->id,
                'path' => $destination,
                'original_name' => $r['supporting_file_name'],
                'size' => File::size($sourcePath),
            ]);

            $stats['attached']++;
        }

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '').'Attachment import complete:');
        $this->line("  Attached: {$stats['attached']}");
        $this->line("  Skipped — already attached: {$stats['skipped_already_attached']}");
        $this->line("  Skipped — no matching PAN (not imported by panda:import-legacy): {$stats['skipped_no_pan']}");
        $this->line("  Skipped — file not recovered from v1 prod: {$stats['skipped_file_missing']}");

        if ($missing) {
            $this->newLine();
            $this->warn('Files referenced in v1 but not found in the production pull (pre-existing loss):');
            foreach ($missing as $m) {
                $this->line("  - {$m}");
            }
        }

        return self::SUCCESS;
    }
}
