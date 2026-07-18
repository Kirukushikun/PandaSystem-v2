<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One sign-in attempt (successful or failed) — org-standard shape.
 */
class AccessLog extends Model
{
    protected $fillable = ['email', 'success', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['success' => 'boolean'];
    }
}
