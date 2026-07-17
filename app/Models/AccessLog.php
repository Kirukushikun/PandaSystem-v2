<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One sign-in attempt (successful or failed). Append-only: no updated_at.
 */
class AccessLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['username', 'user_id', 'ip', 'user_agent', 'successful'];

    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
