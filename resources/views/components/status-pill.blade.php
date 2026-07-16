{{-- Status pill. Usage:
     <x-status-pill status="filed" />                          → known status, default label
     <x-status-pill status="with-division-head">Awaiting your decision</x-status-pill>  → label override
     <x-status-pill tone="ret">Failed — bad password</x-status-pill>                    → freeform (non-PAN tables)
     Status keys mirror the planned PanStatus enum; labels/tones come from the mockup Glossary. --}}
@props(['status' => null, 'tone' => null])
@php
    $map = [
        'draft'                 => ['draft', 'Draft'],
        'with-division-head'    => ['dh',    'With Division Head'],
        'returned-to-requestor' => ['ret',   'Returned — for correction'],
        'awaiting-tag'          => ['prep',  'Awaiting tag & preparation'],
        'in-preparation'        => ['prep',  'HR Preparation'],
        'for-confirmation'      => ['conf',  'For DH Confirmation'],
        'returned-to-preparer'  => ['ret',   'Returned by HR Approver'],
        'for-hr-approval'       => ['hra',   'For HR Approval'],
        'for-final-approval'    => ['final', 'For Final Approval'],
        'approved'              => ['appr',  'Approved — for serving'],
        'served'                => ['appr',  'Served'],
        'unserved'              => ['ret',   'Unserved'],
        'filed'                 => ['filed', 'Filed'],
        'withdrawn'             => ['draft', 'Withdrawn'],
        'voided'                => ['ret',   'Voided'],
    ];
    [$mapTone, $mapLabel] = $map[$status] ?? [null, null];
    $toneClass = 'p-' . ($tone ?? $mapTone ?? 'draft');
@endphp
<span {{ $attributes->merge(['class' => "pill {$toneClass}"]) }}>{{ $slot->isEmpty() ? ($mapLabel ?? '—') : $slot }}</span>
