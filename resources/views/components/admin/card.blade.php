{{-- Admin stat tile: a label, a figure, and optional trend/footnote. --}}
@props([
    'label' => null,
    'value' => null,
    'hint'  => null,
    'tone'  => 'navy',   // navy | denim | gold
])

@php
    $accent = [
        'navy'  => 'text-hoor-navy-500',
        'denim' => 'text-hoor-denim-500',
        'gold'  => 'text-hoor-gold-600',
    ][$tone] ?? 'text-hoor-navy-500';
@endphp

<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    @if ($label)
        <p class="text-xs font-medium uppercase tracking-editorial text-hoor-muted">{{ $label }}</p>
    @endif

    <p class="mt-2 font-display text-3xl {{ $accent }}">
        {{ $value ?? $slot }}
    </p>

    @if ($hint)
        <p class="mt-1 text-xs text-hoor-muted">{{ $hint }}</p>
    @endif
</div>
