<?php

namespace App\Livewire\Concerns;

/**
 * Rows-per-page choice for paginated tables. The shared pager component
 * (components/pagination.blade.php) renders the selector bound to $perPage.
 * Pair with Livewire\WithPagination — resetPage() comes from there.
 */
trait WithPerPage
{
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public int $perPage = 25;

    /** For the pager blade — PHP forbids reading a trait constant via the trait name. */
    public function perPageOptions(): array
    {
        return self::PER_PAGE_OPTIONS;
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 25; // anything hand-tampered snaps back to the default
        }

        $this->resetPage();
    }
}
