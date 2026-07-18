{{-- Empty state for tables/lists. Sits inside the .card in place of (or after) the table.
     Usage: <x-empty-state title="All caught up" message="Nothing is waiting for your sign-off." />
     Optional slot renders below the message (e.g. a reset/action button). --}}
@props(['title' => 'Nothing here', 'message' => ''])
<div {{ $attributes->merge(['class' => 'card']) }} style="padding:40px 24px;text-align:center">
  <svg style="width:34px;height:34px;color:var(--ink-3);margin-bottom:10px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
  <p style="margin:0;font-weight:600;font-size:14px">{{ $title }}</p>
  @if ($message !== '')<p style="margin:4px 0 0;font-size:13px;color:var(--ink-3)">{{ $message }}</p>@endif
  {{ $slot }}
</div>
