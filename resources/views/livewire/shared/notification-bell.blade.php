@php $unread = count(array_filter($notifications, fn ($n) => $n['unread'])); @endphp
<div>
  <button class="iconbtn" id="notif-btn" title="Notifications"
    style="position:fixed;top:16px;right:64px;z-index:70;width:36px;height:36px;border-radius:99px;box-shadow:var(--shadow)">
    <svg style="width:15px;height:15px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
    @if ($unread > 0)<span class="nbadge">{{ $unread }}</span>@endif
  </button>

  <div class="npanel" id="notif-panel">
    <div class="nhead">
      <b>Notifications</b>
      <button class="btn ghost" type="button" style="padding:2px 8px;font-size:12px" wire:click="markAllRead">Mark all read</button>
    </div>
    @foreach ($notifications as $n)
    <div class="nrow @if ($n['unread']) unread @endif"><span class="ndot"></span>
      <div><p>{!! $n['text'] !!}</p><small>{{ $n['meta'] }}</small></div></div>
    @endforeach
  </div>
</div>
