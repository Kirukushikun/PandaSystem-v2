{{-- Filter chip. Usage: <x-chip on>All</x-chip> <x-chip>In progress</x-chip> --}}
@props(['on' => false])
<button {{ $attributes->merge(['type' => 'button', 'class' => 'chip' . ($on ? ' on' : '')]) }}>{{ $slot }}</button>
