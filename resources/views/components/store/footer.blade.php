{{--
    Contact details and social links come from $contact, which every view
    receives (see AppServiceProvider). Nothing here reads config directly, so
    the admin owns these values rather than the deployment.
--}}
@php
    $socials = $contact->socials();
@endphp

<footer class="mt-20 bg-hoor-navy-500 text-hoor-cream-50">
    <div class="hoor-container py-14">
        {{-- The brand takes two fifths and the three link columns share the
             rest: four equal columns gave a block with a wordmark, a paragraph
             and three pills the same width as one holding a single link. --}}
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-[1.6fr_1fr_1fr_1fr] lg:gap-12">

            {{-- Brand --}}
            <div>
                <img src="{{ asset('images/brand/hoor-horizontal-white.svg') }}"
                     alt="{{ __('common.brand') }}"
                     class="h-14 w-auto sm:h-16">

                <p class="mt-5 max-w-xs text-sm leading-relaxed text-hoor-cream-50/70">
                    {{ __('store.footer.about') }}
                </p>

                {{-- Only networks that have been filled in: a dead link is
                     worse than an absent one. --}}
                @if ($socials !== [])
                    <div class="mt-5 flex items-center gap-3">
                        @foreach ($socials as $network => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                               class="rounded-full border border-hoor-cream-50/25 px-3 py-1.5 text-xs
                                      text-hoor-cream-50/80 transition hover:border-hoor-gold-500 hover:text-hoor-gold-500">
                                {{ __('settings.networks.'.$network) }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Link columns. Each entry names its route; anything whose
                 module has not shipped yet stays plain text rather than
                 pointing at a URL that would 404. --}}
            @php
                /*
                 * Each entry is [route, query]. The query matters: without it
                 * "New in" and "Jeans" both landed on the same unfiltered shop
                 * as "Shop", so the column offered one destination three times.
                 */
                $columns = [
                    'shop' => [
                        'shop'   => ['store.shop', []],
                        'new_in' => ['store.shop', ['new' => 1]],
                        'jeans'  => ['store.shop', ['category' => 'jeans']],
                    ],
                    'help' => [
                        'track'   => ['store.tracking.index', []],
                        'contact' => ['store.pages.contact', []],
                        'returns' => ['store.account.returns.index', []],
                    ],
                    'company' => [
                        'about'   => ['store.pages.about', []],
                        'contact' => ['store.pages.contact', []],
                    ],
                ];
            @endphp

            @foreach ($columns as $column => $links)
                <div>
                    <h3 class="mb-4 font-sans text-xs font-semibold uppercase tracking-editorial text-hoor-gold-500">
                        {{ __("store.footer.{$column}") }}
                    </h3>
                    <ul class="space-y-2.5 text-sm">
                        @foreach ($links as $link => [$route, $query])
                            <li>
                                @if (\Illuminate\Support\Facades\Route::has($route))
                                    <a href="{{ route($route, $query) }}"
                                       class="text-hoor-cream-50/60 transition hover:text-hoor-gold-500">
                                        {{ __("nav.{$link}") }}
                                    </a>
                                @else
                                    {{-- A module that has not shipped stays plain
                                         text rather than pointing at a 404. --}}
                                    <span class="text-hoor-cream-50/60">{{ __("nav.{$link}") }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        {{-- Contact strip --}}
        <div class="mt-12 grid gap-4 border-t border-hoor-cream-50/15 pt-8 text-sm sm:grid-cols-2">
            <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-hoor-cream-50/70">
                @if ($contact->email())
                    <a href="mailto:{{ $contact->email() }}" dir="ltr"
                       class="transition hover:text-hoor-gold-500">{{ $contact->email() }}</a>
                @endif

                @if ($contact->email() && $contact->phone())
                    <span class="text-hoor-cream-50/30" aria-hidden="true">|</span>
                @endif

                @if ($contact->phone())
                    <a href="{{ $contact->phoneLink() }}" dir="ltr"
                       class="transition hover:text-hoor-gold-500">{{ $contact->phone() }}</a>
                @endif

                @if ($contact->whatsappLink())
                    <span class="text-hoor-cream-50/30" aria-hidden="true">|</span>
                    <a href="{{ $contact->whatsappLink() }}" target="_blank" rel="noopener noreferrer"
                       class="transition hover:text-hoor-gold-500">{{ __('settings.networks.whatsapp') }}</a>
                @endif
            </p>
            <p class="text-hoor-cream-50/70 sm:text-end">
                {{ __('store.footer.cod_note') }}
            </p>
        </div>
    </div>

    <div class="border-t border-hoor-cream-50/15">
        <div class="hoor-container flex flex-col items-center justify-between gap-2 py-5 text-xs text-hoor-cream-50/55 sm:flex-row">
            <p>&copy; {{ date('Y') }} {{ __('common.brand') }}. {{ __('store.footer.rights') }}</p>
            <x-store.language-switcher tone="light" />
        </div>
    </div>
</footer>
