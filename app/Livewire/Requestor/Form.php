<?php

namespace App\Livewire\Requestor;

use App\Enums\ActionType;
use App\Enums\PanStatus;
use App\Models\Employee;
use App\Models\PanRequest;
use App\Services\PanReferenceGenerator;
use App\Services\PanWorkflow;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * New PAN Request + editing own drafts/returned PANs (same screen, per the mockup).
 * Drafts save without an attachment; submitting demands the PDF.
 */
#[Layout('layouts.app')]
#[Title('PAN Request — PANDA')]
class Form extends Component
{
    use WithFileUploads;

    public ?PanRequest $panRequest = null;

    public ?int $employee_id = null;

    public string $action_type = '';

    public string $justification = '';

    public $attachment = null; // new upload (Livewire temporary file)

    public function mount(?string $pan = null): void
    {
        if ($pan !== null) {
            $this->panRequest = PanRequest::where('reference', $pan)->firstOrFail();
            $this->authorize('update', $this->panRequest);

            $this->employee_id = $this->panRequest->employee_id;
            $this->action_type = $this->panRequest->action_type->value;
            $this->justification = (string) $this->panRequest->justification;
        } else {
            $this->authorize('create', PanRequest::class);
        }
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
        $this->validate($this->rules(draft: true));

        $pan = $this->persist();

        $this->js("showToast('Draft {$pan->reference} saved.')");
        $this->redirectRoute('requests.index', navigate: true);
    }

    public function submit(): void
    {
        $this->validate($this->rules(draft: false));

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
        return [
            'employee_id' => ['required', Rule::in($this->employees->pluck('id'))],
            'action_type' => ['required', Rule::enum(ActionType::class)],
            'justification' => $draft ? ['nullable', 'string'] : ['required', 'string', 'min:10'],
            // drafts may omit the PDF; submission requires one (new or already uploaded)
            'attachment' => [
                $draft || $this->panRequest?->attachment_path ? 'nullable' : 'required',
                'file', 'mimes:pdf', 'max:10240',
            ],
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

        if ($this->attachment) {
            // private disk — downloads only via the policy-gated route
            $path = $this->attachment->storeAs(
                'pans/'.$this->panRequest->reference,
                $this->attachment->getClientOriginalName()
            );
            $this->panRequest->update(['attachment_path' => $path]);
        }

        return $this->panRequest->fresh();
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
