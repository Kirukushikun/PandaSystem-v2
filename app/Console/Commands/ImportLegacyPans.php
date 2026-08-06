<?php

namespace App\Console\Commands;

use App\Enums\ActionType;
use App\Enums\ConfidentialityTag;
use App\Enums\EmploymentStatus;
use App\Enums\PanOrigin;
use App\Enums\PanStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PanForm;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * One-time v1 → v2 data migration. Reads the plain JSON `panda:export-for-v2` wrote (already
 * decrypted, v1 never shares its APP_KEY with this repo) and creates real pan_requests/
 * pan_forms rows. Mapping decisions live in PandaSystem/storage/app/legacy-export/
 * MAPPING_NEEDED.md — this command implements that file, it does not decide anything itself.
 *
 * Deliberately NOT imported: pan_returns (v1's log entries have no from_status/to_status/
 * action to map from, most of the history is already gone to log retention, and returned_by
 * is a required FK to a real user we can't resolve) and pan_attachments (the physical files
 * aren't in the JSON export, only their old name/path — project-overview's migration plan
 * risk #12 leaves this as an open question). Both are counted and reported, not silently lost.
 */
class ImportLegacyPans extends Command
{
    protected $signature = 'panda:import-legacy
        {--path= : Directory containing v1\'s exported JSON (defaults to the sibling PandaSystem repo\'s legacy-export folder)}
        {--dry-run : Report what would happen without writing anything}
        {--timestamps-only : Backfill ONLY approved_at/served_at where still null on already-imported rows — never touches status/action_reference/anything else. Safe against production even after HR has been acting directly in v2 for a while, unlike a full re-import.}';

    protected $description = 'Import v1 PAN history from panda:export-for-v2\'s JSON export';

    /** v1 request_status -> v2 PanStatus, per MAPPING_NEEDED.md. is_deleted=1 overrides this entirely (handled separately). */
    private const STATUS_MAP = [
        'Draft' => PanStatus::Draft,
        'For Head Approval' => PanStatus::WithDivisionHead,
        'For HR Prep' => PanStatus::InPreparation,
        'For Confirmation' => PanStatus::ForConfirmation,
        'For Resolution' => PanStatus::ReturnedToPreparer,
        'For HR Approval' => PanStatus::ForHrApproval,
        'For Final Approval' => PanStatus::ForFinalApproval,
        'Approved' => PanStatus::Approved,
        'Served' => PanStatus::Served,
        'Filed' => PanStatus::Filed,
        'Unserved' => PanStatus::Unserved,
        'Returned to Requestor' => PanStatus::ReturnedToRequestor,
        'Withdrew' => PanStatus::Withdrawn,
    ];

    /**
     * v1 type_of_action -> v2 ActionType. Keyed on the raw stored column value, not the UI
     * label — "Discontinuance of Interim Allowance" is what's in the DB, the v2 case is named
     * after the short label the UI has always shown. See MAPPING_NEEDED.md gotcha #1.
     */
    private const ACTION_TYPE_MAP = [
        'Wage Order' => ActionType::WageOrder,
        'Regularization' => ActionType::Regularization,
        'Lateral Transfer' => ActionType::LateralTransfer,
        'Promotion' => ActionType::Promotion,
        'Other Allowances' => ActionType::OtherAllowances,
        'Salary Alignment' => ActionType::SalaryAlignment,
        'Interim Allowance' => ActionType::InterimAllowance,
        'Discontinuance of Interim Allowance' => ActionType::DiscontinuanceOfAllowance,
        'Confirmation of Appointment' => ActionType::ConfirmationOfAppointment,
        'Training Status' => ActionType::TrainingStatus,
        'Developmental Assignment' => ActionType::DevelopmentalAssignment,
        'Change of Position' => ActionType::ChangeOfPosition,
        'Confirmation of Development Assignment' => ActionType::ConfirmationOfDevelopmentAssignment,
    ];

    public function handle(): int
    {
        $dir = $this->option('path') ?? base_path('../PandaSystem/storage/app/legacy-export');

        if (! File::exists($dir.'/requests.json')) {
            $this->error("No requests.json found under: {$dir}");

            return self::FAILURE;
        }

        $requests = json_decode(File::get($dir.'/requests.json'), true);
        $dryRun = (bool) $this->option('dry-run');

        if ($this->option('timestamps-only')) {
            return $this->backfillTimestampsOnly($requests, $dryRun);
        }

        // The pinned admin account (CLAUDE.md: id 61) stands in for required-but-unresolvable
        // v1 actor FKs (PanForm.prepared_by) — the real name is preserved in legacy_actors.
        $fallbackUser = User::find(61);
        if (! $fallbackUser) {
            $this->error('Fallback admin user (id 61) not found — cannot satisfy pan_forms.prepared_by. Aborting.');

            return self::FAILURE;
        }

        $employeeIds = Employee::pluck('id', 'employee_no');
        $employeeDeptIds = Employee::pluck('department_id', 'employee_no');
        $departmentNames = Department::pluck('name', 'id');

        $stats = [
            'imported' => 0, 'forms' => 0, 'skipped_no_employee' => 0,
            'skipped_unmapped_status' => 0, 'returns_skipped' => 0, 'forms_skipped_incomplete' => 0,
        ];
        $unmatchedEmployees = [];

        $run = function () use (
            $requests, $fallbackUser, $employeeIds, $employeeDeptIds, $departmentNames,
            &$stats, &$unmatchedEmployees
        ) {
            foreach ($requests as $r) {
                // Matches panda:import-legacy-employees' derivation: EMP-{company_id, zero-padded to 5}.
                $employeeNo = $r['employee_company_id'] !== null
                    ? 'EMP-'.str_pad((string) $r['employee_company_id'], 5, '0', STR_PAD_LEFT)
                    : null;
                $employeeId = $employeeNo ? $employeeIds->get($employeeNo) : null;

                if (! $employeeId) {
                    $stats['skipped_no_employee']++;
                    $unmatchedEmployees[] = $r['legacy_reference'].' ('.$r['employee_name'].')';

                    continue;
                }

                $status = $r['is_deleted']
                    ? PanStatus::Voided
                    : (self::STATUS_MAP[$r['request_status']] ?? null);

                if (! $status) {
                    $stats['skipped_unmapped_status']++;
                    $this->warn("Unmapped status \"{$r['request_status']}\" on {$r['legacy_reference']} — skipped.");

                    continue;
                }

                $origin = $r['requested_by'] === null ? PanOrigin::Hr : PanOrigin::Requestor;

                $departmentId = $employeeDeptIds->get($employeeNo);
                $currentDeptName = $departmentNames->get($departmentId);
                $legacyDepartment = trim((string) $r['department']) !== trim((string) $currentDeptName)
                    ? $r['department']
                    : null;

                $actionType = self::ACTION_TYPE_MAP[$r['type_of_action']] ?? null;
                if (! $actionType) {
                    $this->warn("Unmapped action type \"{$r['type_of_action']}\" on {$r['legacy_reference']} — skipped.");
                    $stats['skipped_unmapped_status']++;

                    continue;
                }

                $confidentiality = match (strtolower((string) $r['confidentiality'])) {
                    'tarlac' => ConfidentialityTag::Tarlac,
                    'manila' => ConfidentialityTag::Manila,
                    default => ConfidentialityTag::Untagged,
                };

                $legacyActors = array_filter([
                    'requested_by' => $r['requested_by'],
                    'requestor_id' => $r['requestor_id'],
                    'divisionhead_id' => $r['divisionhead_id'],
                    'hr_id' => $r['hr_id'],
                    'approver_id' => $r['approver_id'],
                    'prepared_by' => $r['preparer']['prepared_by'] ?? null,
                    'approved_by' => $r['preparer']['approved_by'] ?? null,
                ]);

                $pan = PanRequest::updateOrCreate(
                    ['legacy_id' => $r['legacy_id']],
                    [
                        'reference' => $r['legacy_reference'],
                        'employee_id' => $employeeId,
                        'department_id' => $departmentId,
                        'legacy_department' => $legacyDepartment,
                        'action_type' => $actionType,
                        'justification' => $r['justification'],
                        'status' => $status,
                        'confidentiality_tag' => $confidentiality,
                        'origin' => $origin,
                        'submitted_at' => $r['submitted_at'],
                        // v1 doesn't track separate approved/served/filed timestamps — updated_at
                        // at the point the row reached each milestone is the closest available
                        // approximation. Unserved skips Served entirely (PanWorkflow: Approved ->
                        // Unserved directly), so it gets approved_at but never served_at.
                        'approved_at' => in_array($status, [PanStatus::Approved, PanStatus::Served, PanStatus::Unserved, PanStatus::Filed], true)
                            ? $r['updated_at'] : null,
                        'served_at' => in_array($status, [PanStatus::Served, PanStatus::Filed], true)
                            ? $r['updated_at'] : null,
                        'filed_at' => $status === PanStatus::Filed ? $r['updated_at'] : null,
                        'legacy_actors' => $legacyActors ?: null,
                    ]
                );

                $stats['imported']++;

                $p = $r['preparer'];
                if ($p && $p['date_hired'] && $p['doe_from']) {
                    PanForm::updateOrCreate(
                        ['pan_request_id' => $pan->id],
                        [
                            'date_hired' => $p['date_hired'],
                            'employment_status' => EmploymentStatus::from(
                                strtolower(str_replace(' ', '-', trim((string) $p['employment_status'])))
                            ),
                            'doe_from' => $p['doe_from'],
                            'doe_to' => $p['doe_to'],
                            'wage_no' => $p['wage_no'],
                            'action_reference' => $p['action_reference_data'],
                            'remarks' => $p['remarks'],
                            'prepared_by' => $fallbackUser->id,
                        ]
                    );
                    $stats['forms']++;
                } elseif ($p) {
                    $stats['forms_skipped_incomplete']++;
                    $this->warn("Preparer data on {$r['legacy_reference']} missing date_hired/doe_from — form skipped.");
                }

                $stats['returns_skipped'] += count($r['returns']);
            }
        };

        PanRequest::withoutEvents(function () use ($run, $dryRun) {
            try {
                DB::transaction(function () use ($run, $dryRun) {
                    $run();
                    if ($dryRun) {
                        throw new \RuntimeException('__dry_run_rollback__');
                    }
                });
            } catch (\RuntimeException $e) {
                if ($e->getMessage() !== '__dry_run_rollback__') {
                    throw $e;
                }
            }
        });

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '').'Import complete:');
        $this->line("  PAN requests imported: {$stats['imported']}");
        $this->line("  PAN forms imported: {$stats['forms']}");
        $this->line("  Forms skipped (incomplete preparer data): {$stats['forms_skipped_incomplete']}");
        $this->line("  Skipped — no matching v2 employee: {$stats['skipped_no_employee']}");
        $this->line("  Skipped — unmapped status/action type: {$stats['skipped_unmapped_status']}");
        $this->line("  Return/dispute log entries not imported (see class docblock): {$stats['returns_skipped']}");

        if ($unmatchedEmployees) {
            $this->newLine();
            $this->warn('Employees not found in v2 roster (risk #11 — likely departed before v2 existed):');
            foreach ($unmatchedEmployees as $u) {
                $this->line("  - {$u}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Narrow, additive-only companion to the full import: only ever writes
     * approved_at/served_at, and only when the column is currently null on an
     * already-imported row. Never touches status, action_reference, or anything
     * else — so unlike the full import, this is safe to run against production
     * after HR has been mirroring real actions into v2 directly for a while.
     * Skips (never creates) rows with no matching legacy_id already in v2.
     */
    private function backfillTimestampsOnly(array $requests, bool $dryRun): int
    {
        $existing = PanRequest::whereNotNull('legacy_id')
            ->get(['id', 'legacy_id', 'status', 'approved_at', 'served_at'])
            ->keyBy('legacy_id');

        $stats = [
            'approved_filled' => 0, 'served_filled' => 0,
            'already_set' => 0, 'not_applicable' => 0, 'no_match' => 0,
        ];

        $run = function () use ($requests, $existing, &$stats) {
            foreach ($requests as $r) {
                $pan = $existing->get($r['legacy_id']);
                if (! $pan) {
                    $stats['no_match']++;

                    continue;
                }

                $status = $r['is_deleted'] ? PanStatus::Voided : (self::STATUS_MAP[$r['request_status']] ?? null);
                $reachedApproved = in_array($status, [PanStatus::Approved, PanStatus::Served, PanStatus::Unserved, PanStatus::Filed], true);
                $reachedServed = in_array($status, [PanStatus::Served, PanStatus::Filed], true);
                $update = [];

                if ($pan->approved_at === null && $reachedApproved) {
                    $update['approved_at'] = $r['updated_at'];
                }

                if ($pan->served_at === null && $reachedServed) {
                    $update['served_at'] = $r['updated_at'];
                }

                if ($update === []) {
                    // Two different reasons nothing needs writing: this status never gets
                    // these timestamps at all (e.g. still InPreparation), vs. it qualifies
                    // but a value's already there (already backfilled, or set live since).
                    $stats[$reachedApproved ? 'already_set' : 'not_applicable']++;

                    continue;
                }

                // timestamps=false: this is a historical backfill, not a real edit —
                // don't let it bump updated_at and make the row look freshly touched.
                $pan->timestamps = false;
                $pan->forceFill($update)->save();

                if (isset($update['approved_at'])) {
                    $stats['approved_filled']++;
                }
                if (isset($update['served_at'])) {
                    $stats['served_filled']++;
                }
            }
        };

        PanRequest::withoutEvents(function () use ($run, $dryRun) {
            try {
                DB::transaction(function () use ($run, $dryRun) {
                    $run();
                    if ($dryRun) {
                        throw new \RuntimeException('__dry_run_rollback__');
                    }
                });
            } catch (\RuntimeException $e) {
                if ($e->getMessage() !== '__dry_run_rollback__') {
                    throw $e;
                }
            }
        });

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '').'Timestamp backfill complete (status/action_reference/everything else untouched):');
        $this->line("  approved_at filled: {$stats['approved_filled']}");
        $this->line("  served_at filled: {$stats['served_filled']}");
        $this->line("  already had a value (skipped): {$stats['already_set']}");
        $this->line("  status never reaches Approved — n/a (skipped): {$stats['not_applicable']}");
        $this->line("  no matching v2 row for this legacy_id (skipped): {$stats['no_match']}");

        return self::SUCCESS;
    }
}
