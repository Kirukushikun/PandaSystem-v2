<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Local profile + permissions only. No password column — credentials are checked
 * against the external company system (ExternalAuthenticator). Roles are the 8
 * fixed booleans below + Gates/Policies; deliberately no spatie/laravel-permission.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'external_id', 'username', 'email', 'name', 'position', 'farm_id',
        'is_requestor', 'is_division_head', 'is_hr_preparer',
        'is_hr_approver', 'is_final_approver',
        'is_hr_head', 'is_dh_head', 'is_admin',
        'esign_path',
    ];

    protected $hidden = ['remember_token'];

    protected function casts(): array
    {
        return [
            'is_requestor' => 'boolean',
            'is_division_head' => 'boolean',
            'is_hr_preparer' => 'boolean',
            'is_hr_approver' => 'boolean',
            'is_final_approver' => 'boolean',
            'is_hr_head' => 'boolean',
            'is_dh_head' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /** Departments this user may raise PANs for ("Requests for"). */
    public function requestorDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user_requestor');
    }

    /** Departments this user heads (independent of requesting; co-heads allowed). */
    public function headedDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user_head');
    }

    /**
     * Bridge to PanWorkflow's transition table: checks the stage-permission key
     * an action carries ('requestor', 'division_head', …) against the booleans.
     */
    public function hasStagePermission(string $stage): bool
    {
        return match ($stage) {
            'requestor' => $this->is_requestor,
            'division_head' => $this->is_division_head,
            'hr_preparer' => $this->is_hr_preparer,
            'hr_approver' => $this->is_hr_approver,
            'final_approver' => $this->is_final_approver,
            default => false,
        };
    }
}
