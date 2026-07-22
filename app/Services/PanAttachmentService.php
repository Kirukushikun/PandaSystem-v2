<?php

namespace App\Services;

use App\Models\PanAttachment;
use App\Models\PanRequest;
use Illuminate\Support\Facades\Storage;

/**
 * Up to 3 supporting documents per PAN. Centralized so the two upload entry
 * points (Requestor's own Form, HR's "Update PAN") share one rule for the
 * cap, one storage convention, and one removal path — nothing here decides
 * WHO may call it; that's each caller's own policy check first.
 */
class PanAttachmentService
{
    public const MAX_ATTACHMENTS = 3;

    /**
     * @param  \Illuminate\Http\UploadedFile[]  $files
     */
    public function store(PanRequest $pan, array $files): void
    {
        foreach ($files as $file) {
            // Randomized stored name — original_name is what's ever shown to a
            // person, so collisions between filenames are a non-issue here.
            $path = $file->store('pans/'.$pan->reference);

            PanAttachment::create([
                'pan_request_id' => $pan->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]);
        }
    }

    public function remove(PanAttachment $attachment): void
    {
        Storage::delete($attachment->path);
        $attachment->delete();
    }

    /**
     * One combined-count rule for an array-bound file input: existing rows on
     * the PAN (if editing) plus whatever's newly selected must not exceed the
     * cap, and — when required — at least one of either must exist.
     */
    public function countRule(int $existingCount, bool $required): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($existingCount, $required) {
            $total = $existingCount + count($value);

            if ($total > self::MAX_ATTACHMENTS) {
                $fail('You can attach at most '.self::MAX_ATTACHMENTS.' supporting documents in total.');
            } elseif ($required && $total === 0) {
                $fail('At least one supporting document is required.');
            }
        };
    }
}
