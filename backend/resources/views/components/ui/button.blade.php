@props(['href' => null, 'variant' => 'primary'])

@php
    $classes = trim('btn '.($variant === 'secondary' ? 'secondary' : ($variant === 'ghost' ? 'ghost' : '')));
@endphp

@if($href)
    <a {{ $attributes->merge(['class' => $classes, 'href' => $href]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $classes, 'type' => 'button']) }}>{{ $slot }}</button>
@endif
