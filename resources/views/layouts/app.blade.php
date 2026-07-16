<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title ?? 'PANDA — PAN Workflow System' }}</title>
{{-- FOUC guard: stamp the saved theme on <html> before first paint --}}
<script>
  try{var t=localStorage.getItem('panda-theme');if(t)document.documentElement.dataset.theme=t}catch(e){}
</script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

{{-- Theme toggle: fixed to the top-right corner, floats above all screens --}}
<button class="iconbtn" id="theme-toggle" title="Toggle light / dark theme"
  style="position:fixed;top:16px;right:20px;z-index:70;width:36px;height:36px;border-radius:99px;box-shadow:var(--shadow)"></button>

{{-- Notification bell — static sample content for now; becomes Shared\NotificationBell in scaffold step 8 --}}
<button class="iconbtn" id="notif-btn" title="Notifications"
  style="position:fixed;top:16px;right:64px;z-index:70;width:36px;height:36px;border-radius:99px;box-shadow:var(--shadow)">
  <svg style="width:15px;height:15px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
  <span class="nbadge" id="notif-count">3</span>
</button>

<div class="npanel" id="notif-panel">
  <div class="nhead">
    <b>Notifications</b>
    <button class="btn ghost" id="notif-clear" style="padding:2px 8px;font-size:12px">Mark all read</button>
  </div>
  <div class="nrow unread"><span class="ndot"></span>
    <div><p><b>Allowance expiring soon</b> — C. Mercado's Interim Allowance (<span class="ref">PAN-2026-00332</span>) ends Jul 22, 2026.</p><small>2 hours ago · Expiry reminder</small></div></div>
  <div class="nrow unread"><span class="ndot"></span>
    <div><p><b>Returned for resolution</b> — <span class="ref">PAN-2026-00338</span> was sent back by the HR Approver: "Wage number mismatch".</p><small>Yesterday · HR Preparation</small></div></div>
  <div class="nrow unread"><span class="ndot"></span>
    <div><p><b>Returned to you</b> — <span class="ref">PAN-2026-00351</span> needs its attachment replaced before resubmitting.</p><small>Jul 10 · Requestor</small></div></div>
  <div class="nrow"><span class="ndot"></span>
    <div><p><b>Filed</b> — <span class="ref">PAN-2026-00298</span> (L. Bautista, Wage Order) closed out.</p><small>Jun 30 · HR Preparation</small></div></div>
  <div class="nrow"><span class="ndot"></span>
    <div><p><b>Bulk approval</b> — 3 Regularization PANs were approved by V. Salazar.</p><small>Jun 28 · Final Approver</small></div></div>
</div>

<div class="app">
<aside class="side">
  <div class="brand">
    <h1>PANDA<span>&nbsp;v2</span></h1>
    <p>Personnel Action Notice workflow</p>
  </div>
  <nav class="navsec" aria-label="Modules">
    <h2>Workflow modules</h2>
    <a class="navbtn @if(request()->routeIs('requests.*')) active @endif" href="{{ route('requests.index') }}" wire:navigate><span class="dot"></span>Requestor<span class="n">8</span></a>
    <a class="navbtn @if(request()->routeIs('division.*')) active @endif" href="{{ route('division.queue') }}" wire:navigate><span class="dot"></span>Division Head<span class="n">12</span></a>
    {{-- employees.* is the HR-Prep roster lens (/employees), part of this module per the mockup --}}
    <a class="navbtn @if(request()->routeIs('preparation.*') || request()->routeIs('employees.*')) active @endif" href="{{ route('preparation.queue') }}" wire:navigate><span class="dot"></span>HR Preparation<span class="n">9</span></a>
    <a class="navbtn @if(request()->routeIs('hr-approval.*')) active @endif" href="{{ route('hr-approval.queue') }}" wire:navigate><span class="dot"></span>HR Approver<span class="n">4</span></a>
    <a class="navbtn @if(request()->routeIs('final-approval.*')) active @endif" href="{{ route('final-approval.queue') }}" wire:navigate><span class="dot"></span>Final Approver<span class="n">6</span></a>
  </nav>
  <nav class="navsec" aria-label="Administration">
    <h2>Administration</h2>
    <a class="navbtn @if(request()->routeIs('admin.users*')) active @endif" href="{{ route('admin.users') }}" wire:navigate><span class="dot"></span>User Access</a>
    <a class="navbtn @if(request()->routeIs('admin.employees*')) active @endif" href="{{ route('admin.employees') }}" wire:navigate><span class="dot"></span>Employees</a>
    <a class="navbtn @if(request()->routeIs('maintenance.*')) active @endif" href="{{ route('maintenance.index') }}" wire:navigate><span class="dot"></span>Maintenance</a>
  </nav>
  <nav class="navsec" aria-label="Help">
    <h2>Help</h2>
    <a class="navbtn @if(request()->routeIs('help.glossary')) active @endif" href="{{ route('help.glossary') }}" wire:navigate><span class="dot"></span>Glossary</a>
  </nav>
  <div class="me" style="flex-direction:column;align-items:stretch;gap:12px">
    <div style="display:flex;gap:10px;align-items:center">
      <div class="avatar">MD</div>
      <div style="min-width:0"><b>M. Dela Cruz</b><small>Requestor · Division Head · HR Head</small></div>
    </div>
    <a class="btn" href="#" style="display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;color:var(--ink-2)">
      <svg style="width:14px;height:14px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
      Log out
    </a>
  </div>
</aside>

<main>
  <section class="screen on">
    {{ $slot }}
  </section>
</main>
</div>

<div class="toast" id="toast"><span class="ok">✓</span><span id="toast-msg"></span></div>

</body>
</html>
