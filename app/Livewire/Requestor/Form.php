<?php

namespace App\Livewire\Requestor;

use App\Enums\ActionType;
use App\Enums\PanStatus;
use App\Livewire\Concerns\ValidatesWithToast;
use App\Models\Employee;
use App\Models\PanAttachment;
use App\Models\PanRequest;
use App\Services\PanAttachmentService;
use App\Services\PanReferenceGenerator;
use App\Services\PanWorkflow;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * New PAN Request + editing own drafts/returned PANs (same screen, per the mockup).
 * Drafts save without an attachment; submitting demands at least one (up to 3 total).
 */
#[Layout('layouts.app')]
#[Title('PAN Request — PANDA')]
class Form extends Component
{
    use ValidatesWithToast;
    use WithFileUploads;

    public ?PanRequest $panRequest = null;

    public ?int $employee_id = null;

    public string $action_type = '';

    public string $justification = '';

    /** @var \Illuminate\Http\UploadedFile[] newly-picked, not yet uploaded to the PAN */
    public array $newAttachments = [];

    public function mount(?string $pan = null): void
    {
        if ($pan !== null) {
            $this->panRequest = PanRequest::where('reference', $pan)->with('attachments')->firstOrFail();
            $this->authorize('update', $this->panRequest);

            $this->employee_id = $this->panRequest->employee_id;
            $this->action_type = $this->panRequest->action_type->value;
            $this->justification = (string) $this->panRequest->justification;
        } else {
            $this->authorize('create', PanRequest::class);
        }
    }

    /** Drop a just-picked file before it's ever uploaded — no server round trip needed. */
    public function removeNewAttachment(int $index): void
    {
        unset($this->newAttachments[$index]);
        $this->newAttachments = array_values($this->newAttachments);
    }

    /** Remove an already-saved document — same edit rights that unlock this whole screen. */
    public function removeAttachment(int $id): void
    {
        $attachment = PanAttachment::where('pan_request_id', $this->panRequest?->id)->findOrFail($id);
        $this->authorize('update', $this->panRequest);

        app(PanAttachmentService::class)->remove($attachment);
        $this->panRequest->refresh();
    }

    /** Employees selectable = those in the user's "Requests for" departments. */
    public function getEmployeesProperty()
    {
        return Employee::whereIn('department_id', auth()->user()->requestorDepartments()->pluck('departments.id'))
            ->with('department')
            ->orderBy('name')
            ->get();
    }

    public function saveDraft(): void
    {
        if ($this->validateOrToast($this->rules(draft: true)) === false) {
            return;
        }

        $pan = $this->persist();

        $this->js("showToast('Draft {$pan->reference} saved.')");
        $this->redirectRoute('requests.index', navigate: true);
    }

    public function submit(): void
    {
        if ($this->validateOrToast($this->rules(draft: false)) === false) {
            return;
        }

        $pan = $this->persist();

        $action = $pan->status === PanStatus::ReturnedToRequestor ? 'resubmit' : 'submit';
        $this->authorize($action, $pan);
        $pan->update([
            'status' => app(PanWorkflow::class)->apply($pan->status, $action),
            'submitted_at' => now(),
        ]);

        $this->js("showToast('{$pan->reference} submitted to the Division Head.')");
        $this->redirectRoute('requests.index', navigate: true);
    }

    private function rules(bool $draft): array
    {
        $existingCount = $this->panRequest?->attachments->count() ?? 0;

        return [
            'employee_id' => ['required', Rule::in($this->employees->pluck('id'))],
            'action_type' => ['required', Rule::enum(ActionType::class)],
            'justification' => $draft ? ['nullable', 'string'] : ['required', 'string', 'min:10'],
            // drafts may omit documents entirely; submission requires at least one, up to 3 total
            'newAttachments' => ['array', app(PanAttachmentService::class)->countRule($existingCount, required: ! $draft)],
            'newAttachments.*' => ['file', 'mimes:pdf', 'max:10240'],
        ];
    }

    private function persist(): PanRequest
    {
        $employee = Employee::findOrFail($this->employee_id);

        if ($this->panRequest === null) {
            $this->panRequest = PanRequest::create([
                'reference' => app(PanReferenceGenerator::class)->next(),
                'employee_id' => $employee->id,
                'department_id' => $employee->department_id,
                'action_type' => $this->action_type,
                'justification' => $this->justification ?: null,
                'status' => PanStatus::Draft,
                'requested_by' => auth()->id(),
            ]);
        } else {
            $this->panRequest->update([
                'employee_id' => $employee->id,
                'department_id' => $employee->department_id,
                'action_type' => $this->action_type,
                'justification' => $this->justification ?: null,
            ]);
        }

        if ($this->newAttachments !== []) {
            app(PanAttachmentService::class)->store($this->panRequest, $this->newAttachments);
            $this->newAttachments = [];
        }

        return $this->panRequest->fresh('attachments');
    }

    public function render()
    {
        return view('livewire.requestor.form', [
            'actionTypes' => ActionType::cases(),
            'selectedEmployee' => $this->employee_id ? $this->employees->firstWhere('id', $this->employee_id) : null,
            'departments' => auth()->user()->requestorDepartments->pluck('name')->implode(', '),
        ]);
    }
}
