<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /** Users who may raise PANs for this department. */
    public function requestors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_user_requestor');
    }

    /** This department's head(s) — co-heads are supported. */
    public function heads(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_user_head');
    }

    /** Reference value guard: a department in use can't be deleted. */
    public function isInUse(): bool
    {
        return $this->employees()->exists()
            || $this->requestors()->exists()
            || $this->heads()->exists();
    }
}
