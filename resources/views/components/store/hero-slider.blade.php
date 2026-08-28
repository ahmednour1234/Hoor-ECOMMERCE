{{--
    Hero slider.

    A full-bleed photograph carrying the brand mark and headline as live text,
    so the copy translates, is indexable, and can be edited without redrawing an
    image. Slides stay in the DOM; autoplay pauses on hover and honours
    prefers-reduced-motion.
--}}
@props(['slides' => null])

@php
    $disk = \Illuminate\Support\Facades\Storage::disk(config('hoor.media.disk'));

    /*
     * Falls back to the brand slides when the admin has configured none.
     *
     * These are wide 12:5 plates composed from the 4:5 studio portraits: the
     * model sits toward the start edge with open ground after her, so the
     * headline has clear space at every viewport width. Cropping the portraits
     * directly would cut the garment out of frame.
     */
    $slides = $slides ?: [
        ['image' => 'hero/hero-1.jpg', 'backdrop' => '#CAB296'],
        ['image' => 'hero/hero-2.jpg', 'backdrop' => '#CCB49A'],
        ['image' => 'hero/hero-3.jpg', 'backdrop' => '#DDCBB5'],
    ];

    $rtl = \App\Support\Locale::direction() === 'rtl';

    /*
     * Each plate has a right-handed twin for Arabic. An admin-uploaded slide
     * has no twin, so it falls back to the one image it has — better a
     * left-composed photograph than a broken one.
     */
    $slides = array_map(static function (array $slide) use ($disk): array {
        // The dot is inside the capture, so it is restored with it —
        // '-rtl$1' alone produced 'hero-1-rtljpg' and silently missed.
        $twin = preg_replace('/(\.(?:jpg|jpeg|png|webp))$/i', '-rtl$1', $slide['image']);

        // Checked rather than assumed: a slide uploaded through the admin has
        // no twin, and a src pointing at a file that is not there would render
        // a broken image where the hero should be.
        $slide['image_rtl'] = $disk->exists($twin) ? $twin : $slide['image'];

        return $slide;
    }, $slides);

    $shopUrl = \Illuminate\Support\Facades\Route::has('store.shop')
        ? route('store.shop')
        : route('store.home');
@endphp

<section
    x-data="{
        active: 0,
        count: {{ count($slides) }},
        timer: null,
        start() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            this.stop();
            this.timer = setInterval(() => this.next(), 6000);
        },
        stop() { clearInterval(this.timer); },
        next() { this.active = (this.active + 1) % this.count; },
        prev() { this.active = (this.active - 1 + this.count) % this.count; },
        go(i)  { this.active = i; this.start(); },
    }"
    x-init="start()"
    @mouseenter="stop()" @mouseleave="start()"
    @keydown.left.window="prev()" @keydown.right.window="next()"
    class="relative overflow-hidden"
    aria-roledescription="carousel"
    aria-label="{{ __('store.hero.label') }}">

    {{-- Fixed height reserves the space before the photograph loads, so the
         page never jumps as slides arrive. --}}
    <div class="relative h-[30rem] w-full sm:h-[32rem] md:h-[30rem] lg:h-[44rem] xl:h-[46rem]">
        @foreach ($slides as $index => $slide)
            <div x-show="active === {{ $index }}"
                 x-transition:enter="transition ease-hoor duration-700"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-hoor duration-700"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @if ($index > 0) x-cloak @endif
                 class="absolute inset-0"
                 style="background-color: {{ ($slide['backdrop'] ?? null) ?: '#CAB296' }};"
                 role="group"
                 aria-roledescription="slide"
                 aria-label="{{ $index + 1 }} / {{ count($slides) }}">

                {{-- A slow drift while the slide is on screen, so the band is
                     never quite still. Applied only to the active slide, so
                     the animation restarts with each turn. --}}
                {{--
                    A plate composed for the reading direction.

                    object-position could not solve this: the plate is 2.00 and
                    the band about 2.05, so object-cover crops nothing
                    horizontally and the focal point has nothing to move.

                    The Arabic plate is the same photograph with the subject
                    slid to the other side — not mirrored, because that would
                    reverse the jacket's buttons and placket and show a garment
                    that does not exist.
                --}}
                <img src="{{ $disk->url($rtl ? $slide['image_rtl'] : $slide['image']) }}"
                     alt=""
                     width="1122" height="1402"
                     loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                     fetchpriority="{{ $index === 0 ? 'high' : 'auto' }}"
                     decoding="async"
                     {{--
                         The crop window, anchored to where the model stands.

                         A phone band is far narrower than the 2:1 plate, so
                         object-cover keeps only about 40% of its width.
                         Centred, that window falls at 30%-70% of the plate —
                         the middle of the model. Anchored to the leading edge
                         it covers 0%-40%, where she actually is.

                         The Arabic plate places her on the other side, so the
                         anchor flips with the writing direction. From lg the
                         band is as wide as the plate and nothing is cropped,
                         which is why the anchor is reset to centre there.
                     --}}
                     :class="active === {{ $index }} ? 'hero-drift' : ''"
                     class="h-full w-full object-cover
                            [object-position:12%_center] rtl:[object-position:88%_center]
                            lg:object-center">

                {{--
                    This slide's own words.

                    Inside the loop, because outside it every slide rendered
                    the last one's copy — the eyebrow read "Cash on delivery"
                    on all three.
                --}}
                {{--
                    The copy occupies the half the model does not.

                    A scrim covers the whole band on small screens, where the
                    photograph fills the width and there is no free side; from
                    sm up the words sit in the open half and the scrim lifts.
                --}}
                {{--
                    The copy sits beside the model, in the half of the plate
                    composed to be left empty.

                    A scrim carries it on small screens, where the crop is
                    tight enough that the photograph reaches under the words;
                    from lg the band shows the whole plate and the open half is
                    genuinely empty, so the scrim lifts.
                --}}
                <div class="absolute inset-0 flex items-center bg-hoor-beige-100/70 sm:bg-transparent">
                    <div class="hoor-container">
                        <div class="ms-auto max-w-md text-center sm:max-w-sm lg:me-10 lg:max-w-md
                                    sm:rounded-md sm:bg-hoor-beige-100/45 sm:p-6 sm:backdrop-blur-[2px]
                                    lg:bg-transparent lg:p-0 lg:backdrop-blur-none">

                            <img src="{{ asset('images/brand/hoor-primary-blue.svg') }}"
                                 alt="{{ __('common.brand') }}"
                                 :class="active === {{ $index }} ? 'hero-in' : ''"
                                 style="--hero-delay: 60ms"
                                 class="mx-auto w-56 sm:w-64 lg:w-80">

                            @if (! empty($slide['eyebrow'] ?? null))
                                <p :class="active === {{ $index }} ? 'hero-in' : ''"
                                   style="--hero-delay: 160ms"
                                   class="mt-5 text-xs font-medium uppercase tracking-editorial text-hoor-navy-600/70">
                                    {{ $slide['eyebrow'] }}
                                </p>
                            @endif

                            <h1 :class="active === {{ $index }} ? 'hero-in' : ''"
                                style="--hero-delay: 240ms"
                                class="mt-6 font-display text-3xl italic leading-tight text-hoor-navy-700
                                       sm:text-4xl lg:text-[2.75rem]">
                                {{ ($slide['headline'] ?? null) ?: __('store.hero.headline') }}
                            </h1>

                            @if (empty($slide['headline'] ?? null))
                                <p :class="active === {{ $index }} ? 'hero-in' : ''"
                                   style="--hero-delay: 300ms"
                                   class="mt-3 font-arabic-display text-lg leading-relaxed text-hoor-navy-600 sm:text-xl"
                                   lang="ar" dir="rtl">
                                    {{ __('store.hero.headline_ar') }}
                                </p>
                            @endif

                            <p :class="active === {{ $index }} ? 'hero-in' : ''"
                               style="--hero-delay: 360ms"
                               class="mt-4 text-sm text-hoor-navy-600/80 sm:text-base">
                                {{ ($slide['subheadline'] ?? null) ?: __('store.hero.tagline') }}
                            </p>

                            <div :class="active === {{ $index }} ? 'hero-in' : ''"
                                 style="--hero-delay: 440ms"
                                 class="mt-7">
                                <a href="{{ ($slide['cta_url'] ?? null) ?: $shopUrl }}"
                                   class="group inline-flex items-center gap-2 rounded-sm bg-hoor-navy-500 px-8 py-3.5
                                          text-sm font-medium tracking-wide text-hoor-cream-50 shadow-card
                                          transition duration-200 ease-hoor hover:bg-hoor-navy-700 hover:shadow-card-hover">
                                    {{ ($slide['cta_label'] ?? null) ?: __('common.actions.shop_now') }}
                                    <span class="transition-transform group-hover:translate-x-1 rtl:rotate-180
                                                 rtl:group-hover:-translate-x-1" aria-hidden="true">&rarr;</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Arrows --}}
        @if (count($slides) > 1)
            <button type="button" @click="prev(); start()"
                    class="absolute start-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center
                           justify-center rounded-full bg-white/85 text-hoor-navy-600 shadow-card
                           backdrop-blur transition hover:bg-white lg:start-6"
                    aria-label="{{ __('store.hero.previous') }}">
                <span class="rtl:rotate-180" aria-hidden="true">&#8249;</span>
            </button>

            <button type="button" @click="next(); start()"
                    class="absolute end-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center
                           justify-center rounded-full bg-white/85 text-hoor-navy-600 shadow-card
                           backdrop-blur transition hover:bg-white lg:end-6"
                    aria-label="{{ __('store.hero.next') }}">
                <span class="rtl:rotate-180" aria-hidden="true">&#8250;</span>
            </button>

            {{-- Dots --}}
            <div class="absolute inset-x-0 bottom-5 z-10 flex items-center justify-center gap-2">
                @foreach ($slides as $index => $slide)
                    <button type="button" @click="go({{ $index }})"
                            class="h-2 rounded-full transition-all duration-300"
                            :class="active === {{ $index }}
                                ? 'w-6 bg-hoor-navy-500'
                                : 'w-2 bg-hoor-navy-500/30 hover:bg-hoor-navy-500/60'"
                            :aria-current="active === {{ $index }}"
                            aria-label="{{ __('store.hero.go_to', ['number' => $index + 1]) }}"></button>
                @endforeach
            </div>
        @endif
    </div>
</section>
