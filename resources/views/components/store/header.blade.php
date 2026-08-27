{{--
    Storefront header.

    Two bars: a navy utility strip, then a white navigation bar carrying the
    circular brand mark that overhangs the hero below it.

    Navigation is built from the active category tree rather than a hardcoded
    list, and only branches with published products are listed so no menu entry
    is a dead end.
--}}
@php
    $menuCategories = \Illuminate\Support\Facades\Cache::remember(
        'store.menu.'.app()->getLocale(),
        now()->addMinutes(10),
        function () {
            $hasProducts = fn ($query) => $query->whereHas(
                'products',
                fn ($products) => $products->published(),
            );

            return \App\Models\Category::query()
                ->active()
                ->roots()
                ->where(fn ($query) => $query
                    ->whereHas('products', fn ($products) => $products->published())
                    ->orWhereHas('children', fn ($children) => $hasProducts($children->active())))
                ->with([
                    'children' => fn ($query) => $query
                        ->active()
                        ->whereHas('products', fn ($products) => $products->published())
                        ->ordered(),
                ])
                ->ordered()
                ->get();
        },
    );

    $shopUrl = \Illuminate\Support\Facades\Route::has('store.shop')
        ? route('store.shop')
        : route('store.home');

    /*
     * Categories link into the filtered shop, not to a page of their own.
     *
     * The previous guard checked for a `store.categories.show` route that was
     * never defined, so every category in the menu silently pointed at the
     * homepage.
     */
    $categoryUrl = fn ($category) => route('store.shop', ['category' => $category->slug]);

    // Read from the session rather than hydrating the cart: the badge renders
    // on every page and needs no prices or stock.
    $cartCount = app(\App\Services\CartService::class)->count();
@endphp

<header x-data="{ mobileOpen: false, openMenu: null, searchOpen: false }" class="relative z-40">

    {{-- ================================================ Utility strip --}}
    <div class="bg-hoor-navy-700 text-hoor-cream-50">
        <div class="hoor-container flex h-10 items-center justify-between gap-4 text-xs">

            <p class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-hoor-cream-50/80" fill="none" stroke="currentColor"
                     stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.25 6.75h9.75v9h-9.75zM12 10.5h3.75l2.25 2.625v2.625H12z" />
                    <circle cx="6.75" cy="17.25" r="1.5" /><circle cx="15.75" cy="17.25" r="1.5" />
                </svg>
                <span>{{ __('store.announcement.free_shipping') }}</span>
            </p>

            <div class="flex items-center gap-3 sm:gap-4">
                <a href="{{ route('store.tracking.index') }}"
                   class="hidden transition hover:text-hoor-gold-500 sm:inline">
                    {{ __('nav.track') }}
                </a>

                <span class="hidden text-hoor-cream-50/30 sm:inline" aria-hidden="true">|</span>

                @auth
                    <a href="{{ route('store.account.index') }}" class="transition hover:text-hoor-gold-500">
                        {{ __('nav.account') }}
                    </a>
                @else
                    <span class="hidden sm:inline">
                        <a href="{{ route('login') }}" class="transition hover:text-hoor-gold-500">
                            {{ __('nav.login') }}
                        </a>
                        <span class="text-hoor-cream-50/30" aria-hidden="true">/</span>
                        <a href="{{ route('register') }}" class="transition hover:text-hoor-gold-500">
                            {{ __('nav.register') }}
                        </a>
                    </span>
                @endauth

                <span class="hidden text-hoor-cream-50/30 sm:inline" aria-hidden="true">|</span>

                {{-- Language switch: shows the language you would switch *to*. --}}
                @foreach (\App\Support\Locale::alternates() as $code => $meta)
                    <a href="{{ route('locale.switch', ['locale' => $code]) }}"
                       class="inline-flex items-center gap-1.5 transition hover:text-hoor-gold-500"
                       hreflang="{{ $code }}" lang="{{ $code }}" rel="alternate">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.5"
                             viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M3 12h18M12 3c2.5 2.7 2.5 15.3 0 18M12 3c-2.5 2.7-2.5 15.3 0 18" />
                        </svg>
                        {{ $meta['native'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ================================================== Navigation --}}
    <div class="relative border-b border-hoor-cream-300 bg-white">
        <div class="hoor-container flex h-16 items-center justify-between gap-4 lg:h-20">

            {{-- Left: search (desktop) / drawer toggle (mobile) --}}
            <div class="flex flex-1 items-center gap-2">
                <button type="button"
                        class="-ms-2 rounded-sm p-2 text-hoor-navy-600 lg:hidden"
                        @click="mobileOpen = true"
                        :aria-expanded="mobileOpen"
                        aria-label="{{ __('nav.menu') }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6"
                         viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>

                <form action="{{ $shopUrl }}" method="GET"
                      class="hidden items-center gap-2 lg:flex" role="search">
                    <label for="site-search" class="sr-only">{{ __('nav.search_products') }}</label>

                    <svg class="h-4 w-4 shrink-0 text-hoor-muted" fill="none" stroke="currentColor"
                         stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" />
                        <path stroke-linecap="round" d="M20 20l-3.5-3.5" />
                    </svg>

                    <input type="search" id="site-search" name="search"
                           placeholder="{{ __('nav.search_products') }}"
                           class="w-44 border-0 bg-transparent p-0 text-sm text-hoor-navy-700
                                  placeholder:text-hoor-muted focus:ring-0 xl:w-52">
                </form>
            </div>

            {{-- Centre: primary navigation. --}}
            <nav class="hidden items-center gap-7 lg:flex" aria-label="{{ __('nav.menu') }}">
                <a href="{{ route('store.home') }}"
                   @class([
                       'relative py-1 text-sm font-medium transition hover:text-hoor-navy-700',
                       'text-hoor-navy-700 after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5 after:bg-hoor-navy-500'
                           => request()->routeIs('store.home'),
                       'text-hoor-navy-600' => ! request()->routeIs('store.home'),
                   ])>
                    {{ __('nav.home') }}
                </a>

                <a href="{{ \Illuminate\Support\Facades\Route::has('store.pages.about') ? route('store.pages.about') : route('store.home') }}"
                   class="py-1 text-sm font-medium text-hoor-navy-600 transition hover:text-hoor-navy-700">
                    {{ __('nav.about') }}
                </a>

                <a href="#new-arrivals"
                   class="py-1 text-sm font-medium text-hoor-navy-600 transition hover:text-hoor-navy-700">
                    {{ __('nav.new_in') }}
                </a>

                {{-- Clears the centred brand mark that overhangs below. --}}
                <span class="w-44 shrink-0 lg:w-56" aria-hidden="true"></span>

                {{-- Collection: every shoppable category, grouped. --}}
                @if ($menuCategories->isNotEmpty())
                    <div class="relative"
                         @mouseenter="openMenu = 'collection'"
                         @mouseleave="openMenu = null">

                        <a href="{{ $shopUrl }}"
                           class="flex items-center gap-1 py-1 text-sm font-medium text-hoor-navy-600
                                  transition hover:text-hoor-navy-700"
                           @focus="openMenu = 'collection'"
                           :aria-expanded="openMenu === 'collection'">
                            {{ __('nav.collection') }}
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                 viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" d="M6 9l6 6 6-6" />
                            </svg>
                        </a>

                        <div x-show="openMenu === 'collection'" x-cloak
                             x-transition.origin.top.duration.200ms
                             @keydown.escape="openMenu = null"
                             class="absolute start-1/2 top-full z-40 w-60 -translate-x-1/2 pt-4 rtl:translate-x-1/2">
                            <div class="overflow-hidden rounded-md border border-hoor-cream-300
                                        bg-white py-2 shadow-soft">
                                @foreach ($menuCategories as $category)
                                    <a href="{{ $categoryUrl($category) }}"
                                       class="block px-4 py-2.5 text-sm font-medium text-hoor-navy-700
                                              transition hover:bg-hoor-cream-100 hover:text-hoor-gold-600">
                                        {{ $category->name }}
                                    </a>

                                    @foreach ($category->children as $child)
                                        <a href="{{ $categoryUrl($child) }}"
                                           class="block px-4 py-2 ps-7 text-sm text-hoor-muted
                                                  transition hover:bg-hoor-cream-100 hover:text-hoor-gold-600">
                                            {{ $child->name }}
                                        </a>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <a href="{{ \Illuminate\Support\Facades\Route::has('store.pages.contact') ? route('store.pages.contact') : route('store.home') }}"
                   class="py-1 text-sm font-medium text-hoor-navy-600 transition hover:text-hoor-navy-700">
                    {{ __('nav.contact') }}
                </a>
            </nav>

            {{-- Right: account utilities --}}
            <div class="flex flex-1 items-center justify-end gap-1 sm:gap-2">
                <a href="{{ auth()->check() ? route('store.account.wishlist.index') : route('login') }}"
                   class="rounded-full p-2 text-hoor-navy-600 transition hover:bg-hoor-cream-100"
                   aria-label="{{ __('nav.wishlist') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6"
                         viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 20.25s-7.5-4.5-7.5-9.75a4.125 4.125 0 017.5-2.4 4.125 4.125 0 017.5 2.4c0 5.25-7.5 9.75-7.5 9.75z" />
                    </svg>
                </a>

                <a href="{{ auth()->check() ? route('store.account.index') : route('login') }}"
                   class="rounded-full p-2 text-hoor-navy-600 transition hover:bg-hoor-cream-100"
                   aria-label="{{ __('nav.account') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6"
                         viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0" />
                    </svg>
                </a>

                <a href="{{ route('store.cart.index') }}"
                   class="relative rounded-full p-2 text-hoor-navy-600 transition hover:bg-hoor-cream-100"
                   aria-label="{{ __('nav.cart') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6"
                         viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6.75 7.5V6a5.25 5.25 0 1110.5 0v1.5M4.5 7.5h15l-1.05 12a1.5 1.5 0 01-1.5 1.35H7.05a1.5 1.5 0 01-1.5-1.35z" />
                    </svg>

                    {{-- Server-rendered so it is correct on first paint, then
                         driven by the shared store as the cart changes. --}}
                    <span x-show="$store.cart.count > 0"
                          x-text="$store.cart.count"
                          @if ($cartCount === 0) x-cloak @endif
                          class="absolute -end-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center
                                 rounded-full bg-hoor-gold-500 px-1 text-[0.6rem] font-semibold
                                 text-hoor-navy-700">{{ $cartCount }}</span>
                </a>
            </div>
        </div>

        {{-- Brand wordmark, centred and straddling the header edge so it reads
             as part of the bar rather than as a stray mark floating on the
             photograph. No panel behind it: the navy type sits directly on the
             pale hero backdrop. --}}
        <a href="{{ route('store.home') }}"
           class="absolute start-1/2 z-20 hidden -translate-x-1/2 -translate-y-1/2 items-center
                  justify-center transition lg:flex rtl:translate-x-1/2"
           style="top: 100%;"
           aria-label="{{ __('common.brand') }}">
            <img src="{{ asset('images/brand/hoor-primary-blue.svg') }}"
                 alt="" class="w-48 xl:w-56">
        </a>
    </div>

    {{-- ================================================ Mobile drawer --}}
    <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-hoor-navy-900/40" @click="mobileOpen = false"></div>

        <div class="absolute inset-y-0 start-0 flex w-80 max-w-[85%] flex-col bg-white shadow-soft"
             x-transition:enter="transition ease-hoor duration-300"
             x-transition:enter-start="-translate-x-full rtl:translate-x-full"
             x-transition:enter-end="translate-x-0">

            <div class="flex h-16 shrink-0 items-center justify-between border-b border-hoor-cream-300 px-5">
                <img src="{{ asset('images/brand/hoor-horizontal-blue.svg') }}"
                     alt="{{ __('common.brand') }}" class="h-8 w-auto">

                <button type="button" class="rounded-sm p-2 text-hoor-navy-600"
                        @click="mobileOpen = false" aria-label="{{ __('nav.close') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"
                         viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <div class="border-b border-hoor-cream-300 p-4">
                <form action="{{ $shopUrl }}" method="GET" role="search"
                      class="flex items-center gap-2 rounded-sm border border-hoor-cream-300 px-3 py-2">
                    <svg class="h-4 w-4 shrink-0 text-hoor-muted" fill="none" stroke="currentColor"
                         stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" />
                        <path stroke-linecap="round" d="M20 20l-3.5-3.5" />
                    </svg>
                    <label for="drawer-search" class="sr-only">{{ __('nav.search_products') }}</label>
                    <input type="search" id="drawer-search" name="search"
                           placeholder="{{ __('nav.search_products') }}"
                           class="w-full border-0 bg-transparent p-0 text-sm focus:ring-0">
                </form>
            </div>

            <nav class="flex-1 overflow-y-auto p-4" aria-label="{{ __('nav.menu') }}">
                <a href="{{ route('store.home') }}"
                   class="block rounded-sm px-3 py-2.5 text-sm font-medium text-hoor-navy-700 hover:bg-hoor-cream-100">
                    {{ __('nav.home') }}
                </a>

                <a href="{{ \Illuminate\Support\Facades\Route::has('store.pages.about') ? route('store.pages.about') : route('store.home') }}"
                   class="block rounded-sm px-3 py-2.5 text-sm font-medium text-hoor-navy-700 hover:bg-hoor-cream-100">
                    {{ __('nav.about') }}
                </a>

                @foreach ($menuCategories as $category)
                    <a href="{{ $categoryUrl($category) }}"
                       class="block rounded-sm px-3 py-2.5 text-sm font-medium text-hoor-navy-700 hover:bg-hoor-cream-100">
                        {{ $category->name }}
                    </a>

                    @foreach ($category->children as $child)
                        <a href="{{ $categoryUrl($child) }}"
                           class="block rounded-sm px-3 py-2 ps-7 text-sm text-hoor-muted hover:bg-hoor-cream-100">
                            {{ $child->name }}
                        </a>
                    @endforeach
                @endforeach

                <a href="#new-arrivals" @click="mobileOpen = false"
                   class="block rounded-sm px-3 py-2.5 text-sm font-medium text-hoor-navy-700 hover:bg-hoor-cream-100">
                    {{ __('nav.new_in') }}
                </a>

                <a href="{{ \Illuminate\Support\Facades\Route::has('store.pages.contact') ? route('store.pages.contact') : route('store.home') }}"
                   class="block rounded-sm px-3 py-2.5 text-sm font-medium text-hoor-navy-700 hover:bg-hoor-cream-100">
                    {{ __('nav.contact') }}
                </a>

                @guest
                    <div class="mt-3 border-t border-hoor-cream-300 pt-3">
                        <a href="{{ route('login') }}"
                           class="block rounded-sm px-3 py-2.5 text-sm font-medium text-hoor-navy-700 hover:bg-hoor-cream-100">
                            {{ __('nav.login') }}
                        </a>
                        <a href="{{ route('register') }}"
                           class="block rounded-sm px-3 py-2.5 text-sm font-medium text-hoor-navy-700 hover:bg-hoor-cream-100">
                            {{ __('nav.register') }}
                        </a>
                    </div>
                @endguest
            </nav>

            <div class="shrink-0 border-t border-hoor-cream-300 p-4">
                <p class="mb-2 text-xs font-medium uppercase tracking-editorial text-hoor-muted">
                    {{ __('common.language') }}
                </p>
                <x-store.language-switcher />
            </div>
        </div>
    </div>
</header>
