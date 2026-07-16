{{-- Workflow stage tracker. Stages before `current` render done, `current` renders now.
     Usage:
     <x-stage-tracker :stages="['Submitted','Division Head','HR Preparation','HR Approval','Final Approval']"
                      current="Division Head" />
     current also accepts a 0-based index; pass current="*" to mark every stage done (closed-out PANs). --}}
@props(['stages' => [], 'current' => null])
@php
    $idx = $current === '*' ? count($stages) : (is_int($current) ? $current : array_search($current, $stages));
@endphp
<div {{ $attributes->merge(['class' => 'flow']) }} aria-label="Workflow progress">
@foreach ($stages as $i => $stage)
  <span class="step{{ $idx === false || $idx === null ? '' : ($i < $idx ? ' done' : ($i === $idx ? ' now' : '')) }}">{{ $stage }}</span>@unless ($loop->last)<span class="sep">→</span>@endunless
@endforeach
</div>
