{{--
    Category carousel.

    A horizontal rail rather than a wrapping grid: with six or seven categories
    a grid drops a stranded second row, which is what made the homepage look
    unfinished. A rail shows a partial card at the edge instead, which reads as
    "there is more" rather than "something is missing".

    Built on CSS scroll-snap, so the track scrolls natively — by touch, by
    trackpad, by shift+wheel — and the arrows only automate what the browser
    already does. With JavaScript off it stays a scrollable rail; nothing is
    lost but the buttons.
--}}
@props(['categories'])

@php
    // Autoplay is pointless when everything already fits.
    $count = $categories->count();
@endphp

<div x-data="categorySlider({{ $count }})"
     x-init="init()"
     @mouseenter="pause()" @mouseleave="resume()"
     @focusin="pause()" @focusout="resume()"
     class="relative">

    {{-- The rail. `scroll-smooth` gives the arrows their glide without a
         single line of animation code. --}}
    <ul x-ref="track"
        @scroll.debounce.100ms="onScroll()"
        class="no-scrollbar flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth pb-2 sm:gap-6"
        role="list">

        @foreach ($categories as $index => $category)
            {{-- Revealed by the site-wide observer in
                 store.partials.reveal-script. --}}
            <li style="--reveal-delay: {{ $index * 70 }}ms"
                class="reveal w-[68%] shrink-0 snap-start sm:w-[45%] lg:w-[calc((100%-4.5rem)/4)]">

                <x-store.category-card :category="$category" :eager="$index < 2" class="h-full" />
            </li>
        @endforeach
    </ul>

    {{-- Arrows. Hidden from assistive tech: the rail is already reachable and
         operable without them, so they would only add noise. --}}
    <template x-if="scrollable">
        <div>
            @foreach (['prev' => 'start', 'next' => 'end'] as $direction => $edge)
                <button type="button"
                        @click="{{ $direction }}()"
                        x-show="can{{ ucfirst($direction) }}"
                        x-transition.opacity
                        aria-hidden="true" tabindex="-1"
                        class="absolute top-1/2 hidden h-10 w-10 -translate-y-1/2 items-center justify-center
                               rounded-full bg-white/90 text-hoor-navy-700 shadow-card backdrop-blur
                               transition hover:bg-white hover:text-hoor-gold-600 lg:flex
                               {{ $edge === 'start' ? '-start-4' : '-end-4' }}">
                    <svg class="h-4 w-4 {{ $edge === 'start' ? 'rtl:rotate-180' : 'rotate-180 rtl:rotate-0' }}"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            @endforeach
        </div>
    </template>

    {{-- Dots: one per page of cards, not one per card. --}}
    <template x-if="scrollable">
        <div class="mt-5 flex justify-center gap-2">
            <template x-for="page in pages" :key="page">
                <button type="button"
                        @click="goTo(page - 1)"
                        :aria-label="`${@js(__('store.categories.eyebrow'))} ${page}`"
                        :aria-current="activePage === page - 1"
                        class="h-1.5 rounded-full transition-all duration-300"
                        :class="activePage === page - 1
                            ? 'w-6 bg-hoor-navy-500'
                            : 'w-1.5 bg-hoor-cream-400 hover:bg-hoor-navy-300'"></button>
            </template>
        </div>
    </template>
</div>

@once
    @push('scripts')
        <script>
            /**
             * The category rail.
             *
             * Position is read from the track's own scrollLeft rather than kept
             * in a variable: the rail can be scrolled by touch or trackpad
             * without the component knowing, and a remembered index would drift
             * out of step with what is actually on screen.
             */
            function categorySlider(count) {
                return {
                    count,
                    activePage: 0,
                    pages: 1,
                    scrollable: false,
                    canPrev: false,
                    canNext: false,
                    timer: null,
                    paused: false,

                    init() {
                        this.measure();

                        // The card width changes at each breakpoint, so the page
                        // count has to be recomputed rather than assumed.
                        const resize = new ResizeObserver(() => this.measure());
                        resize.observe(this.$refs.track);

                        this.start();
                    },

                    measure() {
                        const track = this.$refs.track;
                        if (!track) return;

                        // A rail that fits needs no controls at all.
                        this.scrollable = track.scrollWidth > track.clientWidth + 8;
                        this.pages = Math.max(1, Math.ceil(track.scrollWidth / track.clientWidth));

                        this.onScroll();
                    },

                    onScroll() {
                        const track = this.$refs.track;
                        if (!track) return;

                        // In RTL the browser reports scrollLeft as negative or
                        // reversed depending on engine, so take the magnitude.
                        const offset = Math.abs(track.scrollLeft);
                        const max = track.scrollWidth - track.clientWidth;

                        this.activePage = Math.round(offset / track.clientWidth);
                        this.canPrev = offset > 8;
                        this.canNext = offset < max - 8;
                    },

                    goTo(page) {
                        const track = this.$refs.track;
                        if (!track) return;

                        const rtl = getComputedStyle(track).direction === 'rtl';
                        const target = page * track.clientWidth;

                        track.scrollTo({ left: rtl ? -target : target, behavior: 'smooth' });
                    },

                    next() {
                        const track = this.$refs.track;
                        const max = track.scrollWidth - track.clientWidth;

                        // Wrap round at the end, so autoplay never dead-ends.
                        if (Math.abs(track.scrollLeft) >= max - 8) {
                            this.goTo(0);
                            return;
                        }

                        this.goTo(this.activePage + 1);
                    },

                    prev() {
                        this.goTo(Math.max(0, this.activePage - 1));
                    },

                    /**
                     * Autoplay, but only when it would do something and only for
                     * visitors who have not asked for less motion.
                     */
                    start() {
                        const stillPrefers = window.matchMedia('(prefers-reduced-motion: reduce)');

                        if (!this.scrollable || stillPrefers.matches) return;

                        this.timer = setInterval(() => {
                            if (!this.paused && !document.hidden) this.next();
                        }, 4500);
                    },

                    pause() { this.paused = true; },
                    resume() { this.paused = false; },
                };
            }
        </script>
    @endpush
@endonce
