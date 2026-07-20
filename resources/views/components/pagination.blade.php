{{-- Shared Livewire paginator: render via $rows->links('components.pagination').
     Expects the component to use WithPagination + WithPerPage ($perPage drives the selector).
     Prev/Next only appear when they can act (no disabled buttons — house rule). --}}
@if ($paginator->hasPages() || $paginator->total() > min($this->perPageOptions()))
<nav style="display:flex;align-items:center;gap:10px;margin-top:12px;flex-wrap:wrap">
  <small style="color:var(--ink-3)">Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}</small>
  <div class="spacer"></div>
  <label style="display:flex;align-items:center;gap:6px"><small style="color:var(--ink-3)">Per page</small>
    <select wire:model.live="perPage" style="width:auto;padding:4px 8px">
      @foreach ($this->perPageOptions() as $option)
      <option value="{{ $option }}">{{ $option }}</option>
      @endforeach
    </select>
  </label>
  @unless ($paginator->onFirstPage())
    <button class="btn ghost" type="button" wire:click="previousPage">‹ Prev</button>
  @endunless
  <small style="color:var(--ink-3)">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</small>
  @if ($paginator->hasMorePages())
    <button class="btn ghost" type="button" wire:click="nextPage">Next ›</button>
  @endif
</nav>
@endif
