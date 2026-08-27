{{--
    Polymorphic button.

    Renders <a> when an href is supplied and <button> otherwise, so calling code
    never has to choose the element to get consistent styling.
--}}
@props([
    'variant' => 'primary',  // primary | secondary | outline | ghost | gold | danger
    'size'    => 'md',       // sm | md | lg
    'href'    => null,
    'type'    => 'button',
])

@php
    $classes = trim(implode(' ', array_filter([
        'btn-'.$variant,
        $size !== 'md' ? 'btn-'.$size : null,
    ])));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
