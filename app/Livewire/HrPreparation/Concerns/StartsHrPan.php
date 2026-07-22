<?php

namespace App\Livewire\HrPreparation\Concerns;

use App\Enums\ActionType;
use App\Enums\PanOrigin;
use App\Models\Employee;
use App\Models\PanRequest;
use App\Services\PanAttachmentService;
use App\Services\PanReferenceGenerator;
use App\Services\PanWorkflow;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;

/**
 * "Update PAN": starts a new PAN directly at HR Preparation (origin='hr'),
 * skipping the Requestor and Division Head stages — typically for Wage Orders.
 * Shared by the Employees lens and the per-employee PAN history.
 */
trait StartsHrPan
{
    use WithFileUploads;

    public bool $showUpdateModal = false;

    public ?int $updateEmployeeId = null;

    public string $updateAction = '';

    /** @var \Illuminate\Http\UploadedFile[] */
    public array $updateAttachments = [];

    /** Arms and opens the modal — server state, so the upload's re-render can't shut it. */
    public function startUpdate(int $employeeId): void
    {
        $this->authorize('createHr', PanRequest::class);

        $this->updateEmployeeId = $employeeId;
        $this->updateAction = '';
        $this->updateAttachments = [];
        $this->resetErrorBag();
        $this->showUpdateModal = true;
    }

    /** Drop a just-picked file before it's ever uploaded — no server round trip needed. */
    public function removeUpdateAttachment(int $index): void
    {
        unset($this->updateAttachments[$index]);
        $this->updateAttachments = array_values($this->updateAttachments);
    }

    public function createHrPan(): void
    {
        $this->authorize('createHr', PanRequest::class);

        $this->validate([
            'updateEmployeeId' => ['required', Rule::exists(Employee::class, 'id')->whereNull('deleted_at')],
            'updateAction' => ['required', Rule::enum(ActionType::class)],
            'updateAttachments' => ['array', app(PanAttachmentService::class)->countRule(0, required: true)],
            'updateAttachments.*' => ['file', 'mimes:pdf', 'max:10240'],
        ], [], ['updateAction' => 'type of action', 'updateAttachments' => 'supporting document']);

        $employee = Employee::findOrFail($this->updateEmployeeId);

        $pan = PanRequest::create([
            'reference' => app(PanReferenceGenerator::class)->next(),
            'employee_id' => $employee->id,
            'department_id' => $employee->department_id,
            'action_type' => $this->updateAction,
            'status' => app(PanWorkflow::class)->initialStatus(PanOrigin::Hr), // enters at Awaiting Tag
            'origin' => PanOrigin::Hr,
            'requested_by' => null, // no requestor — the HR preparer is recorded at tagging
            'submitted_at' => now(),
        ]);

        app(PanAttachmentService::class)->store($pan, $this->updateAttachments);

        $this->js("showToast('{$pan->reference} created at HR Preparation — tag it to begin.')");
        $this->redirectRoute('preparation.edit', $pan->reference, navigate: true);
    }
}
