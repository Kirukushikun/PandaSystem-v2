{{-- Kebab-style dropdown for the sort/type/department (and, on HR Preparation,
     tag) filters. $open is real Livewire state, not a client classList toggle
     — see the .filters-menu CSS comment for why. $active shows a dot on the
     trigger when a non-default filter is applied, independent of $open.
     $clear names a component method (e.g. "clearPanFilters") wired to the
     "Clear all" link; omit to hide it. --}}
@props(['open' => false, 'active' => false, 'clear' => null])
<div class="filters-anchor">
  <button type="button" class="filters-trigger" wire:click="$toggle('showFilters')">
    Filters
    <svg class="chevron @if ($open) open @endif" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    @if ($active)<span class="filter-dot" title="A filter is applied"></span>@endif
  </button>
  @if ($open)
  <div class="filters-menu" wire:click.outside="$set('showFilters', false)" wire:keydown.escape.window="$set('showFilters', false)">
    {{ $slot }}
    @if ($clear)
    <div class="filters-clear"><button type="button" wire:click="{{ $clear }}">Clear all</button></div>
    @endif
  </div>
  @endif
</div>
