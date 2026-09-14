<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton (id=1) — the Access Hub connection this install is enrolled under.
 * client_id === null means "never enrolled" (see isEnrolled()). client_secret
 * is encrypted at rest via the cast below, never stored plaintext.
 */
class AccessHubConnection extends Model
{
    protected $fillable = ['client_id', 'client_secret', 'last_synced_at'];

    protected function casts(): array
    {
        return [
            'client_secret' => 'encrypted',
            'last_synced_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }

    public function isEnrolled(): bool
    {
        return $this->client_id !== null && $this->client_secret !== null;
    }
}
