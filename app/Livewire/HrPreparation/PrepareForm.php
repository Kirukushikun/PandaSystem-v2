<?php

namespace App\Livewire\HrPreparation;

use App\Enums\ConfidentialityTag;
use App\Enums\PanStatus;
use App\Livewire\Concerns\ValidatesWithToast;
use App\Models\PanAttachment;
use App\Models\PanForm;
use App\Models\PanRequest;
use App\Services\CarryOverService;
use App\Services\PanAttachmentService;
use App\Services\PanWorkflow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The preparation screen: tagging (while Awaiting Tag), then the official
 * paperwork — employment details + the Action Reference editor that writes
 * the pan_forms.action_reference JSON the print view consumes.
 */
#[Layout('layouts.app')]
#[Title('Prepare PAN — PANDA')]
class PrepareForm extends Component
{
    use ValidatesWithToast;
    use WithFileUploads;

    public PanRequest $panRequest;

    /** Tag choice (only actionable while status is Awaiting Tag). */
    public string $tag = '';

    // Employment details
    public string $date_hired = '';

    public string $doe_from = '';

    public string $doe_to = '';

    public string $wage_no = '';

    public string $remarks = '';

    /** Editable dropdown, not free text — EmploymentStatus::cases() are the only valid values. */
    public string $employment_status = '';

    /** Fixed Action Reference rows: field => value. From is carried; To is typed. */
    public array $fromValues = [];

    public array $toValues = [];

    /** Dynamic allowance rows: [{label, from, to}, …]. */
    public array $allowances = [];

    public string $allowanceType = 'Communication Allowance';

    /** Previous-PAN "See more" inline summary. */
    public bool $showPrev = false;

    // Shared reason modal — void, or send the PAN back to the Requestor
    // (e.g. missing supporting documents the preparer can't fix themselves).
    public bool $showModal = false;

    public string $modalAction = 'void';

    public string $reason = '';

    public string $details = '';

    /** @var \Illuminate\Http\UploadedFile[] preparer uploading on the requestor's behalf */
    public array $newAttachments = [];

    public const ALLOWANCE_TYPES = [
        'Communication Allowance', 'Meal Allowance', 'Living Allowance',
        'Transportation Allowance', 'Clothing Allowance', 'Fuel Allowance',
        'Management Allowance', 'Developmental Assignment Allowance',
        'Professional Allowance', 'Interim Allowance', 'Training Allowance',
        'Mancom Allowance', 'Allowance (generic)',
    ];

    public const FIXED_FIELDS = ['section', 'place', 'head', 'position', 'joblevel', 'basic'];

    /** Monthly SL/VL accrual pairs (finalized 2026-07-18) — the Leave Credits "To" choices. */
    public const LEAVE_CREDIT_OPTIONS = [
        'SL - 5.00 | VL - 5.00',
        'SL - 4.58 | VL - 4.58',
        'SL - 4.17 | VL - 4.17',
        'SL - 3.75 | VL - 3.75',
        'SL - 3.33 | VL - 3.33',
        'SL - 2.92 | VL - 2.92',
        'SL - 2.50 | VL - 2.50',
        'SL - 2.08 | VL - 2.08',
        'SL - 1.67 | VL - 1.67',
        'SL - 1.25 | VL - 1.25',
        'SL - 0.83 | VL - 0.83',
        'SL - 0.42 | VL - 0.42',
    ];

    public function mount(string $pan): void
    {
        $this->panRequest = PanRequest::where('reference', $pan)
            ->with(['employee.farm', 'employee.department', 'form', 'previousPan.form.preparedBy', 'attachments'])
            ->firstOrFail();

        // Tagging and preparing are different rights; anything past preparation 403s here.
        $this->authorize($this->panRequest->status === PanStatus::AwaitingTag ? 'tag' : 'prepare', $this->panRequest);

        $this->hydrateForm();
    }

    private function hydrateForm(): void
    {
        $carry = app(CarryOverService::class);
        $form = $this->panRequest->form;

        if ($form !== null) {
            $this->date_hired = $form->date_hired?->format('Y-m-d') ?? '';
            $this->doe_from = $form->doe_from?->format('Y-m-d') ?? '';
            $this->doe_to = $form->doe_to?->format('Y-m-d') ?? '';
            $this->wage_no = (string) $form->wage_no;
            $this->remarks = (string) $form->remarks;
            $this->employment_status = $form->employment_status->value;

            foreach ($form->action_reference as $row) {
                if (in_array($row['field'], [...self::FIXED_FIELDS, 'leavecredits'], true)) {
                    $this->fromValues[$row['field']] = $row['from'];
                    $this->toValues[$row['field']] = $row['to'];
                } else {
                    $this->allowances[] = ['label' => $row['field'], 'from' => $row['from'], 'to' => $row['to']];
                }
            }
        } else {
            // Fresh preparation: the previous approved PAN's "To" values seed the "From" side —
            // fixed fields and allowance rows both, so a recurring allowance doesn't have to be
            // retyped from scratch on every PAN.
            $this->fromValues = $carry->fromValuesFor($this->panRequest);
            $this->allowances = $carry->allowancesFor($this->panRequest);
            $this->date_hired = $carry->previousPanFor($this->panRequest->employee, $this->panRequest)
                ?->form->date_hired?->format('Y-m-d') ?? '';
            $this->employment_status = $carry->employmentStatusFor($this->panRequest)->value;

            // Division Head approval was proxy-approved — the preparer must document why (remarks
            // becomes required in rules()); seed a real, editable starting value rather than a
            // placeholder, since an untouched placeholder submits empty and would fail that rule.
            if ($this->remarks === '' && $this->panRequest->wasProxyApproved()) {
                $log = $this->panRequest->returns()->where('action', 'proxy_approve_dh')->latest('id')->first();
                if ($log !== null) {
                    $this->remarks = "Division Head approval was proxy-approved — {$log->reason}";
                }
            }
        }

        if ($this->panRequest->action_type->includesLeaveCredits()) {
            $this->fromValues['leavecredits'] ??= '';
            $this->toValues['leavecredits'] ??= '';
        }
    }

    public function applyTag(): void
    {
        $this->authorize('tag', $this->panRequest);
        $this->validate(['tag' => 'required|in:tarlac,manila'], [], ['tag' => 'confidentiality tag']);

        $this->panRequest->update([
            'status' => app(PanWorkflow::class)->apply($this->panRequest->status, 'tag'),
            'confidentiality_tag' => $this->tag,
            'hr_preparer_id' => auth()->id(),
        ]);

        // A normal preparer who tags Manila hands the record to the HR Head — and loses it.
        if ($this->tag === ConfidentialityTag::Manila->value && ! auth()->user()->is_hr_head) {
            $this->js("showToast('{$this->panRequest->reference} tagged Manila — now confidential, HR Head Preparers only.')");
            $this->redirectRoute('preparation.queue', navigate: true);

            return;
        }

        $this->js("showToast('{$this->panRequest->reference} tagged — preparation unlocked.')");
        $this->panRequest->refresh();
    }

    public function togglePrev(): void
    {
        $this->showPrev = ! $this->showPrev;
    }


    /** A freshly-added row has no carry-over source at all — "From" is inputtable, unlike a carried row's locked text. */
    public function addAllowance(): void
    {
        $this->allowances[] = ['label' => $this->allowanceType, 'from' => '', 'to' => '', 'editable' => true];
    }

    public function removeAllowance(int $index): void
    {
        unset($this->allowances[$index]);
        $this->allowances = array_values($this->allowances);
    }

    public function save(): void
    {
        $this->authorize('prepare', $this->panRequest);
        if ($this->validateOrToast($this->rules()) === false) {
            return;
        }

        $this->persist();
        $this->js("showToast('{$this->panRequest->reference} saved — you can continue anytime.')");
    }

    public function submit(): void
    {
        $this->authorize('prepare', $this->panRequest);
        if ($this->validateOrToast($this->rules()) === false) {
            return;
        }

        $this->persist();

        $action = $this->panRequest->status === PanStatus::ReturnedToPreparer
            ? 'resubmit_to_hr'              // rework goes straight back to the HR Approver
            : 'submit_for_confirmation';
        $this->panRequest->update([
            'status' => app(PanWorkflow::class)->apply($this->panRequest->status, $action),
        ]);

        $this->js($action === 'resubmit_to_hr'
            ? "showToast('{$this->panRequest->reference} resubmitted to the HR Approver.')"
            : "showToast('{$this->panRequest->reference} submitted for Division Head Confirmation.')");
        $this->redirectRoute('preparation.queue', navigate: true);
    }

    /** Arms and opens the reason modal — server state, so re-renders can't shut it. */
    public function startReason(string $action): void
    {
        $this->modalAction = $action; // void | send_back_to_requestor
        $this->reason = '';
        $this->details = '';
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function submitReason(): void
    {
        $ability = $this->modalAction === 'void' ? 'void' : 'sendBackToRequestor';
        $this->authorize($ability, $this->panRequest);
        $this->validate([
            'reason' => 'required|string|max:255',
            'details' => 'required_if:reason,Custom reason…|nullable|string|max:1000',
        ], ['details.required_if' => 'Describe the custom reason in the details field.']);

        $from = $this->panRequest->status;
        $to = app(PanWorkflow::class)->apply($from, $this->modalAction);

        $this->panRequest->update(['status' => $to]);
        $this->panRequest->returns()->create([
            'action' => $this->modalAction,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $this->reason,
            'details' => $this->details ?: null,
            'returned_by' => auth()->id(),
        ]);

        $this->js($this->modalAction === 'void'
            ? "showToast('{$this->panRequest->reference} voided — kept on record with the reason.')"
            : "showToast('{$this->panRequest->reference} sent back to the Requestor with your reason.')");
        $this->redirectRoute('preparation.queue', navigate: true);
    }

    /** Drop a just-picked file before it's ever uploaded — no server round trip needed. */
    public function removeNewAttachment(int $index): void
    {
        unset($this->newAttachments[$index]);
        $this->newAttachments = array_values($this->newAttachments);
    }

    /** Preparer uploading on the Requestor's behalf — "sent it over Viber" scenarios. */
    public function uploadAttachments(): void
    {
        $this->authorize('prepare', $this->panRequest);
        $this->validate([
            'newAttachments' => ['array', app(PanAttachmentService::class)->countRule($this->panRequest->attachments->count(), required: false)],
            'newAttachments.*' => ['file', 'mimes:pdf', 'max:10240'],
        ]);

        if ($this->newAttachments !== []) {
            app(PanAttachmentService::class)->store($this->panRequest, $this->newAttachments);
            $this->newAttachments = [];
            $this->panRequest->refresh();
            $this->js("showToast('Document(s) added to {$this->panRequest->reference}.')");
        }
    }

    public function removeAttachment(int $id): void
    {
        $this->authorize('prepare', $this->panRequest);
        $attachment = PanAttachment::where('pan_request_id', $this->panRequest->id)->findOrFail($id);

        app(PanAttachmentService::class)->remove($attachment);
        $this->panRequest->refresh();
    }

    private function rules(): array
    {
        return [
            'date_hired' => 'required|date',
            'doe_from' => 'required|date',
            'doe_to' => 'nullable|date|after_or_equal:doe_from',
            'wage_no' => $this->panRequest->action_type->requiresWageNumber() ? 'required|string|max:50' : 'nullable|string|max:50',
            'toValues.*' => 'nullable|string|max:255',
            'toValues.leavecredits' => ['nullable', \Illuminate\Validation\Rule::in(self::LEAVE_CREDIT_OPTIONS)],
            'allowances.*.to' => 'nullable|string|max:255',
            'remarks' => $this->panRequest->wasProxyApproved() ? 'required|string|max:1000' : 'nullable|string|max:1000',
            'employment_status' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\EmploymentStatus::class)],
        ];
    }

    private function persist(): void
    {
        // Leave Credits sits BEFORE Basic Pay (and any allowances) in the reference order.
        $fields = ['section', 'place', 'head', 'position', 'joblevel'];
        if ($this->panRequest->action_type->includesLeaveCredits()) {
            $fields[] = 'leavecredits';
        }
        $fields[] = 'basic';

        // Blank "To" = no change: the From value carries through (print view shows both sides).
        $reference = array_map(fn (string $field) => [
            'field' => $field,
            'from' => $this->fromValues[$field] !== '' ? $this->fromValues[$field] : '—',
            'to' => trim($this->toValues[$field] ?? '') !== ''
                ? trim($this->toValues[$field])
                : ($this->fromValues[$field] !== '' ? $this->fromValues[$field] : '—'),
        ], $fields);

        foreach ($this->allowances as $allowance) {
            $reference[] = [
                'field' => $allowance['label'],
                'from' => $allowance['from'] !== '' ? $allowance['from'] : '—',
                'to' => trim($allowance['to']) !== '' ? trim($allowance['to']) : '—',
            ];
        }

        PanForm::updateOrCreate(
            ['pan_request_id' => $this->panRequest->id],
            [
                'date_hired' => $this->date_hired,
                'employment_status' => $this->employment_status,
                'doe_from' => $this->doe_from,
                'doe_to' => $this->doe_to ?: null,
                'wage_no' => $this->panRequest->action_type->requiresWageNumber() ? $this->wage_no : null,
                'action_reference' => $reference,
                'remarks' => $this->remarks ?: null,
                'prepared_by' => auth()->id(),
            ]
        );

        // First save links the carry-over chain for good.
        if ($this->panRequest->previous_pan_id === null) {
            $previous = app(CarryOverService::class)->previousPanFor($this->panRequest->employee, $this->panRequest);
            if ($previous !== null) {
                $this->panRequest->update(['previous_pan_id' => $previous->id]);
            }
        }

        $this->panRequest->refresh();
    }

    /** The tag/role outcomes, now driven by the real record (the mockup's simulation is gone). */
    public function lockState(): array
    {
        if ($this->panRequest->status === PanStatus::AwaitingTag) {
            return [true, 'note info', 'i',
                'Locked — tag the request (Tarlac or Manila) before preparation can begin. Any preparer may apply the initial tag.'];
        }
        if ($this->panRequest->confidentiality_tag === ConfidentialityTag::Manila) {
            return [false, 'note manila', '●',
                '<b>Tagged Manila</b>&nbsp;— unlocked for you as HR Head Preparer. The DH Head (not the regular Division Head) will act at the confirmation stage.'];
        }

        return [false, 'note info', 'i',
            'Tagged Tarlac (routine) — unlocked. Continue with Employment Details and the Action Reference.'];
    }

    public function render()
    {
        [$locked, $noteClass, $noteIcon, $noteMsg] = $this->lockState();

        return view('livewire.hr-preparation.prepare-form', [
            'locked' => $locked,
            'noteClass' => $noteClass,
            'noteIcon' => $noteIcon,
            'noteMsg' => $noteMsg,
            'pan' => $this->panRequest,
            'previous' => $this->panRequest->previousPan
                ?? app(CarryOverService::class)->previousPanFor($this->panRequest->employee, $this->panRequest),
            'isHrHead' => auth()->user()->is_hr_head,
        ]);
    }
}
