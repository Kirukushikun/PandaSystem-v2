{{-- Shared Livewire paginator: render via $rows->links('components.pagination').
     Expects the component to use WithPagination + WithPerPage ($perPage drives the selector).
     Prev/Next and page numbers only appear when they can act (no disabled buttons — house rule). --}}
@if ($paginator->hasPages() || $paginator->total() > min($this->perPageOptions()))
@php
  // A small window of page numbers around the current one, with edges + ellipsis —
  // avoids rendering 40 buttons for a 40-page table.
  $current = $paginator->currentPage();
  $last = $paginator->lastPage();
  $window = collect(range(max(1, $current - 2), min($last, $current + 2)));
  if ($window->first() > 1) $window->prepend($window->first() > 2 ? '…' : 1);
  if ($window->first() !== 1 && $window->first() !== '…') $window->prepend(1);
  if ($window->last() < $last) $window->push($last - $window->last() > 1 ? '…' : $last);
  if ($window->last() !== $last && $window->last() !== '…') $window->push($last);
@endphp
<nav style="display:flex;align-items:center;gap:8px;margin-top:12px;flex-wrap:wrap">
  <small style="color:var(--ink-3)">Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}</small>
  <div class="spacer"></div>
  <label style="display:flex;align-items:center;gap:6px"><small style="color:var(--ink-3)">Per page</small>
    <select wire:model.live="perPage" class="pager-select">
      @foreach ($this->perPageOptions() as $option)
      <option value="{{ $option }}">{{ $option }}</option>
      @endforeach
    </select>
  </label>
  @unless ($paginator->onFirstPage())
    <button class="btn ghost" type="button" wire:click="previousPage">‹ Prev</button>
  @endunless
  @foreach ($window as $page)
    @if ($page === '…')
      <span style="color:var(--ink-3);padding:0 2px">…</span>
    @elseif ($page === $current)
      <span class="chip on" aria-current="page">{{ $page }}</span>
    @else
      <button class="chip" type="button" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
    @endif
  @endforeach
  @if ($paginator->hasMorePages())
    <button class="btn ghost" type="button" wire:click="nextPage">Next ›</button>
  @endif
</nav>
@endif
