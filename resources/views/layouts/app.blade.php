<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ?? 'PANDA — PAN Workflow System' }}</title>
<link rel="icon" type="image/x-icon" href="{{ asset('images/pan-icon.ico') }}">
{{-- FOUC guard: stamp the saved theme on <html> before first paint --}}
<script>
  try{var t=localStorage.getItem('panda-theme');if(t)document.documentElement.dataset.theme=t}catch(e){}
</script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-user-id="{{ auth()->id() }}">

{{-- Theme toggle: fixed to the top-right corner, floats above all screens --}}
<button class="iconbtn" id="theme-toggle" title="Toggle light / dark theme"
  style="position:fixed;top:16px;right:20px;z-index:70;width:36px;height:36px;border-radius:99px;box-shadow:var(--shadow)"></button>

{{-- Notification bell — Livewire owns the unread state; open/close stays in app.js --}}
<livewire:shared.notification-bell />

<div class="app">
<aside class="side">
  <div class="brand">
    <h1>PANDA<span>&nbsp;v2</span></h1>
    <p>Personnel Action Notice workflow</p>
  </div>
  {{-- Links hidden per gate are UX only — every route enforces the same gate + policies --}}
  <nav class="navsec" aria-label="Modules">
    <h2>Workflow modules</h2>
    @can('requestor')
    <a class="navbtn @if(request()->routeIs('requests.*')) active @endif" href="{{ route('requests.index') }}" wire:navigate><span class="dot"></span>Requestor</a>
    @endcan
    @can('division_head')
    <a class="navbtn @if(request()->routeIs('division.*')) active @endif" href="{{ route('division.queue') }}" wire:navigate><span class="dot"></span>Division Head</a>
    @endcan
    {{-- employees.* is the HR-Prep roster lens (/employees), part of this module per the mockup --}}
    @can('hr_preparer')
    <a class="navbtn @if(request()->routeIs('preparation.*') || request()->routeIs('employees.*')) active @endif" href="{{ route('preparation.queue') }}" wire:navigate><span class="dot"></span>HR Preparation</a>
    @endcan
    @can('hr_approver')
    <a class="navbtn @if(request()->routeIs('hr-approval.*')) active @endif" href="{{ route('hr-approval.queue') }}" wire:navigate><span class="dot"></span>HR Approver</a>
    @endcan
    @can('final_approver')
    <a class="navbtn @if(request()->routeIs('final-approval.*')) active @endif" href="{{ route('final-approval.queue') }}" wire:navigate><span class="dot"></span>Final Approver</a>
    @endcan
  </nav>
  @can('admin')
  <nav class="navsec" aria-label="Administration">
    <h2>Administration</h2>
    <a class="navbtn @if(request()->routeIs('admin.users*')) active @endif" href="{{ route('admin.users') }}" wire:navigate><span class="dot"></span>User Access</a>
    <a class="navbtn @if(request()->routeIs('maintenance.*')) active @endif" href="{{ route('maintenance.logs') }}" wire:navigate><span class="dot"></span>Maintenance</a>
  </nav>
  @endcan
  {{-- Roster CRUD: admins and HR Preparers both (manage_roster gate) — its own
       section so it still shows for a Preparer who isn't otherwise an Admin. --}}
  @can('manage_roster')
  <nav class="navsec" aria-label="Roster">
    <h2>Employee Roster</h2>
    <a class="navbtn @if(request()->routeIs('admin.employees*')) active @endif" href="{{ route('admin.employees') }}" wire:navigate><span class="dot"></span>Manage Employees</a>
  </nav>
  @endcan
  <nav class="navsec" aria-label="Help">
    <h2>Help</h2>
    <a class="navbtn @if(request()->routeIs('help.glossary')) active @endif" href="{{ route('help.glossary') }}" wire:navigate><span class="dot"></span>Glossary</a>
  </nav>
  <div class="me" style="flex-direction:column;align-items:stretch;gap:12px">
    @php
      $me = auth()->user();
      $meRoles = collect([
          'Requestor' => $me?->is_requestor, 'Division Head' => $me?->is_division_head,
          'HR Preparer' => $me?->is_hr_preparer, 'HR Approver' => $me?->is_hr_approver,
          'Final Approver' => $me?->is_final_approver, 'HR Head' => $me?->is_hr_head,
          'DH Head' => $me?->is_dh_head, 'Admin' => $me?->is_admin,
      ])->filter()->keys()->implode(' · ');
      $meInitials = collect(explode(' ', $me?->name ?? ''))->map(fn ($w) => mb_substr(trim($w, '.'), 0, 1))->take(2)->implode('');
    @endphp
    <div style="display:flex;gap:10px;align-items:center">
      <div class="avatar">{{ strtoupper($meInitials) ?: '?' }}</div>
      <div style="min-width:0"><b>{{ $me?->name }}</b><small>{{ $meRoles ?: $me?->position }}</small></div>
    </div>
    <form method="POST" action="{{ route('logout') }}" style="display:contents">
      @csrf
      <button class="btn" type="submit" style="display:flex;align-items:center;justify-content:center;gap:8px;color:var(--ink-2);width:100%">
        <svg style="width:14px;height:14px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
        Log out
      </button>
    </form>
  </div>
</aside>

<main>
  <section class="screen on">
    {{ $slot }}
  </section>
</main>
</div>

<div class="toast" id="toast"><span class="ok">✓</span><span id="toast-msg"></span></div>

{{-- Styled replacement for every wire:confirm browser prompt (see app.js interceptor).
     Closes via the shared overlay JS: Cancel button, backdrop click, or Escape. --}}
<div class="overlay" id="confirm-modal">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="confirm-title" style="max-width:420px">
    <h3><span id="confirm-title">Please confirm</span>
      <button class="x" type="button" data-close title="Close">×</button></h3>
    <p id="confirm-msg" style="margin:0;padding:16px 18px;font-size:13.5px;color:var(--ink-2)"></p>
    <div class="formfoot">
      <button class="btn" type="button" data-close>Cancel</button>
      <div class="spacer"></div>
      <button class="btn primary" type="button" id="confirm-ok">Continue</button>
    </div>
  </div>
</div>

</body>
</html>
