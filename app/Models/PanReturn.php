<?php

namespace App\Models;

use App\Enums\PanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One backward/destructive move with its mandatory reason.
 */
class PanReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'pan_request_id', 'action', 'from_status', 'to_status',
        'reason', 'details', 'returned_by',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => PanStatus::class,
            'to_status' => PanStatus::class,
        ];
    }

    public function panRequest(): BelongsTo
    {
        return $this->belongsTo(PanRequest::class);
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }
}
