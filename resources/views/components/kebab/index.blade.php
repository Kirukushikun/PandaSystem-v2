{{-- Kebab (⋯) row menu — the destructive/secondary slot of the row-action grammar.
     Open/close behavior is global in resources/js/app.js. Usage:
     <x-kebab>
       <x-kebab.item danger>Delete draft</x-kebab.item>
     </x-kebab> --}}
<div {{ $attributes->merge(['class' => 'kebab']) }}>
  <button class="kbtn" type="button" title="More actions">⋯</button>
  <div class="kmenu">{{ $slot }}</div>
</div>
