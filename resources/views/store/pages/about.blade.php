{{--
    About HOOR.

    An editorial page rather than a text block: the brand's argument is made in
    photographs as much as words, so the layout alternates image and copy down
    the page and lets the navy band carry the quality claim.

    Copy comes from lang files for the structural labels and from settings for
    the prose the shop may want to reword (see PageController) — a heading typed
    in the admin overrides the default without a deployment.
--}}
@php
    $disk = \Illuminate\Support\Facades\Storage::disk(config('hoor.media.disk'));

    // The photographs, chosen per slot: full-length for the hero, detail crops
    // for the collages, texture for the quality band.
    $photo = fn (string $file): string => $disk->url('products/'.$file);

    $adminHeading = $heading !== __('store.pages.about') ? $heading : null;
@endphp

<x-layouts.store>
    @section('title', __('store.pages.about'))
    @section('description', $intro ?: strip_tags(__('store.about.lead')))

    {{-- ── 1. Hero ─────────────────────────────────────────────────────────
         A background band, as on the homepage: the photograph fills its side
         of the section and dissolves into the cream, so picture and panel read
         as one piece. Kept short — a 4:5 portrait covering a tall band shows
         only its middle, which is what cropped the model's head before. --}}
    <section class="relative overflow-hidden bg-hoor-beige-100">

        {{--
            The photograph.

            Anchored to the top of the source, so the crop takes the hem rather
            than the face. At this band height roughly 55% of the portrait is
            visible, which is her head down to the knee.
        --}}
        <img src="{{ $photo('hoor-3.png') }}"
             alt="{{ __('common.brand') }}"
             width="1123" height="1404"
             fetchpriority="high" decoding="async"
             class="absolute inset-y-0 start-0 h-full w-full object-cover
                    [object-position:50%_0%] sm:w-2/3 lg:w-1/2">

        {{--
            The fade.

            Fully transparent across the photograph and only ramping up past
            its trailing edge, so the picture keeps its own contrast instead of
            being washed out — the gradient exists to hide the seam, not to
            tint the model.

            The wash begins over the photograph itself rather than at its
            edge. That is the point: a gradient that only starts where the
            picture ends leaves a hard vertical seam, which is exactly what it
            is there to hide. Starting it early costs a little contrast at the
            trailing side of the image and buys a dissolve with no edge at all.
        --}}
        <div class="absolute inset-0
                    bg-gradient-to-t from-hoor-beige-100 via-hoor-beige-100/70 to-transparent
                    sm:bg-gradient-to-r sm:from-transparent sm:from-15%
                    sm:via-hoor-beige-100/55 sm:via-45% sm:to-hoor-beige-100 sm:to-80%
                    rtl:sm:bg-gradient-to-l"
             aria-hidden="true"></div>

        <div class="hoor-container relative">
            <div class="grid gap-6 py-10 lg:grid-cols-[0.9fr_1.1fr] lg:gap-10">

                {{-- Empty on large screens: the photograph occupies it. --}}
                <div class="hidden lg:block"></div>

                {{-- Room for the header's overhanging wordmark, as on the
                     contact page. --}}
                <div class="text-center lg:flex lg:min-h-[22rem] lg:flex-col lg:justify-center
                            lg:pe-8 lg:pt-16 lg:text-start">
                    <p class="font-arabic-display text-base text-hoor-navy-600/80 sm:text-lg"
                       lang="ar" dir="rtl">
                        {{ __('store.about.eyebrow') }}
                    </p>

                    <h1 class="mt-3 font-display text-3xl italic leading-tight text-hoor-navy-700 sm:text-4xl lg:text-5xl">
                        {!! $adminHeading ? e($adminHeading) : __('store.about.headline') !!}
                    </h1>

                    <p class="mx-auto mt-4 max-w-md text-sm leading-relaxed text-hoor-navy-600/80 sm:text-base lg:mx-0">
                        {{ $intro ?: __('store.about.lead') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── 2. Our Mission ──────────────────────────────────────────────── --}}
    <section class="hoor-container py-14 lg:py-20">
        <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-14">

            <div>
                <h2 class="font-display text-2xl italic text-hoor-navy-700 sm:text-3xl">
                    {{ __('store.about.mission.eyebrow') }}
                </h2>

                <span class="mt-3 block h-px w-16 bg-hoor-gold-500"></span>

                <p class="mt-5 max-w-lg text-sm leading-relaxed text-hoor-navy-600/85 sm:text-base">
                    {{ $body ?: __('store.about.mission.body') }}
                </p>

                {{-- Four pillars, as icon + label. --}}
                <ul class="mt-8 grid grid-cols-2 gap-x-6 gap-y-6 sm:grid-cols-4">
                    @foreach (['quality' => 'gem', 'modest' => 'leaf', 'made' => 'heart', 'timeless' => 'clock'] as $key => $icon)
                        <li class="text-center sm:text-start">
                            <x-store.about-icon :name="$icon" class="mx-auto h-7 w-7 text-hoor-navy-500 sm:mx-0" />

                            <p class="mt-2 text-xs font-medium leading-snug text-hoor-navy-700">
                                {{ __('store.about.mission.pillars.'.$key) }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Two-image collage: a wide plate with a portrait overlapping it. --}}
            <div class="relative">
                <img src="{{ $photo('hoor-3.png') }}" alt=""
                     width="1123" height="1404" loading="lazy" decoding="async"
                     class="aspect-[4/3] w-full rounded-sm object-cover object-top">

                <img src="{{ $photo('hoor-1.png') }}" alt=""
                     width="1123" height="1404" loading="lazy" decoding="async"
                     class="absolute -bottom-6 end-4 hidden w-40 rounded-sm border-4 border-white
                            object-cover shadow-card sm:block lg:w-48">
            </div>
        </div>
    </section>

    {{-- ── 3. Modest by Choice ─────────────────────────────────────────── --}}
    <section class="bg-hoor-cream-100">
        <div class="hoor-container py-14 lg:py-20">
            <div class="grid items-center gap-10 lg:grid-cols-[1fr_1fr_0.85fr] lg:gap-10">

                {{-- Collage: one tall image beside two stacked. --}}
                <div class="grid grid-cols-2 gap-3">
                    <img src="{{ $photo('hoor-8.png') }}" alt=""
                         width="1123" height="1404" loading="lazy" decoding="async"
                         class="col-span-2 aspect-[16/10] w-full rounded-sm object-cover">

                    <img src="{{ $photo('hoor-2.png') }}" alt=""
                         width="1123" height="1404" loading="lazy" decoding="async"
                         class="aspect-square w-full rounded-sm object-cover object-top">

                    <img src="{{ $photo('hoor-6.png') }}" alt=""
                         width="1123" height="1404" loading="lazy" decoding="async"
                         class="aspect-square w-full rounded-sm object-cover">
                </div>

                <div class="text-center lg:text-start">
                    <p class="font-arabic-display text-base text-hoor-navy-600/80" lang="ar" dir="rtl">
                        {{ __('store.about.modest.eyebrow') }}
                    </p>

                    <h2 class="mt-3 font-display text-2xl italic leading-tight text-hoor-navy-700 sm:text-3xl">
                        {!! __('store.about.modest.headline') !!}
                    </h2>

                    <p class="mt-4 text-sm leading-relaxed text-hoor-navy-600/85">
                        {{ $values ?: __('store.about.modest.body') }}
                    </p>
                </div>

                {{-- Four features, icon beside two lines of copy. --}}
                <ul class="space-y-5">
                    @foreach ([
                        'coverage' => 'shield',
                        'flatter'  => 'sparkle',
                        'comfort'  => 'feather',
                        'timeless' => 'clock',
                    ] as $key => $icon)
                        <li class="flex items-start gap-3">
                            <x-store.about-icon :name="$icon"
                                                class="mt-0.5 h-6 w-6 shrink-0 text-hoor-navy-500" />

                            <div>
                                <p class="text-sm font-medium text-hoor-navy-700">
                                    {{ __('store.about.modest.features.'.$key.'.title') }}
                                </p>
                                <p class="text-xs leading-relaxed text-hoor-muted">
                                    {{ __('store.about.modest.features.'.$key.'.body') }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- ── 4. Quality You Can Feel ─────────────────────────────────────────
         The navy band. The claim about fabric is made on the brand's own
         colour, with a denim photograph carrying it. --}}
    <section class="hoor-container py-14 lg:py-20">
        <div class="grid overflow-hidden rounded-sm bg-hoor-navy-500 lg:grid-cols-[1.35fr_0.65fr]">

            <div class="p-8 text-hoor-cream-50 sm:p-10 lg:p-12">
                <h2 class="font-display text-2xl italic text-hoor-cream-50 sm:text-3xl">
                    {{ __('store.about.quality.headline') }}
                </h2>

                <p class="mt-4 max-w-xl text-sm leading-relaxed text-hoor-cream-50/75">
                    {{ __('store.about.quality.body') }}
                </p>

                <ul class="mt-8 grid grid-cols-2 gap-6 sm:grid-cols-4">
                    @foreach ([
                        'premium' => 'gem',
                        'expert'  => 'needle',
                        'durable' => 'shield',
                        'inspect' => 'badge',
                    ] as $key => $icon)
                        <li class="text-center sm:text-start">
                            <x-store.about-icon :name="$icon"
                                                class="mx-auto h-7 w-7 text-hoor-gold-500 sm:mx-0" />

                            <p class="mt-2 text-xs font-medium leading-snug">
                                {{ __('store.about.quality.points.'.$key.'.title') }}
                            </p>
                            <p class="text-xs text-hoor-cream-50/60">
                                {{ __('store.about.quality.points.'.$key.'.body') }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="relative min-h-[12rem]">
                <img src="{{ $photo('hoor-9.png') }}" alt=""
                     width="1123" height="1404" loading="lazy" decoding="async"
                     class="absolute inset-0 h-full w-full object-cover">
            </div>
        </div>
    </section>

    {{-- ── 5. A note from our founder ──────────────────────────────────── --}}
    <section class="hoor-container pb-14 lg:pb-20">
        <div class="grid items-center gap-8 rounded-sm bg-hoor-beige-100 p-6 sm:p-8
                    lg:grid-cols-[auto_1.4fr_0.9fr] lg:gap-10 lg:p-10">

            {{-- Portrait, circular, as in the design. --}}
            <div class="mx-auto lg:mx-0">
                <img src="{{ $photo('hoor-4.png') }}"
                     alt="{{ __('store.about.founder.name') }}"
                     width="1123" height="1404" loading="lazy" decoding="async"
                     class="h-28 w-28 rounded-full border-4 border-white object-cover object-top
                            shadow-card sm:h-32 sm:w-32">
            </div>

            <div class="text-center lg:text-start">
                <p class="text-xs font-medium uppercase tracking-editorial text-hoor-muted">
                    {{ __('store.about.founder.eyebrow') }}
                </p>

                <h2 class="mt-2 font-display text-2xl italic text-hoor-navy-700">
                    {{ __('store.about.founder.headline') }}
                </h2>

                <p class="mt-3 text-sm leading-relaxed text-hoor-navy-600/85">
                    {{ __('store.about.founder.body') }}
                </p>

                <p class="mt-4 font-display text-lg italic text-hoor-navy-700">
                    &mdash; {{ __('store.about.founder.name') }}
                </p>
            </div>

            {{-- Pull quote. --}}
            <blockquote class="relative rounded-sm bg-white p-6 text-center shadow-card lg:text-start">
                <span class="font-display text-4xl leading-none text-hoor-gold-500" aria-hidden="true">&ldquo;</span>

                <p class="mt-1 text-sm leading-relaxed text-hoor-navy-700">
                    {{ __('store.about.founder.quote') }}
                </p>
            </blockquote>
        </div>
    </section>

    {{-- ── 6. Our Values ───────────────────────────────────────────────── --}}
    <section class="border-y border-hoor-cream-300 bg-white">
        <div class="hoor-container py-10 lg:py-12">
            <h2 class="text-center font-display text-xl italic text-hoor-navy-700">
                {{ __('store.about.values.eyebrow') }}
            </h2>

            <span class="mx-auto mt-3 block h-px w-16 bg-hoor-gold-500"></span>

            <ul class="mt-8 grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ([
                    'faith'     => 'moon',
                    'integrity' => 'shield',
                    'empower'   => 'sparkle',
                    'sustain'   => 'leaf',
                    'community' => 'people',
                ] as $key => $icon)
                    <li class="text-center">
                        <x-store.about-icon :name="$icon" class="mx-auto h-7 w-7 text-hoor-navy-500" />

                        <p class="mt-2 text-xs font-medium text-hoor-navy-700">
                            {{ __('store.about.values.items.'.$key.'.title') }}
                        </p>
                        <p class="text-xs text-hoor-muted">
                            {{ __('store.about.values.items.'.$key.'.body') }}
                        </p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ── 7. Follow / newsletter ──────────────────────────────────────── --}}
    <section class="bg-hoor-cream-100">
        <div class="hoor-container py-10 lg:py-12">
            <div class="grid gap-8 lg:grid-cols-[0.8fr_1.6fr_0.8fr] lg:items-center lg:gap-8">

                <div class="text-center lg:text-start">
                    <h2 class="font-display text-lg italic text-hoor-navy-700">
                        {{ __('store.about.follow.title') }}
                    </h2>

                    <p class="mt-1 text-xs text-hoor-muted" dir="ltr">
                        {{ __('store.about.follow.handle') }}
                    </p>

                    @php $instagram = $contact->socials()['instagram'] ?? null; @endphp

                    @if ($instagram)
                        <x-ui.button variant="primary" size="sm" class="mt-4"
                                     :href="$instagram" target="_blank" rel="noopener noreferrer">
                            {{ __('store.about.follow.cta') }}
                        </x-ui.button>
                    @endif
                </div>

                {{-- A strip of the collection, linking through to the shop. --}}
                <ul class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                    @foreach (['hoor-1', 'hoor-2', 'hoor-3', 'hoor-6', 'hoor-7', 'hoor-10'] as $file)
                        <li>
                            <a href="{{ route('store.shop') }}" class="block overflow-hidden rounded-sm">
                                <img src="{{ $photo($file.'.png') }}" alt=""
                                     width="1123" height="1404" loading="lazy" decoding="async"
                                     class="aspect-square w-full object-cover object-top transition
                                            duration-500 ease-hoor hover:scale-105">
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{-- The newsletter, posting to the real subscribe route. --}}
                <div class="text-center lg:text-start">
                    <h2 class="font-display text-lg italic text-hoor-navy-700">
                        {{ __('store.about.newsletter.title') }}
                    </h2>

                    <p class="mt-1 text-xs leading-relaxed text-hoor-muted">
                        {{ __('store.about.newsletter.body') }}
                    </p>

                    <form method="POST" action="{{ route('store.newsletter.subscribe') }}"
                          class="mt-4 flex gap-2">
                        @csrf

                        <label for="about-newsletter" class="sr-only">
                            {{ __('store.footer.email_placeholder') }}
                        </label>

                        <input type="email" id="about-newsletter" name="email" required dir="ltr"
                               placeholder="{{ __('store.footer.email_placeholder') }}"
                               class="form-input py-2 text-sm">

                        {{-- Honeypot, as on every public form. --}}
                        <input type="text" name="website" tabindex="-1" autocomplete="off"
                               class="hidden" aria-hidden="true">

                        <x-ui.button type="submit" variant="primary" size="sm">
                            {{ __('store.footer.subscribe') }}
                        </x-ui.button>
                    </form>

                    @if (session('status'))
                        <p class="mt-2 text-xs text-emerald-700">{{ session('status') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.store>
