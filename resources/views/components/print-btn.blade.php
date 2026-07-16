{{-- Print-PAN icon button. Per the UI convention it stays visible in HR Prep tables whenever a
     prepared PAN exists (never disabled — rows without one simply don't render it). --}}
<button {{ $attributes->merge(['class' => 'iconbtn', 'type' => 'button', 'title' => 'Print PAN']) }}>
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
</button>
