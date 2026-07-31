<?php

namespace App\Models;

use App\Enums\ActionType;
use App\Enums\ConfidentialityTag;
use App\Enums\PanOrigin;
use App\Enums\PanStatus;
use App\Observers\PanRequestObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(PanRequestObserver::class)]
class PanRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference', 'employee_id', 'department_id', 'action_type',
        'justification', 'status', 'confidentiality_tag',
        'origin', 'requested_by', 'division_head_id', 'hr_preparer_id',
        'hr_approver_id', 'final_approver_id', 'previous_pan_id',
        'submitted_at', 'filed_at',
    ];

    protected function casts(): array
    {
        return [
            'action_type' => ActionType::class,
            'status' => PanStatus::class,
            'confidentiality_tag' => ConfidentialityTag::class,
            'origin' => PanOrigin::class,
            'submitted_at' => 'datetime',
            'filed_at' => 'datetime',
        ];
    }

    /**
     * Ongoing = blocks employee deletion: submitted, not yet filed/withdrawn/
     * voided/unserved. Drafts don't block. Mirrors PanStatus::isOngoing().
     */
    public function scopeOngoing(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            PanStatus::Draft->value,
            PanStatus::Unserved->value,
            PanStatus::Filed->value,
            PanStatus::Withdrawn->value,
            PanStatus::Voided->value,
        ]);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** The prepared paperwork — exists once HR Preparation has saved the form. */
    public function form(): HasOne
    {
        return $this->hasOne(PanForm::class);
    }

    /** Up to 3 supporting documents, oldest first. */
    public function attachments(): HasMany
    {
        return $this->hasMany(PanAttachment::class)->orderBy('id');
    }

    /** Full back-and-forth history (returns, disputes, rejections, voids). */
    public function returns(): HasMany
    {
        return $this->hasMany(PanReturn::class);
    }

    /** The most recent return/dispute/rejection — lets a queue explain *why* a PAN is back, not just that it is. */
    public function latestReturn(): HasOne
    {
        return $this->hasOne(PanReturn::class)->latestOfMany();
    }

    /** The chain: previous PAN's "To" values become this one's "From" values. */
    public function previousPan(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_pan_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(self::class, 'previous_pan_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function divisionHead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'division_head_id');
    }

    public function hrPreparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_preparer_id');
    }

    public function hrApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_approver_id');
    }

    public function finalApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_approver_id');
    }
}
