{{--
    A headline figure with its trend against the previous equivalent window.

    Distinct from <x-admin.card>, which carries a free-text hint: this one
    knows about money formatting and about direction — and about the fact that
    for some figures (cancellations, out of stock) going *up* is the bad news.
--}}
@props([
    'label'    => null,
    'value'    => 0,
    'change'   => null,     // percentage, or null when there is nothing to compare
    'money'    => false,
    'tone'     => 'navy',   // navy | denim | gold | danger
    'inverted' => false,    // true when a rise is bad news
    'href'     => null,
])

@php
    $accent = [
        'navy'   => 'text-hoor-navy-700',
        'denim'  => 'text-hoor-denim-600',
        'gold'   => 'text-hoor-gold-600',
        'danger' => 'text-red-600',
    ][$tone] ?? 'text-hoor-navy-700';

    $display = $money ? \App\Casts\Money::format((int) $value) : number_format((int) $value);

    // "Good" depends on the metric: more orders is good, more cancellations is not.
    $rising = $change !== null && $change > 0;
    $good = $change === null || $change == 0 ? null : ($inverted ? ! $rising : $rising);

    $trendClass = $good === null ? 'text-hoor-muted' : ($good ? 'text-emerald-600' : 'text-red-600');
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
   {{ $attributes->merge(['class' => 'card p-5 '.($href ? 'transition hover:border-hoor-cream-400 hover:shadow-sm' : '')]) }}>

    <p class="text-xs font-medium uppercase tracking-editorial text-hoor-muted">{{ $label }}</p>

    <p class="mt-2 font-display text-2xl {{ $accent }}" dir="ltr">{{ $display }}</p>

    @if ($change !== null)
        <p class="mt-1 flex items-center gap-1 text-xs {{ $trendClass }}" dir="ltr">
            <span aria-hidden="true">{{ $rising ? '▲' : ($change == 0 ? '—' : '▼') }}</span>
            <span>{{ abs($change) }}%</span>
            <span class="text-hoor-muted">{{ __('admin.dashboard.vs_previous') }}</span>
        </p>
    @elseif (isset($hint))
        <p class="mt-1 text-xs text-hoor-muted">{{ $hint }}</p>
    @endif
</{{ $tag }}>
