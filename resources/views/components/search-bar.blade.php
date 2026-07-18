{{-- Toolbar search box. Sits inside a plain <div class="bar"> next to filter chips.
     Usage: <x-search-bar placeholder="Search by employee, reference no., or action type…" /> --}}
@props(['placeholder' => 'Search…'])
<div {{ $attributes->merge(['class' => 'search']) }}>⌕<input placeholder="{{ $placeholder }}"></div>
