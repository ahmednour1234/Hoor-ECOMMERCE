{{--
    Product gallery: a main frame with thumbnails beneath.

    All images stay in the DOM and are swapped by index, so switching is instant
    after the first load and the markup remains crawlable. The frame holds a
    fixed 4:5 ratio, matching the source photography, so nothing shifts as
    images arrive.
--}}
@props(['product'])

@php
    $images = $product->images->sortByDesc('is_primary')->sortBy('sort_order')->values();
@endphp

<div x-data="{ active: 0, count: {{ max($images->count(), 1) }} }"
     @keydown.left.window="active = (active - 1 + count) % count"
     @keydown.right.window="active = (active + 1) % count">

    {{-- Main frame --}}
    <div class="relative aspect-[4/5] overflow-hidden rounded-md bg-hoor-cream-100">
        @forelse ($images as $index => $image)
            <img src="{{ $image->url() }}"
                 alt="{{ $image->alt ?? $product->name }}"
                 width="1122" height="1402"
                 loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                 fetchpriority="{{ $index === 0 ? 'high' : 'auto' }}"
                 decoding="async"
                 x-show="active === {{ $index }}"
                 @if ($index > 0) x-cloak @endif
                 class="absolute inset-0 h-full w-full object-cover">
        @empty
            <span class="flex h-full w-full items-center justify-center">
                <img src="{{ asset('images/brand/hoor-icon-blue.svg') }}" alt="" class="w-1/5 opacity-20">
            </span>
        @endforelse

        {{-- Badges mirror the card so a product reads the same everywhere. --}}
        <div class="absolute start-4 top-4 flex flex-col items-start gap-2">
            @if ($product->isOnSale())
                <x-store.badge tone="sale">-{{ $product->discountPercentage() }}%</x-store.badge>
            @elseif ($product->is_new)
                <x-store.badge tone="new">{{ __('catalog.labels.new') }}</x-store.badge>
            @endif
        </div>

        {{-- Arrows, only when there is more than one image. --}}
        @if ($images->count() > 1)
            <button type="button"
                    @click="active = (active - 1 + count) % count"
                    class="absolute start-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center
                           justify-center rounded-full bg-white/85 text-hoor-navy-600 shadow-card
                           backdrop-blur transition hover:bg-white"
                    aria-label="{{ __('store.product.gallery_prev') }}">
                <span class="rtl:rotate-180" aria-hidden="true">&#8249;</span>
            </button>

            <button type="button"
                    @click="active = (active + 1) % count"
                    class="absolute end-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center
                           justify-center rounded-full bg-white/85 text-hoor-navy-600 shadow-card
                           backdrop-blur transition hover:bg-white"
                    aria-label="{{ __('store.product.gallery_next') }}">
                <span class="rtl:rotate-180" aria-hidden="true">&#8250;</span>
            </button>
        @endif
    </div>

    {{-- Thumbnails --}}
    @if ($images->count() > 1)
        <div class="mt-3 grid grid-cols-5 gap-2 sm:gap-3">
            @foreach ($images as $index => $image)
                <button type="button"
                        @click="active = {{ $index }}"
                        class="aspect-[4/5] overflow-hidden rounded-sm border-2 transition"
                        :class="active === {{ $index }}
                            ? 'border-hoor-navy-500'
                            : 'border-transparent hover:border-hoor-cream-400'"
                        :aria-current="active === {{ $index }}"
                        aria-label="{{ __('store.product.view_image', ['number' => $index + 1]) }}">
                    <img src="{{ $image->url() }}"
                         alt=""
                         loading="lazy" decoding="async"
                         class="h-full w-full object-cover">
                </button>
            @endforeach
        </div>
    @endif
</div>
