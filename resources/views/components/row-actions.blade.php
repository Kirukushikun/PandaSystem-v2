{{-- Row-action cell enforcing the table action grammar:
     View (ghost link) · one filled primary verb · kebab for destructive/secondary.
     No disabled buttons — the status pill explains inaction. Usage:
     <x-row-actions>
       <a class="btn ghost" href="…" wire:navigate>View</a>
       <button class="btn primary">Approve</button>
       <x-kebab><x-kebab.item danger>Return to Requestor…</x-kebab.item></x-kebab>
     </x-row-actions> --}}
<td {{ $attributes->merge(['class' => 'acts']) }}>{{ $slot }}</td>
