<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Master roster record. Soft-deleted on "remove" so PAN history survives;
 * removal is blocked while an ongoing PAN exists (EmployeePolicy + query —
 * the ongoing() scope lands with pan_requests in the next step).
 */
class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['employee_no', 'name', 'farm_id', 'department_id', 'position'];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function panRequests(): HasMany
    {
        return $this->hasMany(PanRequest::class);
    }

    /**
     * The deletion guard (v1 lesson — policy + query, never just the UI):
     * an employee with a PAN anywhere in the workflow can't be removed.
     */
    public function hasOngoingPan(): bool
    {
        return $this->panRequests()->ongoing()->exists();
    }
}
