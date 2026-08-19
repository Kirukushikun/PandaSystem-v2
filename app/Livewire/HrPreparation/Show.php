<?php

namespace App\Livewire\HrPreparation;

use App\Enums\PanStatus;
use App\Models\PanForm;
use App\Models\PanRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('View Request — PANDA')]
class Show extends Component
{
    public PanRequest $panRequest;

    /**
     * Temporary quick-fix (see PanRequestPolicy::patchEmptyFromValues): lets HR
     * fill in a still-blank "From" value straight from this view, for PANs that
     * got stuck with a dash before the no-previous-PAN default was corrected.
     * Deliberately narrow — only touches action_reference.*.from, nothing else:
     * no allowance rows added/removed, no pan_returns entry, no notification.
     */
    public bool $showQuickFix = false;

    public array $emptyFromValues = [];

    public function mount(string $pan): void
    {
        $this->panRequest = PanRequest::where('reference', $pan)
            ->with(['employee.department', 'requestedBy', 'form.preparedBy', 'returns.returnedBy', 'attachments', 'latestReturn'])
            ->firstOrFail();

        $this->authorize('view', $this->panRequest);
    }

    /** field => label, for every action_reference row still sitting on the "—" placeholder. */
    private function emptyFromFields(): array
    {
        return collect($this->panRequest->form?->action_reference ?? [])
            ->filter(fn (array $row) => in_array($row['from'], ['', '—'], true))
            ->mapWithKeys(fn (array $row) => [$row['field'] => PanForm::fieldLabel($row['field'])])
            ->all();
    }

    public function startQuickFix(): void
    {
        $this->authorize('patchEmptyFromValues', $this->panRequest);

        $this->emptyFromValues = array_fill_keys(array_keys($this->emptyFromFields()), '');
        $this->showQuickFix = true;
    }

    public function saveQuickFix(): void
    {
        $this->authorize('patchEmptyFromValues', $this->panRequest);
        $this->validate(['emptyFromValues.*' => 'nullable|string|max:255']);

        $form = $this->panRequest->form;
        $reference = collect($form->action_reference)->map(function (array $row) {
            $typed = trim($this->emptyFromValues[$row['field']] ?? '');
            if ($typed !== '' && in_array($row['from'], ['', '—'], true)) {
                $row['from'] = $typed;
            }

            return $row;
        })->all();

        $form->update(['action_reference' => $reference]);
        $this->panRequest->refresh();
        $this->showQuickFix = false;
        $this->emptyFromValues = [];
        $this->js("showToast('Missing From value(s) saved.')");
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
        ]);
    }
}
