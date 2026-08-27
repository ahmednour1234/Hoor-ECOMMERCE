{{--
    Shop / collection listing.

    Filters render twice: as a sidebar from lg up, and inside an off-canvas
    drawer below it. Both include the same partial, so there is one definition
    of every control.
--}}
<x-layouts.store>
    @section('title', __('store.shop.title').' — '.__('common.brand'))
    @section('description', __('store.shop.lead'))

    <div x-data="{ filtersOpen: false, ...shopPage() }"
         @click="$event.target.closest('a[data-filter-link]') && navigate({
             currentTarget: $event.target.closest('a[data-filter-link]'),
             preventDefault: () => $event.preventDefault(),
             metaKey: $event.metaKey, ctrlKey: $event.ctrlKey,
             shiftKey: $event.shiftKey, button: $event.button,
         })"
         class="hoor-container py-10 lg:py-14"
         :class="loading && 'cursor-progress'">

        {{-- Heading --}}
        <div class="mb-8">
            <p class="eyebrow">{{ __('nav.shop') }}</p>
            <h1 class="mt-2 section-title">{{ __('store.shop.title') }}</h1>
            <p class="mt-2 text-sm text-hoor-muted">{{ __('store.shop.lead') }}</p>
        </div>

        <div class="lg:flex lg:items-start lg:gap-10">

            {{-- Sidebar filters --}}
            <aside class="hidden w-64 shrink-0 lg:block" id="shop-filters-desktop">
                @include('store.shop._filters', [
                    'filter'   => $filter,
                    'facets'   => $facets,
                    'idPrefix' => 'desktop',
                ])
            </aside>

            <div class="min-w-0 flex-1">

                {{-- Toolbar --}}
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3 border-b
                            border-hoor-cream-300 pb-4" id="shop-toolbar">

                    <p class="text-sm text-hoor-muted">
                        {{ trans_choice('store.shop.results', $products->total(), ['count' => $products->total()]) }}
                    </p>

                    <div class="flex items-center gap-2">
                        {{-- Drawer trigger, below lg only. --}}
                        <button type="button"
                                @click="filtersOpen = true"
                                class="btn-outline btn-sm lg:hidden"
                                :aria-expanded="filtersOpen">
                            {{ __('store.shop.filters') }}
                            @if ($filter->activeCount() > 0)
                                <span class="ms-1 flex h-5 min-w-5 items-center justify-center rounded-full
                                             bg-hoor-navy-500 px-1 text-[0.65rem] text-hoor-cream-50">
                                    {{ $filter->activeCount() }}
                                </span>
                            @endif
                        </button>

                        {{-- Sort: a GET form so the choice lands in the URL and
                             the resulting view stays shareable. --}}
                        <form method="GET" action="{{ route('store.shop') }}"
                              @submit="submit($event)"
                              class="flex items-center gap-2">
                            @foreach ($filter->toQuery() as $key => $value)
                                @unless ($key === 'sort')
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endunless
                            @endforeach

                            <label for="sort" class="sr-only">{{ __('store.shop.sort_by') }}</label>
                            <select name="sort" id="sort" class="form-select py-2 text-sm"
                                    @change="$el.form.requestSubmit()">
                                @foreach ($sorts as $sort)
                                    <option value="{{ $sort }}" @selected($filter->sort === $sort)>
                                        {{ __("store.shop.sorts.{$sort}") }}
                                    </option>
                                @endforeach
                            </select>

                            {{-- Submit for browsers without JS; the select's
                                 onchange covers everyone else. --}}
                            <noscript>
                                <button type="submit" class="btn-secondary btn-sm">
                                    {{ __('common.actions.confirm') }}
                                </button>
                            </noscript>
                        </form>
                    </div>
                </div>

                {{-- Grid --}}
                <div id="shop-results" :class="loading && 'opacity-60 transition-opacity'">
                @if ($products->isEmpty())
                    <x-admin.empty-state
                        :title="__('store.shop.none')"
                        :message="__('store.shop.none_hint')">
                        <x-slot:action>
                            <x-ui.button variant="outline" :href="route('store.shop')">
                                {{ __('store.shop.clear_all') }}
                            </x-ui.button>
                        </x-slot:action>
                    </x-admin.empty-state>
                @else
                    <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:gap-x-6 lg:grid-cols-3">
                        @foreach ($products as $index => $product)
                            <x-store.product-card :product="$product" :eager="$index < 3" />
                        @endforeach
                    </div>

                    <div class="mt-10">{{ $products->links() }}</div>
                @endif
                </div>
            </div>
        </div>

        {{-- ===================================== Off-canvas filter drawer --}}
        <div x-show="filtersOpen" x-cloak class="fixed inset-0 z-50 lg:hidden"
             role="dialog" aria-modal="true" aria-label="{{ __('store.shop.filters') }}"
             @keydown.escape.window="filtersOpen = false">

            <div class="absolute inset-0 bg-hoor-navy-900/40" @click="filtersOpen = false"></div>

            <div class="absolute inset-y-0 end-0 flex w-[22rem] max-w-[88%] flex-col bg-hoor-cream-50 shadow-soft"
                 x-transition:enter="transition ease-hoor duration-300"
                 x-transition:enter-start="translate-x-full rtl:-translate-x-full"
                 x-transition:enter-end="translate-x-0">

                <div class="flex h-16 shrink-0 items-center justify-between border-b border-hoor-cream-300 px-5">
                    <h2 class="font-display text-lg text-hoor-navy-700">{{ __('store.shop.filters') }}</h2>

                    <button type="button" class="rounded-sm p-2 text-hoor-navy-600"
                            @click="filtersOpen = false" aria-label="{{ __('store.shop.close') }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"
                             viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-5" id="shop-filters-drawer">
                    @include('store.shop._filters', [
                        'filter'   => $filter,
                        'facets'   => $facets,
                        'idPrefix' => 'drawer',
                    ])
                </div>

                <div class="shrink-0 border-t border-hoor-cream-300 p-4">
                    <button type="button" @click="filtersOpen = false" class="btn-primary w-full">
                        {{ __('store.shop.apply', ['count' => $products->total()]) }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('store.partials.shop-script')
</x-layouts.store>
