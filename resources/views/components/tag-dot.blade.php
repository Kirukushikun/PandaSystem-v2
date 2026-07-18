{{-- Confidentiality tag dot: purple=Manila, blue=Tarlac (routine), gray=untagged.
     Usage: <x-tag-dot tag="manila" />  ·  <x-tag-dot tag="tarlac" />  ·  <x-tag-dot /> --}}
@props(['tag' => 'none'])
@php
    $class = ['manila' => 't-manila', 'tarlac' => 't-routine', 'none' => 't-none'][$tag] ?? 't-none';
    $title = ['manila' => 'Manila — confidential', 'tarlac' => 'Tarlac — routine', 'none' => 'Untagged'][$tag] ?? 'Untagged';
@endphp
<span {{ $attributes->merge(['class' => "tagdot {$class}", 'title' => $title]) }}></span>
