<?php

namespace App\Models;

use App\Enums\ConfidentialityTag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A scanned/legacy PAN copy attached directly to an Employee, not a specific
 * PanRequest — HR Head uploads only, for now always Manila (see the
 * migration). `path` is a randomized storage key (private disk, never
 * shown); `original_name` is what's ever displayed/downloaded.
 */
class EmployeeAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'uploaded_by', 'path', 'original_name', 'size', 'confidentiality_tag'];

    protected function casts(): array
    {
        return [
            'confidentiality_tag' => ConfidentialityTag::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
