{{-- Shared Livewire paginator: render via $rows->links('components.pagination').
     Prev/Next only appear when they can act (no disabled buttons — house rule). --}}
@if ($paginator->hasPages())
<nav style="display:flex;align-items:center;gap:10px;margin-top:12px">
  <small style="color:var(--ink-3)">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</small>
  <div class="spacer"></div>
  @unless ($paginator->onFirstPage())
    <button class="btn ghost" type="button" wire:click="previousPage">‹ Prev</button>
  @endunless
  <small style="color:var(--ink-3)">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</small>
  @if ($paginator->hasMorePages())
    <button class="btn ghost" type="button" wire:click="nextPage">Next ›</button>
  @endif
</nav>
@endif
