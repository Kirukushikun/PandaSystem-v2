<?php

namespace App\Livewire\Dev;

use App\Models\Employee;
use App\Services\CarryOverService;
use App\Services\LegacyPeekService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Dev-only, hard-pinned to user id 61 — see project-overview/legacy-peek-tool-plan.md.
 * One employee's v2 record (and latest v2 PAN's action reference) next to whatever
 * v1 (PandaSystem) has on file for the same employee_no/company_id.
 */
#[Layout('layouts.app')]
#[Title('v1 Peek — PANDA')]
class LegacyPeekShow extends Component
{
    public Employee $employee;

    public ?array $legacyPeek = null;

    public bool $checked = false;

    public function mount(string $employeeNo): void
    {
        abort_unless(auth()->id() === 61, 404);

        $this->employee = Employee::where('employee_no', $employeeNo)
            ->with(['department', 'farm'])
            ->firstOrFail();

        $this->refreshPeek();
    }

    public function refreshPeek(): void
    {
        $this->legacyPeek = app(LegacyPeekService::class)->forEmployee($this->employee->employee_no);
        $this->checked = true;
    }

    /**
     * v1's `latest_pan` is picked by newest submission date, not by "most recent PAN
     * that actually has prepared data" — those aren't always the same PAN (e.g. a
     * newer PAN still sitting at "For HR Prep" has no action_reference_data yet,
     * while an older Approved one does). Prefers latest_pan when it has data, else
     * scans recent_pans for the first entry that does (works automatically once v1
     * adds action_reference_data there too — see legacy-peek-tool-plan.md's newest
     * request — until then this just falls back to latest_pan/null like before).
     */
    private function comparisonPan(): ?array
    {
        $candidates = array_filter([
            $this->legacyPeek['latest_pan'] ?? null,
            ...($this->legacyPeek['recent_pans'] ?? []),
        ]);

        foreach ($candidates as $pan) {
            if (! empty($pan['action_reference_data'] ?? null)) {
                return $pan;
            }
        }

        return $this->legacyPeek['latest_pan'] ?? null;
    }

    /**
     * "If HR Prep started a fresh PAN for this employee right now, would v2's
     * carry-over produce the same From values v1 actually has on file?" — runs
     * the real CarryOverService (not a copy of its logic) against this employee,
     * then diffs each field against v1's comparison PAN's "To" values (what v1
     * considers this employee's current real-world value per field).
     *
     * @return array<int, array{field: string, v2Simulated: string, v1Actual: string|null, match: bool|null}>
     */
    private function simulateAgainstV1(): array
    {
        $simulated = app(CarryOverService::class)->fromValuesForEmployee($this->employee);
        $v1Rows = collect($this->comparisonPan()['action_reference_data'] ?? [])
            ->keyBy('field');

        return collect($simulated)->map(function (string $v2Value, string $field) use ($v1Rows) {
            $v1Row = $v1Rows->get($field);

            return [
                'field' => $field,
                'v2Simulated' => $v2Value !== '' ? $v2Value : '—',
                'v1Actual' => $v1Row['to'] ?? null,
                // null = v1 has nothing to compare (no PAN reached HR Prep, or this field never appears there)
                'match' => $v1Row === null ? null : trim($v2Value) === trim($v1Row['to']),
            ];
        })->values()->all();
    }

    public function render()
    {
        $v2Pans = $this->employee->panRequests()
            ->with('form')
            ->orderByDesc('id')
            ->get();

        return view('livewire.dev.legacy-peek-show', [
            'v2Pans' => $v2Pans,
            'v2Latest' => $v2Pans->first(),
            'simulation' => $this->legacyPeek ? $this->simulateAgainstV1() : [],
            'comparisonPan' => $this->legacyPeek ? $this->comparisonPan() : null,
            'simulatedEmploymentStatus' => app(CarryOverService::class)->employmentStatusForEmployee($this->employee)->label(),
        ]);
    }
}
