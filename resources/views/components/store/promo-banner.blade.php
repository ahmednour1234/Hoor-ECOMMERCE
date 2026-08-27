{{--
    An admin-managed promotional panel.

    Takes the homepage slot the static collection banner otherwise fills, so a
    sale can be put up and taken down without a deployment. The image is
    optional: a text-only banner on the brand navy still reads.
--}}
@props(['banner'])

@php
    $image = $banner->imageUrl();
@endphp

<section class="relative overflow-hidden bg-hoor-navy-500 text-hoor-cream-50">
    @if ($image)
        <img src="{{ $image }}" alt=""
             class="absolute inset-0 h-full w-full object-cover opacity-40"
             loading="lazy" decoding="async">
    @endif

    <div class="hoor-container relative py-16 text-center lg:py-20">
        @if ($banner->title)
            <h2 class="font-display text-3xl italic leading-tight text-hoor-cream-50 sm:text-4xl">
                {{ $banner->title }}
            </h2>
        @endif

        @if ($banner->body)
            <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-hoor-cream-50/85 sm:text-base">
                {{ $banner->body }}
            </p>
        @endif

        @if ($banner->cta_url)
            <div class="mt-8">
                <a href="{{ $banner->cta_url }}"
                   class="group inline-flex items-center gap-2 rounded-sm bg-hoor-gold-500 px-8 py-3.5
                          text-sm font-medium tracking-wide text-hoor-navy-700 shadow-card
                          transition duration-200 ease-hoor hover:bg-hoor-gold-600 hover:shadow-card-hover">
                    {{ $banner->cta_label ?: __('common.actions.shop_now') }}
                    <span class="transition-transform group-hover:translate-x-1 rtl:rotate-180
                                 rtl:group-hover:-translate-x-1" aria-hidden="true">&rarr;</span>
                </a>
            </div>
        @endif
    </div>
</section>
