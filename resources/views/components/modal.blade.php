{{-- Modal shell (overlay + dialog). Openers anywhere on the page use data-modal-open="{id}";
     [data-close] buttons, the × button, backdrop click, and Escape all close it (global JS).
     Usage:
     <button class="btn" data-modal-open="return-modal">Return…</button>
     <x-modal id="return-modal" title="Return to Requestor — PAN-2026-00347">
       …body (formgrid etc.)…
       <x-slot:footer>
         <button class="btn" data-close>Cancel</button>
         <div class="spacer"></div>
         <button class="btn danger" data-close>Return to Requestor</button>
       </x-slot:footer>
     </x-modal>
     The type-to-confirm variant is added with the Maintenance step. --}}
@props(['id', 'title' => ''])
<div class="overlay" id="{{ $id }}">
  <div {{ $attributes->merge(['class' => 'modal']) }} role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
    <h3><span id="{{ $id }}-title">{{ $title }}</span>
      <button class="x" type="button" data-close title="Close">×</button></h3>
    {{ $slot }}
    @isset($footer)
    <div class="formfoot">{{ $footer }}</div>
    @endisset
  </div>
</div>
