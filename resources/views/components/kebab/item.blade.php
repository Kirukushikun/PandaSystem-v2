{{-- Kebab menu item. `danger` turns it red. A nested <small> renders as a hint line.
     Usage: <x-kebab.item danger>Withdraw request</x-kebab.item> --}}
@props(['danger' => false])
<button {{ $attributes->merge(['type' => 'button', 'class' => 'kitem' . ($danger ? ' kdanger' : '')]) }}>{{ $slot }}</button>
