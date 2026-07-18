{{-- Summary-strip stat card. Wrap a row of these in a plain <div class="stats">.
     Tones color the number: warn (amber), bad (red), ok (green), acc (accent). Default = ink.
     Usage: <x-stat value="8" label="Total requests" /> · <x-stat value="3" label="In progress" tone="warn" /> --}}
@props(['value', 'label', 'tone' => null])
<div {{ $attributes->merge(['class' => trim('stat ' . ($tone ?? ''))]) }}><b>{{ $value }}</b><span>{{ $label }}</span></div>
