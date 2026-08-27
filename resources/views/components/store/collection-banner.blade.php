{{--
    Featured collection banner: a full-bleed navy panel with brand photography.
    The discount headline is driven by the products actually on sale, so it can
    never advertise a promotion the catalog does not have.
--}}
@props(['products' => null])

@php
    $onSale = $products?->isNotEmpty() ?? false;

    // The deepest genuine discount currently live.
    $maxDiscount = $onSale
        ? $products->max(fn ($product) => $product->discountPercentage())
        : 0;

    $shopUrl = \Illuminate\Support\Facades\Route::has('store.shop')
        ? route('store.shop')
        : route('store.home');
@endphp

<section class="bg-hoor-navy-500 text-hoor-cream-50">
    <div class="hoor-container grid items-stretch gap-0 lg:grid-cols-2">

        {{-- Copy --}}
        <div class="flex flex-col justify-center py-14 lg:py-20 lg:pe-12">
            <p class="text-xs font-medium uppercase tracking-editorial text-hoor-gold-500">
                {{ __('store.collection.eyebrow') }}
            </p>

            {{-- Colour set explicitly: the base h2 rule paints headings navy,
                 which would be invisible on this navy panel. --}}
            <h2 class="mt-3 font-display text-3xl leading-tight text-hoor-cream-50 sm:text-4xl lg:text-5xl">
                {{ __('store.collection.title') }}
            </h2>

            <p class="mt-5 max-w-md text-sm leading-relaxed text-hoor-cream-50/75">
                {{ __('store.collection.body') }}
            </p>

            @if ($maxDiscount > 0)
                <p class="mt-6 inline-flex w-fit items-center gap-2 rounded-sm bg-hoor-gold-500
                          px-4 py-2 text-sm font-medium text-hoor-navy-700">
                    {{ __('store.collection.discount', ['percent' => $maxDiscount]) }}
                </p>
            @endif

            <div class="mt-8">
                <x-ui.button variant="gold" size="lg" :href="$shopUrl">
                    {{ __('store.collection.cta') }}
                </x-ui.button>
            </div>
        </div>

        {{-- Imagery: two offset frames give the panel depth without a busy collage. --}}
        <div class="relative hidden items-center justify-center py-14 lg:flex lg:py-20">
            <div class="relative h-full max-h-[26rem] w-full max-w-sm">
                <div class="absolute inset-y-0 end-0 w-[78%] overflow-hidden rounded-lg shadow-soft">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('hoor.media.disk'))->url('products/hoor-9.png') }}"
                         alt="" width="1122" height="1402"
                         loading="lazy" decoding="async"
                         class="h-full w-full object-cover">
                </div>

                <div class="absolute bottom-8 start-0 aspect-square w-[45%] overflow-hidden
                            rounded-lg border-4 border-hoor-navy-500 shadow-soft">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('hoor.media.disk'))->url('products/hoor-7.png') }}"
                         alt="" width="1254" height="1254"
                         loading="lazy" decoding="async"
                         class="h-full w-full object-cover">
                </div>
            </div>
        </div>
    </div>
</section>
