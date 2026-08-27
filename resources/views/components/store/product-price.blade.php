{{--
    Price display. Reads the product's derived price so a sale is only ever
    shown when the discount genuinely undercuts the base price.
--}}
@props([
    'product',
    'size' => 'md',   // sm | md | lg
])

@php
    $classes = [
        'sm' => 'text-sm',
        'md' => 'text-base',
        'lg' => 'text-lg',
    ][$size] ?? 'text-base';

    $range = $product->relationLoaded('variants') ? $product->priceRange() : null;
    $hasRange = $range !== null && $range['min'] !== $range['max'];
@endphp

<p {{ $attributes->merge(['class' => 'flex flex-wrap items-baseline gap-2 '.$classes]) }}>
    @if ($hasRange)
        <span class="text-xs text-hoor-muted">{{ __('catalog.labels.from') }}</span>
        <span class="font-medium text-hoor-navy-700" dir="ltr">
            {{ \App\Casts\Money::format($range['min']) }}
        </span>
    @else
        <span class="font-medium {{ $product->isOnSale() ? 'text-hoor-gold-600' : 'text-hoor-navy-700' }}"
              dir="ltr">
            {{ \App\Casts\Money::format($product->effectivePrice()) }}
        </span>

        @if ($product->isOnSale())
            <s class="text-xs text-hoor-muted" dir="ltr">
                {{ \App\Casts\Money::format($product->base_price) }}
            </s>
        @endif
    @endif
</p>
