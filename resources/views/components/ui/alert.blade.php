@props([
    'variant' => 'info',   // info | success | warning | danger
    'title'   => null,
])

@php
    $styles = [
        'info'    => 'border-hoor-denim-200 bg-hoor-denim-50 text-hoor-denim-700',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        'danger'  => 'border-red-200 bg-red-50 text-red-800',
    ];
@endphp

<div role="alert"
     {{ $attributes->merge(['class' => 'rounded-sm border px-4 py-3 text-sm '.($styles[$variant] ?? $styles['info'])]) }}>
    @if ($title)
        <p class="mb-1 font-medium">{{ $title }}</p>
    @endif
    {{ $slot }}
</div>
