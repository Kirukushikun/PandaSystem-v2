<?php

namespace App\Livewire\Concerns;

use App\Services\EmployeeAttachmentService;
use Livewire\WithFileUploads;

/**
 * Legacy record upload/remove for an Employee — HR Head only, always Manila
 * for now (see EmployeeAttachmentService). Shared by HrPreparation's
 * EmployeeHistory (which actually uses the upload/remove actions) and
 * FinalApprover's EmployeeHistory (view-only — never calls these actions,
 * but reuses the same $employee->employeeAttachments() listing via the
 * shared legacy-records partial). Every mutating action re-checks is_hr_head
 * itself rather than trusting the view to have hidden the control — the
 * v1 lesson: authorize by policy/role check, never by hiding a button.
 */
trait ManagesLegacyRecords
{
    use WithFileUploads;

    public bool $showLegacyUploadModal = false;

    /** @var \Illuminate\Http\UploadedFile[] */
    public array $newLegacyRecords = [];

    public function startLegacyUpload(): void
    {
        abort_unless(auth()->user()->is_hr_head, 403);

        $this->newLegacyRecords = [];
        $this->resetErrorBag();
        $this->showLegacyUploadModal = true;
    }

    public function removeNewLegacyRecord(int $index): void
    {
        unset($this->newLegacyRecords[$index]);
        $this->newLegacyRecords = array_values($this->newLegacyRecords);
    }

    public function uploadLegacyRecords(): void
    {
        abort_unless(auth()->user()->is_hr_head, 403);

        $this->validate([
            'newLegacyRecords' => [
                'array',
                app(EmployeeAttachmentService::class)->countRule($this->employee->employeeAttachments()->count()),
            ],
            'newLegacyRecords.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [], ['newLegacyRecords' => 'legacy record', 'newLegacyRecords.*' => 'legacy record']);

        app(EmployeeAttachmentService::class)->store($this->employee, $this->newLegacyRecords, auth()->user());

        $this->newLegacyRecords = [];
        $this->showLegacyUploadModal = false;
        $this->js("showToast('Legacy record(s) uploaded.')");
    }

    public function removeLegacyRecord(int $attachmentId): void
    {
        abort_unless(auth()->user()->is_hr_head, 403);

        // Scoped through the relation — never a bare EmployeeAttachment::find().
        $attachment = $this->employee->employeeAttachments()->findOrFail($attachmentId);
        app(EmployeeAttachmentService::class)->remove($attachment);

        $this->js("showToast('Legacy record removed.')");
    }
}
