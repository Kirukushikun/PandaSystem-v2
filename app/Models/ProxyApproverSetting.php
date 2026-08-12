<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton (id=1) — on/off switch and staleness threshold for the temporary
 * Proxy Approver override. See ProxyApprovalEligibility for how it's applied.
 */
class ProxyApproverSetting extends Model
{
    protected $fillable = ['enabled', 'threshold_days'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'threshold_days' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
