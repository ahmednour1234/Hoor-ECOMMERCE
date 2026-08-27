{{--
    Contact HOOR.

    Every detail on this page — the numbers, the address, the hours, the map
    link — comes from settings, so a shop that moves or changes its phone does
    not need a deployment. The FAQ list is admin-managed for the same reason.
--}}
@php
    $disk = \Illuminate\Support\Facades\Storage::disk(config('hoor.media.disk'));
    $photo = fn (string $file): string => $disk->url('products/'.$file);

    $socials = $contact->socials();
    $instagram = $socials['instagram'] ?? null;
    $mapUrl = $settings->get('contact.map_url');
    $response = $settings->translated('contact.response');
@endphp

<x-layouts.store>
    @section('title', __('store.pages.contact'))
    @section('description', $intro ?: __('store.contact_page.sub'))

    {{-- ── 1. Hero ─────────────────────────────────────────────────────────
         Copy centred on cream, model at the end edge against the navy door. --}}
    <section class="relative overflow-hidden bg-hoor-beige-100">
        <div class="grid items-stretch lg:grid-cols-[1.25fr_1fr]">

            {{-- The header's wordmark hangs below the bar, so a short hero
                 reserves room for it rather than letting it land on the
                 headline. --}}
            <div class="px-4 py-12 text-center sm:px-6 lg:py-14 lg:pt-24">
                {{-- Both scripts on one line. The separator is its own
                     neutral-direction element, so it does not get pulled to
                     the wrong side of the Arabic by bidi reordering. --}}
                <h1 class="font-display text-3xl italic leading-tight text-hoor-navy-700 sm:text-4xl">
                    <span>{{ $heading }}</span>

                    <span class="mx-1 text-hoor-gold-500" aria-hidden="true">&middot;</span>

                    <span class="font-arabic-display not-italic" lang="ar" dir="rtl">{{ __('store.contact_page.headline_ar') }}</span>
                </h1>

                <span class="mx-auto mt-4 block h-px w-16 bg-hoor-gold-500"></span>

                <p class="mt-5 font-display text-lg italic text-hoor-navy-700">
                    {{ __('store.contact_page.lead') }}
                </p>

                <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-hoor-navy-600/80">
                    {{ $intro ?: __('store.contact_page.sub') }}
                </p>
            </div>

            {{--
                The copy sets the band's height and the photograph fills it,
                rather than a tall portrait forcing the whole hero to its own
                proportions and leaving the text floating in empty cream.

                This frame suits that: the model sits small in a wide surround,
                so a shorter crop takes background rather than her.
            --}}
            <div class="relative h-64 sm:h-80 lg:h-auto lg:min-h-[24rem]">
                <img src="{{ $photo('hoor-2.png') }}" alt=""
                     width="1123" height="1404"
                     fetchpriority="high" decoding="async"
                     {{-- Anchored high: at these heights only about half the
                          portrait survives the crop, so the window has to stay
                          near the top or it clips her face. --}}
                     class="absolute inset-0 h-full w-full object-cover
                            [object-position:50%_8%] lg:[object-position:50%_10%]">
            </div>
        </div>
    </section>

    {{-- ── 2. The four ways to reach us ────────────────────────────────────
         Each card is rendered only if its detail has been filled in: a card
         pointing nowhere is worse than one absent. --}}
    <section class="hoor-container pt-8 lg:pt-10">
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

            @if ($contact->phone())
                <x-store.contact-card icon="phone"
                                      :title="__('store.contact_page.cards.phone.title')"
                                      :value="$contact->phone()"
                                      :href="$contact->whatsappLink() ?? $contact->phoneLink()"
                                      :note="$contact->hours() ?: __('store.contact_page.cards.phone.note')" />
            @endif

            @if ($contact->email())
                <x-store.contact-card icon="mail"
                                      :title="__('store.contact_page.cards.email.title')"
                                      :value="$contact->email()"
                                      :href="'mailto:'.$contact->email()"
                                      :note="__('store.contact_page.cards.email.note')" />
            @endif

            @if ($instagram)
                <x-store.contact-card icon="instagram"
                                      :title="__('store.contact_page.cards.follow.title')"
                                      :value="__('store.about.follow.handle')"
                                      :href="$instagram"
                                      external
                                      :note="__('store.contact_page.cards.follow.note')" />
            @endif

            <x-store.contact-card icon="truck"
                                  :title="__('store.contact_page.cards.delivery.title')"
                                  :value="__('store.contact_page.order_help.cta')"
                                  :href="route('store.tracking.index')"
                                  :note="__('store.contact_page.cards.delivery.note')" />
        </ul>
    </section>

    {{-- ── 3. Message form and FAQ, side by side ──────────────────────── --}}
    <section class="hoor-container py-12 lg:py-16">
        <div class="grid gap-8 lg:grid-cols-2 lg:gap-10">

            @if ($showForm)
                <div class="card p-6 sm:p-7">
                    <h2 class="font-display text-xl italic text-hoor-navy-700">
                        {{ __('store.contact_page.form.title') }}
                    </h2>

                    <p class="mt-2 text-xs leading-relaxed text-hoor-muted">
                        {{ __('store.contact_page.form.lead') }}
                    </p>

                    @if (session('status'))
                        <x-ui.alert variant="success" class="mt-5">{{ session('status') }}</x-ui.alert>
                    @endif

                    @if ($errors->any())
                        <x-ui.alert variant="danger" class="mt-5">{{ $errors->first() }}</x-ui.alert>
                    @endif

                    <form method="POST" action="{{ route('store.pages.contact.send') }}"
                          class="mt-5 space-y-4">
                        @csrf

                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-ui.input name="name"
                                        :label="__('store.contact.name')"
                                        :value="old('name', auth()->user()?->name)"
                                        autocomplete="name" required />

                            <x-ui.input name="email" type="email"
                                        :label="__('store.contact.email')"
                                        :value="old('email', auth()->user()?->email)"
                                        dir="ltr" autocomplete="email" />

                            <x-ui.input name="phone" type="tel"
                                        :label="__('store.contact.phone')"
                                        :value="old('phone')"
                                        dir="ltr" inputmode="numeric" autocomplete="tel" />

                            <x-ui.input name="subject"
                                        :label="__('store.contact.subject')"
                                        :value="old('subject')" />
                        </div>

                        <p class="text-xs text-hoor-muted">{{ __('store.contact.reach_hint') }}</p>

                        <x-ui.textarea name="body" rows="5"
                                       :label="__('store.contact.message')"
                                       :value="old('body')" required />

                        {{-- A field no human sees, so anything filling it is a bot. --}}
                        <input type="text" name="website" tabindex="-1" autocomplete="off"
                               class="hidden" aria-hidden="true">

                        <x-ui.button type="submit" variant="primary" class="w-full">
                            {{ __('store.contact.send') }}
                        </x-ui.button>

                        @if ($response)
                            <p class="text-center text-xs text-hoor-muted">{{ $response }}</p>
                        @endif
                    </form>
                </div>
            @endif

            {{-- The accordion. Native <details>, so it opens without JavaScript
                 and is keyboard-operable for free. --}}
            <div @class(['lg:col-span-2' => ! $showForm])>
                <h2 class="font-display text-xl italic text-hoor-navy-700">
                    {{ __('store.contact_page.faq.title') }}
                </h2>

                @if ($faqs->isEmpty())
                    <p class="mt-5 text-sm text-hoor-muted">{{ __('store.contact_page.faq.empty') }}</p>
                @else
                    <div class="mt-5 space-y-2">
                        @foreach ($faqs as $faq)
                            <details class="group card overflow-hidden">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3
                                                px-5 py-3.5 text-sm text-hoor-navy-700 transition
                                                hover:bg-hoor-cream-100/60">
                                    <span>{{ $faq->question }}</span>

                                    <span class="shrink-0 text-hoor-muted transition group-open:rotate-45"
                                          aria-hidden="true">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                                        </svg>
                                    </span>
                                </summary>

                                <p class="border-t border-hoor-cream-300 px-5 py-4 text-sm leading-relaxed text-hoor-muted">
                                    {{ $faq->answer }}
                                </p>
                            </details>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ── 4. Order help band ──────────────────────────────────────────── --}}
    <section class="hoor-container pb-12 lg:pb-16">
        <div class="grid overflow-hidden rounded-sm bg-hoor-beige-100 sm:grid-cols-[0.55fr_1.45fr] lg:grid-cols-[0.4fr_1.6fr]">

            <div class="relative min-h-[9rem]">
                <img src="{{ $photo('hoor-9.png') }}" alt=""
                     width="1123" height="1404" loading="lazy" decoding="async"
                     class="absolute inset-0 h-full w-full object-cover">
            </div>

            <div class="grid gap-6 p-6 sm:p-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                <div>
                    <h2 class="font-display text-xl italic text-hoor-navy-700">
                        {{ __('store.contact_page.order_help.title') }}
                    </h2>

                    <p class="mt-2 max-w-sm text-xs leading-relaxed text-hoor-navy-600/85">
                        {{ __('store.contact_page.order_help.body') }}
                    </p>

                    <x-ui.button variant="primary" size="sm" class="mt-4"
                                 :href="route('store.tracking.index')">
                        {{ __('store.contact_page.order_help.cta') }}
                    </x-ui.button>
                </div>

                <ul class="grid grid-cols-3 gap-4">
                    @foreach ([
                        'returns' => 'refresh',
                        'payment' => 'wallet',
                        'support' => 'chat',
                    ] as $key => $icon)
                        <li class="text-center">
                            <x-store.about-icon :name="$icon" class="mx-auto h-6 w-6 text-hoor-navy-500" />

                            <p class="mt-2 text-xs font-medium leading-snug text-hoor-navy-700">
                                {{ __('store.contact_page.order_help.points.'.$key.'.title') }}
                            </p>
                            <p class="text-xs leading-snug text-hoor-muted">
                                {{ __('store.contact_page.order_help.points.'.$key.'.body') }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- ── 5. Our location ─────────────────────────────────────────────── --}}
    <section class="hoor-container pb-14 lg:pb-20">
        <h2 class="font-display text-xl italic text-hoor-navy-700">
            {{ __('store.contact_page.location.title') }}
        </h2>

        <span class="mt-3 block h-px w-16 bg-hoor-gold-500"></span>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">

            {{-- The storefront. --}}
            <img src="{{ $photo('hoor-7.png') }}" alt=""
                 width="1123" height="1404" loading="lazy" decoding="async"
                 class="aspect-[4/3] w-full rounded-sm object-cover">

            {{--
                The map.

                A brand-styled panel that opens the address in Maps rather than
                an embedded third-party frame: no external script loads on the
                storefront, and the destination is the admin's own link.
            --}}
            <a @if ($mapUrl) href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer" @endif
               class="group relative flex aspect-[4/3] items-center justify-center overflow-hidden
                      rounded-sm bg-hoor-cream-100 ring-1 ring-hoor-cream-300 transition
                      hover:ring-hoor-gold-500">

                {{-- Suggested streets, drawn rather than fetched. --}}
                <svg class="absolute inset-0 h-full w-full text-hoor-cream-300" fill="none"
                     stroke="currentColor" stroke-width="6" viewBox="0 0 400 300" aria-hidden="true">
                    <path d="M-20 90h440M-20 210h440M120 -20v340M280 -20v340" />
                    <path d="M-20 150h440" stroke-width="10" />
                </svg>

                <span class="relative flex flex-col items-center">
                    <svg class="h-10 w-10 text-hoor-navy-500 transition group-hover:scale-110"
                         fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 2a7 7 0 00-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 00-7-7zm0 9.5A2.5 2.5 0 1112 6.5a2.5 2.5 0 010 5z" />
                    </svg>

                    @if ($mapUrl)
                        <span class="mt-2 rounded-sm bg-white/90 px-3 py-1 text-xs text-hoor-navy-700 shadow-card">
                            {{ __('store.contact_page.location.directions') }}
                        </span>
                    @endif
                </span>
            </a>

            {{-- Store information. --}}
            <div class="card p-6">
                <h3 class="font-display text-lg text-hoor-navy-700">
                    {{ __('store.contact_page.location.store') }}
                </h3>

                <dl class="mt-4 space-y-4 text-sm">
                    @if ($contact->address())
                        <div class="flex gap-3">
                            <x-store.about-icon name="pin" class="mt-0.5 h-5 w-5 shrink-0 text-hoor-navy-500" />
                            <dd class="text-hoor-navy-700">{{ $contact->address() }}</dd>
                        </div>
                    @endif

                    @if ($contact->phone())
                        <div class="flex gap-3">
                            <x-store.about-icon name="phone" class="mt-0.5 h-5 w-5 shrink-0 text-hoor-navy-500" />
                            <dd dir="ltr">
                                <a href="{{ $contact->phoneLink() }}"
                                   class="text-hoor-navy-700 transition hover:text-hoor-gold-600">
                                    {{ $contact->phone() }}
                                </a>
                            </dd>
                        </div>
                    @endif

                    @if ($contact->email())
                        <div class="flex gap-3">
                            <x-store.about-icon name="mail" class="mt-0.5 h-5 w-5 shrink-0 text-hoor-navy-500" />
                            <dd dir="ltr">
                                <a href="mailto:{{ $contact->email() }}"
                                   class="text-hoor-navy-700 transition hover:text-hoor-gold-600">
                                    {{ $contact->email() }}
                                </a>
                            </dd>
                        </div>
                    @endif

                    @if ($contact->hours())
                        <div class="flex gap-3">
                            <x-store.about-icon name="clock" class="mt-0.5 h-5 w-5 shrink-0 text-hoor-navy-500" />
                            <dd class="text-hoor-navy-700">
                                {{ $contact->hours() }}

                                @if ($alt = $settings->translated('contact.hours_alt'))
                                    <span class="block text-hoor-muted">{{ $alt }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>

                @if ($socials !== [])
                    <div class="mt-5 flex gap-2 border-t border-hoor-cream-300 pt-5">
                        @foreach ($socials as $network => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                               class="rounded-full border border-hoor-cream-300 px-3 py-1.5 text-xs
                                      text-hoor-navy-600 transition hover:border-hoor-gold-500 hover:text-hoor-gold-600">
                                {{ __('settings.networks.'.$network) }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.store>
