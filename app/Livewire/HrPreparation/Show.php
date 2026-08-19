<?php

namespace App\Livewire\HrPreparation;

use App\Enums\ConfidentialityTag;
use App\Enums\PanStatus;
use App\Models\PanForm;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('View Request — PANDA')]
class Show extends Component
{
    public PanRequest $panRequest;

    /**
     * Temporary quick-fix (see PanRequestPolicy::patchMissingPrintDetails): lets HR
     * fill in a still-blank "From" value and/or pick a Division Head for print's
     * "Recommended By", straight from this view — for PANs that predate the fixes
     * made to the live workflow. Deliberately narrow: only touches
     * action_reference.*.from and division_head_id, nothing else — no allowance
     * rows, no pan_returns entry, no notification, no status change.
     */
    public bool $showQuickFix = false;

    public array $emptyFromValues = [];

    public string $selectedDivisionHead = '';

    /** Statuses where division_head_id should already be set — the PAN has moved past confirmation. */
    private const NEEDS_DIVISION_HEAD_STATUSES = [
        PanStatus::ForHrApproval, PanStatus::ForFinalApproval, PanStatus::Approved,
        PanStatus::Served, PanStatus::Unserved, PanStatus::Filed,
    ];

    /**
     * Place/Position are seeded from the Employee record, so they're never
     * genuinely blank — but HR asked for a one-time chance to correct them here
     * too (the employee's assignment may have been recorded wrong originally),
     * unlike the other fixed fields below which only ever get filled when blank.
     */
    private const ALWAYS_EDITABLE_FIELDS = ['place' => 'Place of Assignment', 'position' => 'Position'];

    public function mount(string $pan): void
    {
        $this->panRequest = PanRequest::where('reference', $pan)
            ->with(['employee.department', 'requestedBy', 'form.preparedBy', 'returns.returnedBy', 'attachments', 'latestReturn'])
            ->firstOrFail();

        $this->authorize('view', $this->panRequest);
    }

    /** field => label, for every action_reference row still sitting on the "—" placeholder (excludes Place/Position — see ALWAYS_EDITABLE_FIELDS). */
    private function emptyFromFields(): array
    {
        return collect($this->panRequest->form?->action_reference ?? [])
            ->reject(fn (array $row) => array_key_exists($row['field'], self::ALWAYS_EDITABLE_FIELDS))
            ->filter(fn (array $row) => in_array($row['from'], ['', '—'], true))
            ->mapWithKeys(fn (array $row) => [$row['field'] => PanForm::fieldLabel($row['field'])])
            ->all();
    }

    /** field => label, for Place/Position — offered unconditionally, not just when blank. */
    private function alwaysEditableFields(): array
    {
        $reference = collect($this->panRequest->form?->action_reference ?? []);

        return collect(self::ALWAYS_EDITABLE_FIELDS)
            ->filter(fn (string $label, string $field) => $reference->firstWhere('field', $field) !== null)
            ->all();
    }

    /** Only once the PAN has actually moved past confirmation — before that, null is still legitimate. */
    private function needsDivisionHead(): bool
    {
        return $this->panRequest->division_head_id === null
            && in_array($this->panRequest->status, self::NEEDS_DIVISION_HEAD_STATUSES, true);
    }

    /** Manila -> DH Head population, same as who actually confirms it live; routine -> the PAN's own department head(s). */
    private function divisionHeadCandidates(): Collection
    {
        return $this->panRequest->confidentiality_tag === ConfidentialityTag::Manila
            ? User::where('is_dh_head', true)->orderBy('name')->get()
            : $this->panRequest->department->heads()->orderBy('name')->get();
    }

    public function startQuickFix(): void
    {
        $this->authorize('patchMissingPrintDetails', $this->panRequest);

        $reference = collect($this->panRequest->form->action_reference ?? []);
        $this->emptyFromValues = array_fill_keys(array_keys($this->emptyFromFields()), '');
        foreach (array_keys($this->alwaysEditableFields()) as $field) {
            $current = $reference->firstWhere('field', $field)['from'] ?? '';
            $this->emptyFromValues[$field] = in_array($current, ['', '—'], true) ? '' : $current;
        }
        $this->selectedDivisionHead = '';
        $this->showQuickFix = true;
    }

    public function saveQuickFix(): void
    {
        $this->authorize('patchMissingPrintDetails', $this->panRequest);
        $this->validate([
            'emptyFromValues.*' => 'nullable|string|max:255',
            'selectedDivisionHead' => 'nullable|integer|exists:users,id',
        ]);

        $form = $this->panRequest->form;
        $reference = collect($form->action_reference)->map(function (array $row) {
            $typed = trim($this->emptyFromValues[$row['field']] ?? '');
            $wasBlank = in_array($row['from'], ['', '—'], true);
            $alwaysEditable = array_key_exists($row['field'], self::ALWAYS_EDITABLE_FIELDS);

            if ($typed !== '' && ($wasBlank || $alwaysEditable)) {
                $row['from'] = $typed;
            }

            return $row;
        })->all();

        $form->update(['action_reference' => $reference]);

        // Never overwrite an already-recorded Division Head — same "only fill the
        // gap" rule as the From values above.
        if ($this->selectedDivisionHead !== '' && $this->needsDivisionHead()) {
            $this->panRequest->update(['division_head_id' => (int) $this->selectedDivisionHead]);
        }

        $this->panRequest->refresh();
        $this->showQuickFix = false;
        $this->emptyFromValues = [];
        $this->selectedDivisionHead = '';
        $this->js("showToast('Missing print detail(s) saved.')");
    }

    /** Same journey strip as the other Show views — one truth for stage progress. */
    public function tracker(): array
    {
        $stages = ['Submitted', 'Division Head', 'HR Preparation', 'DH Confirmation', 'HR Approval', 'Final Approval', 'Served', 'Filed'];

        $current = match ($this->panRequest->status) {
            PanStatus::Draft, PanStatus::ReturnedToRequestor => 'Submitted',
            PanStatus::WithDivisionHead => 'Division Head',
            PanStatus::AwaitingTag, PanStatus::InPreparation, PanStatus::ReturnedToPreparer => 'HR Preparation',
            PanStatus::ForConfirmation => 'DH Confirmation',
            PanStatus::ForHrApproval => 'HR Approval',
            PanStatus::ForFinalApproval => 'Final Approval',
            PanStatus::Approved, PanStatus::Served => 'Served',
            PanStatus::Filed => '*',
            PanStatus::Withdrawn, PanStatus::Voided, PanStatus::Unserved => null,
        };

        return [$stages, $current];
    }

    public function render()
    {
        [$stages, $current] = $this->tracker();

        return view('livewire.hr-preparation.show', [
            'pan' => $this->panRequest,
            'form' => $this->panRequest->form,
            'rows' => $this->panRequest->form?->displayRows() ?? [],
            'stages' => $stages,
            'current' => $current,
            'isHrHead' => auth()->user()->is_hr_head,
            'emptyFromFields' => $this->emptyFromFields(),
            'alwaysEditableFields' => $this->alwaysEditableFields(),
            'needsDivisionHead' => $this->needsDivisionHead(),
            'divisionHeadCandidates' => $this->needsDivisionHead() ? $this->divisionHeadCandidates() : collect(),
        ]);
    }
}
