{{-- Maintenance subtab strip — the mockup's tabs as real routes. Active state via routeIs. --}}
<div class="subtabs" role="tablist">
  <a class="stab @if (request()->routeIs('maintenance.logs')) on @endif" href="{{ route('maintenance.logs') }}" wire:navigate style="text-decoration:none">Logs &amp; Audit</a>
  <a class="stab @if (request()->routeIs('maintenance.reference')) on @endif" href="{{ route('maintenance.reference') }}" wire:navigate style="text-decoration:none">Reference Values</a>
  <a class="stab @if (request()->routeIs('maintenance.backups')) on @endif" href="{{ route('maintenance.backups') }}" wire:navigate style="text-decoration:none">Backup &amp; Restore</a>
  <a class="stab @if (request()->routeIs('maintenance.danger')) on @endif" href="{{ route('maintenance.danger') }}" wire:navigate style="text-decoration:none">Danger Zone</a>
</div>
