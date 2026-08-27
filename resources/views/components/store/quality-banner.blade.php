{{--
    Quality banner: a single horizontal band carrying the brand promise.

    Three zones — a navy panel with the headline and call to action, four
    fabric qualities in the middle, and product photography closing the band.
    The navy panel's trailing edge is curved so it reads as one composed strip
    rather than three stacked blocks.
--}}
@php
    $disk = \Illuminate\Support\Facades\Storage::disk(config('hoor.media.disk'));

    $shopUrl = \Illuminate\Support\Facades\Route::has('store.shop')
        ? route('store.shop')
        : route('store.home');

    // Paths are the outline artwork for each quality; kept beside the copy key
    // so a new quality is one array entry rather than new markup.
    $qualities = [
        'fabric' => 'M12 3c1.6 2.2 1.6 4.3 0 6.5-1.6 2.2-1.6 4.3 0 6.5M8 6c1.2 1.6 1.2 3.2 0 4.8M16 6c-1.2 1.6-1.2 3.2 0 4.8M12 16v5M9 21h6',
        'soft'   => 'M3 12c2.5-2 5-2 7.5 0s5 2 7.5 0M3 16.5c2.5-2 5-2 7.5 0s5 2 7.5 0M12 3.5c1.5 1.6 1.5 3.2 0 4.8',
        'fit'    => 'M8.5 3l3.5 2 3.5-2 2.5 3.5-2 2v10.5H8V8.5l-2-2z',
        'style'  => 'M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1M12 8.5a3.5 3.5 0 100 7 3.5 3.5 0 000-7z',
    ];
@endphp

<section class="hoor-container py-10 lg:py-14">
    <div class="relative overflow-hidden rounded-lg bg-hoor-cream-100 shadow-card">
        <div class="grid items-stretch lg:grid-cols-12">

            {{-- Navy panel. On large screens the fill is drawn as an SVG so its
                 trailing edge can bow into the cream; below lg it is a plain
                 block, where a curve would only crowd the copy. --}}
            <div class="relative z-10 bg-hoor-navy-700 px-7 py-9 text-hoor-cream-50
                        sm:px-10 lg:col-span-4 lg:bg-transparent lg:py-14">

                <svg class="absolute inset-y-0 -end-24 start-0 hidden h-full w-[calc(100%+6rem)]
                            lg:block rtl:scale-x-[-1]"
                     viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                    <path d="M0 0 H72 C88 24, 88 76, 72 100 H0 Z" fill="#082540" />
                </svg>

                <div class="relative">

                <h2 class="font-display text-2xl italic leading-tight text-hoor-cream-50 sm:text-3xl">
                    {{ __('store.quality.title') }}
                </h2>

                <p class="mt-3 max-w-xs text-sm leading-relaxed text-hoor-cream-50/75">
                    {{ __('store.quality.lead') }}
                </p>

                <a href="{{ $shopUrl }}"
                   class="group mt-6 inline-flex items-center gap-2 rounded-sm border border-hoor-gold-500
                          px-6 py-3 text-sm font-medium tracking-wide text-hoor-gold-500
                          transition duration-200 ease-hoor hover:bg-hoor-gold-500 hover:text-hoor-navy-700">
                    {{ __('store.quality.cta') }}
                    <span class="transition-transform group-hover:translate-x-1 rtl:rotate-180
                                 rtl:group-hover:-translate-x-1" aria-hidden="true">&rarr;</span>
                </a>
                </div>
            </div>

            {{-- Qualities --}}
            <div class="relative z-10 grid grid-cols-2 items-center gap-6 px-7 py-9
                        sm:grid-cols-4 sm:px-10 lg:col-span-5 lg:gap-2 lg:ps-24 lg:pe-6 lg:py-14">
                @foreach ($qualities as $key => $path)
                    <div class="text-center">
                        <svg class="mx-auto h-8 w-8 text-hoor-navy-500" fill="none" stroke="currentColor"
                             stroke-width="1.3" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
                        </svg>

                        <p class="mt-3 text-xs font-semibold text-hoor-navy-700">
                            {{ __("store.quality.items.{$key}.title") }}
                        </p>
                        <p class="mt-1 text-[0.7rem] leading-snug text-hoor-muted">
                            {{ __("store.quality.items.{$key}.body") }}
                        </p>
                    </div>
                @endforeach
            </div>

            {{-- Photography: decorative, so it is hidden from assistive tech and
                 dropped entirely on small screens where it would only shrink
                 the copy. --}}
            <div class="hidden lg:col-span-3 lg:block">
                <img src="{{ $disk->url('products/hoor-7.png') }}"
                     alt=""
                     width="1254" height="1254"
                     loading="lazy" decoding="async"
                     class="h-full w-full object-cover">
            </div>
        </div>
    </div>
</section>
