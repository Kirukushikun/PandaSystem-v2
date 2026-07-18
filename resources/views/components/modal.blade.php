{{-- Modal shell (overlay + dialog). Two modes:

     STATIC (default): openers use data-modal-open="{id}"; [data-close], ×, backdrop,
     and Escape close it via the global JS. For modals with no Livewire state.

     STATE-DRIVEN: pass :open="$flag" and close="livewire expression" — the overlay's
     "on" class follows server state, so re-renders (validation errors, uploads) can
     never snap it shut. Openers set the flag (wire:click="$set('flag', true)");
     ×, backdrop, and Escape run the close expression. --}}
@props(['id', 'title' => '', 'open' => null, 'close' => null])
<div class="overlay @if ($open) on @endif" id="{{ $id }}"
  @if ($close) wire:click.self="{{ $close }}" wire:keydown.escape.window="{{ $close }}" @endif>
  <div {{ $attributes->merge(['class' => 'modal']) }} role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
    <h3><span id="{{ $id }}-title">{{ $title }}</span>
      @if ($close)
      <button class="x" type="button" wire:click="{{ $close }}" title="Close">×</button>
      @else
      <button class="x" type="button" data-close title="Close">×</button>
      @endif
    </h3>
    {{ $slot }}
    @isset($footer)
    <div class="formfoot">{{ $footer }}</div>
    @endisset
  </div>
</div>
