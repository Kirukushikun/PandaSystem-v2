<?php

namespace App\Livewire\Concerns;

use App\Enums\ConfidentialityTag;
use App\Enums\PanStatus;
use App\Models\PanRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The two lenses, combined for users holding both flags: a department head sees
 * their own departments' non-Manila PANs; a DH Head sees ONLY Manila-tagged
 * PANs across all departments. Drafts are in nobody's queue. Shared between the
 * Department Queue (actionable) and Monitor (full read-only lifecycle) so the
 * visibility rule can't drift between the two.
 */
trait ScopesDivisionPans
{
    private ?Collection $headedDeptIds = null;

    protected function divisionScope(): Builder
    {
        $user = auth()->user();
        $this->headedDeptIds ??= $user->headedDepartments()->pluck('departments.id');

        return PanRequest::query()
            ->where('status', '!=', PanStatus::Draft->value)
            ->where(function (Builder $q) use ($user) {
                if ($user->is_division_head) {
                    $q->orWhere(fn (Builder $q) => $q
                        ->whereIn('department_id', $this->headedDeptIds)
                        ->where('confidentiality_tag', '!=', ConfidentialityTag::Manila->value));
                }
                if ($user->is_dh_head) {
                    $q->orWhere('confidentiality_tag', ConfidentialityTag::Manila->value);
                }
            });
    }
}
