<?php

namespace App\Livewire\HrPreparation\Concerns;

use App\Enums\ActionType;
use App\Enums\PanOrigin;
use App\Models\Employee;
use App\Models\PanRequest;
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

    public $updateAttachment = null; // Livewire temporary upload

    /** Arms and opens the modal — server state, so the upload's re-render can't shut it. */
    public function startUpdate(int $employeeId): void
    {
        $this->authorize('createHr', PanRequest::class);

        $this->updateEmployeeId = $employeeId;
        $this->updateAction = '';
        $this->updateAttachment = null;
        $this->resetErrorBag();
        $this->showUpdateModal = true;
    }

    public function createHrPan(): void
    {
        $this->authorize('createHr', PanRequest::class);

        $this->validate([
            'updateEmployeeId' => ['required', Rule::exists(Employee::class, 'id')->whereNull('deleted_at')],
            'updateAction' => ['required', Rule::enum(ActionType::class)],
            'updateAttachment' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [], ['updateAction' => 'type of action', 'updateAttachment' => 'supporting document']);

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

        // private disk — downloads only via the policy-gated route
        $path = $this->updateAttachment->storeAs(
            'pans/'.$pan->reference,
            $this->updateAttachment->getClientOriginalName()
        );
        $pan->update(['attachment_path' => $path]);

        $this->js("showToast('{$pan->reference} created at HR Preparation — tag it to begin.')");
        $this->redirectRoute('preparation.edit', $pan->reference, navigate: true);
    }
}
