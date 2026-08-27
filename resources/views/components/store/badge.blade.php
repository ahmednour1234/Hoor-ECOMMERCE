{{-- Small overlay label used on product imagery. --}}
@props(['tone' => 'new'])

@php
    $tones = [
        'new'    => 'bg-hoor-navy-500 text-hoor-cream-50',
        'sale'   => 'bg-hoor-gold-500 text-hoor-navy-700',
        'low'    => 'bg-white/95 text-hoor-navy-700',
        'muted'  => 'bg-hoor-navy-900/80 text-hoor-cream-50',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center rounded-sm px-2.5 py-1 text-[0.65rem] font-medium
                uppercase tracking-wider backdrop-blur '.($tones[$tone] ?? $tones['new']),
]) }}>
    {{ $slot }}
</span>
