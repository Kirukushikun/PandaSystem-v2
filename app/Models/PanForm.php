<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The prepared paperwork. action_reference holds the ordered {field, from, to}
 * rows exactly as the print view consumes them.
 */
class PanForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'pan_request_id', 'date_hired', 'employment_status', 'doe_from',
        'doe_to', 'wage_no', 'action_reference', 'remarks', 'prepared_by',
    ];

    protected function casts(): array
    {
        return [
            'date_hired' => 'date',
            'employment_status' => EmploymentStatus::class,
            'doe_from' => 'date',
            'doe_to' => 'date',
            'action_reference' => 'array',
        ];
    }

    public function panRequest(): BelongsTo
    {
        return $this->belongsTo(PanRequest::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
