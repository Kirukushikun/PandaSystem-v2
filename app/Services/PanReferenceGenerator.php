<?php

namespace App\Services;

use App\Models\PanRequest;
use Illuminate\Support\Facades\DB;

/**
 * PAN-2026-00347-style reference numbers: per-year sequence, five digits.
 * Runs inside a transaction with a row lock so concurrent submissions
 * can't mint the same number.
 */
class PanReferenceGenerator
{
    public function next(): string
    {
        $year = now()->year;
        $prefix = "PAN-{$year}-";

        return DB::transaction(function () use ($prefix) {
            $latest = PanRequest::withTrashed()
                ->where('reference', 'like', $prefix.'%')
                ->orderByDesc('reference')
                ->lockForUpdate()
                ->value('reference');

            $sequence = $latest ? (int) substr($latest, strlen($prefix)) + 1 : 1;

            return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
        });
    }
}
