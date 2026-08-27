{{--
    The product card used everywhere a product is listed: homepage rails, shop
    grid, search, related products. Every listing renders through this file, so
    a change to card design happens in one place.

    Price and availability are read from the model's derived methods rather than
    recalculated here — variants are the single source of truth for both.
--}}
@props([
    'product',
    'eager' => false,   // Set on above-the-fold cards so the LCP image is not deferred.
    'saved' => false,   // Whether this customer has wishlisted it; resolved per grid, not per card.
])

@php
    $image = $product->primaryImage;
    $stock = $product->stockStatus();
    $soldOut = ! $stock->isPurchasable();

    // Products are reachable once the catalog routes land; until then the card
    // links to the homepage rather than rendering a dead URL.
    $url = \Illuminate\Support\Facades\Route::has('store.products.show')
        ? route('store.products.show', $product)
        : route('store.home');
@endphp

<article {{ $attributes->merge(['class' => 'group relative flex flex-col']) }}>

    <x-store.wishlist-button :product="$product" :saved="$saved" />

    {{-- Image: fixed 4:5 ratio reserves space before the file loads, so the
         grid never shifts as images arrive. --}}
    <a href="{{ $url }}"
       class="relative block aspect-[4/5] overflow-hidden rounded-md bg-hoor-cream-100">

        @if ($image)
            <img src="{{ $image->url() }}"
                 alt="{{ $image->alt ?? $product->name }}"
                 width="1122" height="1402"
                 loading="{{ $eager ? 'eager' : 'lazy' }}"
                 fetchpriority="{{ $eager ? 'high' : 'auto' }}"
                 decoding="async"
                 class="h-full w-full object-cover transition-transform duration-700 ease-hoor
                        group-hover:scale-105 {{ $soldOut ? 'opacity-60' : '' }}">
        @else
            <span class="flex h-full w-full items-center justify-center">
                <img src="{{ asset('images/brand/hoor-icon-blue.svg') }}" alt=""
                     class="w-1/4 opacity-20">
            </span>
        @endif

        {{-- Badges: at most two, so the image is never buried under labels. --}}
        <div class="absolute start-3 top-3 flex flex-col items-start gap-1.5">
            @if ($product->isOnSale())
                <x-store.badge tone="sale">-{{ $product->discountPercentage() }}%</x-store.badge>
            @elseif ($product->is_new)
                <x-store.badge tone="new">{{ __('catalog.labels.new') }}</x-store.badge>
            @endif

            @if ($soldOut)
                <x-store.badge tone="muted">{{ __('catalog.stock.out_of_stock') }}</x-store.badge>
            @elseif ($stock === \App\Enums\StockStatus::LowStock)
                <x-store.badge tone="low">{{ __('catalog.stock.low_stock') }}</x-store.badge>
            @endif
        </div>

        {{-- Quick action, revealed on hover and always available to keyboards. --}}
        @unless ($soldOut)
            <span class="pointer-events-none absolute inset-x-3 bottom-3 translate-y-2 opacity-0
                         transition duration-300 ease-hoor
                         group-hover:translate-y-0 group-hover:opacity-100
                         group-focus-within:translate-y-0 group-focus-within:opacity-100">
                <span class="block rounded-sm bg-hoor-navy-500/95 px-4 py-2.5 text-center
                             text-xs font-medium tracking-wide text-hoor-cream-50 backdrop-blur">
                    {{ __('catalog.labels.view_product') }}
                </span>
            </span>
        @endunless
    </a>

    {{-- Details --}}
    <div class="flex flex-1 flex-col pt-3">
        @if ($product->category)
            <p class="text-xs tracking-wide text-hoor-muted">{{ $product->category->name }}</p>
        @endif

        <h3 class="mt-1 font-sans text-sm font-medium leading-snug text-hoor-navy-700">
            {{-- Stretched link keeps the whole card clickable without nesting
                 anchors, which would be invalid markup. --}}
            <a href="{{ $url }}" class="transition group-hover:text-hoor-gold-600">
                {{ $product->name }}
            </a>
        </h3>

        {{-- Colour swatches, read from the variants the listing already loaded.
             Purely indicative: choosing a colour happens on the product page. --}}
        @php
            $swatches = $product->relationLoaded('variants')
                ? $product->variants->where('is_active', true)->pluck('color')->filter()->unique('id')->values()
                : collect();
        @endphp

        @if ($swatches->count() > 1)
            <div class="mt-2 flex items-center gap-1.5"
                 aria-label="{{ trans_choice('store.shop.colors_count', $swatches->count(), ['count' => $swatches->count()]) }}">
                @foreach ($swatches->take(5) as $swatch)
                    <span class="h-3.5 w-3.5 rounded-full border border-hoor-cream-300"
                          style="background-color: {{ $swatch->hex }}"
                          title="{{ $swatch->name }}"></span>
                @endforeach

                @if ($swatches->count() > 5)
                    <span class="text-[0.65rem] text-hoor-muted">+{{ $swatches->count() - 5 }}</span>
                @endif
            </div>
        @endif

        <div class="mt-auto pt-2">
            <x-store.product-price :product="$product" />
        </div>
    </div>
</article>
