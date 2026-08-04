<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Farm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * v1 is the only employee roster that exists anywhere right now — v2 production has none
 * (confirmed empty on the 2026-08-03 prod dump). This is a stopgap, not the real HR sync:
 * v1's `employees` table is manually maintained, not synced to the external API, and known
 * stale for at least the Financial Operations and Compliance split (v1 predates it entirely).
 * Run BEFORE panda:import-legacy, which needs these rows to resolve PAN employee_id/department_id.
 *
 * employee_no is free text in this app (no generator — Admin/Employees.php validates
 * required|max:20|unique, admin types it in). Derived here as EMP-{company_id, zero-padded
 * to 5} so it's deterministic, traceable back to v1, and won't collide with anything an admin
 * types by hand later.
 */
class ImportLegacyEmployees extends Command
{
    protected $signature = 'panda:import-legacy-employees
        {--path= : Directory containing v1\'s exported JSON (defaults to the sibling PandaSystem repo\'s legacy-export folder)}
        {--dry-run : Report what would happen without writing anything}';

    protected $description = 'Import v1\'s employee roster as v2\'s starting employees table (stopgap until a real HR sync exists)';

    /**
     * Financial Operations and Compliance doesn't exist in v2 — it split into Accounting/
     * Audit/Treasury. v1 has no data that disambiguates it; classified by job title with the
     * three genuinely ambiguous cases (Business Compliance roles, a joint Accounting/Audit
     * Division Head) resolved by the user. Keyed by v1 company_id.
     */
    private const FOC_SPLIT = [
        117 => 'Treasury', 1254 => 'Treasury', 1540 => 'Treasury', 607 => 'Treasury',
        1318 => 'Treasury', 1320 => 'Treasury', 1521 => 'Treasury', 1520 => 'Treasury',
        236 => 'Treasury', 713 => 'Treasury', 1609 => 'Treasury',
        1446 => 'Accounting', 151 => 'Accounting', 1397 => 'Accounting', 402 => 'Accounting',
        1322 => 'Accounting', 1498 => 'Accounting', 750 => 'Accounting', 247 => 'Accounting',
        168 => 'Audit', 1499 => 'Audit', 1265 => 'Audit', 1606 => 'Audit',
    ];

    public function handle(): int
    {
        $dir = $this->option('path') ?? base_path('../PandaSystem/storage/app/legacy-export');

        if (! File::exists($dir.'/employees.json')) {
            $this->error("No employees.json found under: {$dir}");

            return self::FAILURE;
        }

        $employees = json_decode(File::get($dir.'/employees.json'), true);
        $dryRun = (bool) $this->option('dry-run');

        $farmIds = Farm::pluck('id', 'name');
        // Case-insensitive department lookup — v1's own data has a "FeedMill" vs "Feedmill" typo.
        $departmentIds = Department::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id]);

        $stats = ['imported' => 0, 'skipped_unmapped_department' => 0, 'skipped_unmapped_farm' => 0];

        $run = function () use ($employees, $farmIds, $departmentIds, &$stats) {
            foreach ($employees as $e) {
                $farmId = $farmIds->get($e['farm']);
                if (! $farmId) {
                    $this->warn("Unmapped farm \"{$e['farm']}\" for {$e['full_name']} (legacy id {$e['legacy_id']}) — skipped.");
                    $stats['skipped_unmapped_farm']++;

                    continue;
                }

                $departmentName = self::FOC_SPLIT[$e['company_id']]
                    ?? (strtolower(trim($e['department'])) === 'financial operations and compliance'
                        ? null // FOC employee not in our resolved list — shouldn't happen, but don't guess
                        : $e['department']);

                $departmentId = $departmentName ? $departmentIds->get(strtolower(trim($departmentName))) : null;

                if (! $departmentId) {
                    $this->warn("Unmapped department \"{$e['department']}\" for {$e['full_name']} (legacy id {$e['legacy_id']}) — skipped.");
                    $stats['skipped_unmapped_department']++;

                    continue;
                }

                $employeeNo = 'EMP-'.str_pad((string) $e['company_id'], 5, '0', STR_PAD_LEFT);

                Employee::updateOrCreate(
                    ['employee_no' => $employeeNo],
                    [
                        'name' => $e['full_name'],
                        'farm_id' => $farmId,
                        'department_id' => $departmentId,
                        'position' => $e['position'] ?: 'Not on file',
                    ]
                );

                $stats['imported']++;
            }
        };

        if ($dryRun) {
            try {
                DB::transaction(function () use ($run) {
                    $run();
                    throw new \RuntimeException('__dry_run_rollback__');
                });
            } catch (\RuntimeException $e) {
                if ($e->getMessage() !== '__dry_run_rollback__') {
                    throw $e;
                }
            }
        } else {
            DB::transaction($run);
        }

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '').'Employee import complete:');
        $this->line("  Employees imported: {$stats['imported']}");
        $this->line("  Skipped — unmapped department: {$stats['skipped_unmapped_department']}");
        $this->line("  Skipped — unmapped farm: {$stats['skipped_unmapped_farm']}");

        return self::SUCCESS;
    }
}
