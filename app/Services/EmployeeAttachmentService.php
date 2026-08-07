<?php

namespace App\Services;

use App\Enums\ConfidentialityTag;
use App\Models\Employee;
use App\Models\EmployeeAttachment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Legacy record uploads on an Employee — mirrors PanAttachmentService's shape
 * (storage convention + removal in one place), but these represent an
 * employee's whole back-history rather than one PAN's paperwork, so the cap
 * is higher. Every upload is Manila for now (see the migration) — nothing
 * here decides WHO may call it, that's the caller's own policy check first.
 */
class EmployeeAttachmentService
{
    public const MAX_ATTACHMENTS = 10;

    /**
     * @param  \Illuminate\Http\UploadedFile[]  $files
     */
    public function store(Employee $employee, array $files, User $uploader): void
    {
        foreach ($files as $file) {
            // Read before store() — a Livewire temporary file's getSize() lazily
            // reads from the livewire-tmp disk, and store() moves it off that disk.
            $originalName = $file->getClientOriginalName();
            $size = $file->getSize();

            $path = $file->store('employees/'.$employee->employee_no.'/legacy');

            EmployeeAttachment::create([
                'employee_id' => $employee->id,
                'uploaded_by' => $uploader->id,
                'path' => $path,
                'original_name' => $originalName,
                'size' => $size,
                'confidentiality_tag' => ConfidentialityTag::Manila,
            ]);
        }
    }

    public function remove(EmployeeAttachment $attachment): void
    {
        Storage::delete($attachment->path);
        $attachment->delete();
    }

    /**
     * One combined-count rule for an array-bound file input: existing rows on
     * the employee plus whatever's newly selected must not exceed the cap.
     */
    public function countRule(int $existingCount): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($existingCount) {
            if ($existingCount + count($value) > self::MAX_ATTACHMENTS) {
                $fail('You can attach at most '.self::MAX_ATTACHMENTS.' legacy records in total.');
            }
        };
    }
}
