{{-- Collapsible connector-line timeline for a PAN's back-and-forth trail (pan_returns rows).
     One row per return; only the most recent is open by default. Dot color echoes the stage
     the PAN was returned FROM, reusing the same tone mapping as x-status-pill so the color
     language stays consistent across the app. --}}
@props(['returns'])
@php
    $tones = [
        'draft' => 'draft', 'with-division-head' => 'dh', 'returned-to-requestor' => 'ret',
        'awaiting-tag' => 'prep', 'in-preparation' => 'prep', 'for-confirmation' => 'conf',
        'returned-to-preparer' => 'ret', 'for-hr-approval' => 'hra', 'for-final-approval' => 'final',
        'approved' => 'appr', 'served' => 'appr', 'unserved' => 'ret', 'filed' => 'filed',
        'withdrawn' => 'draft', 'voided' => 'ret',
    ];
    $ordered = $returns->sortByDesc('id')->values();
@endphp
@if ($ordered->isNotEmpty())
<div class="rethist">
  <div class="rethist-head"><span class="ic">!</span><span>{{ $ordered->count() }} {{ Str::plural('return', $ordered->count()) }} on this request</span></div>
  <div class="rethist-body">
    <div class="tl">
      @foreach ($ordered as $i => $return)
      <details class="tl-row" data-tone="{{ $tones[$return->from_status->value] ?? 'draft' }}" @if ($i === 0) open @endif>
        <summary>
          <span class="tl-dot"></span>
          <span class="tl-stage">{{ $return->from_status->label() }}</span>
          <span class="tl-arrow">→</span>
          <span class="tl-by">{{ $return->returnedBy?->name ?? '—' }}</span>
          <span class="tl-time">{{ $return->created_at->format('M j, Y') }}</span>
          <span class="tl-chev">▸</span>
        </summary>
        <div class="tl-detail"><b>{{ $return->reason }}</b>@if ($return->details) — {{ $return->details }}@endif</div>
      </details>
      @endforeach
    </div>
  </div>
</div>
@endif
