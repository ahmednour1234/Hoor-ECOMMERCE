{{--
    Filter panel.

    Rendered twice — as a sidebar on desktop and inside the mobile drawer — so
    every control exists once here. Facets are plain links carrying the next
    query string, which means filtering works without JavaScript, the back
    button behaves, and any filtered view can be shared as a URL.
--}}
@props(['filter', 'facets', 'idPrefix' => 'filter'])

@php
    $shop = fn (\App\Support\ProductFilter $next): string => route('store.shop', $next->toQuery());
@endphp

<div class="space-y-7">

    {{-- Active filters --}}
    @if ($filter->isActive())
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-xs font-semibold uppercase tracking-editorial text-hoor-muted">
                    {{ __('store.shop.active') }}
                </h3>

                <a data-filter-link href="{{ route('store.shop', $filter->sort === \App\Support\ProductFilter::DEFAULT_SORT ? [] : ['sort' => $filter->sort]) }}"
                   class="text-xs font-medium text-hoor-gold-600 hover:text-hoor-gold-700">
                    {{ __('store.shop.clear_all') }}
                </a>
            </div>

            <div class="flex flex-wrap gap-1.5">
                @foreach ($filter->categories as $slug)
                    @php($label = $facets['categories']->firstWhere('slug', $slug)?->name ?? $slug)
                    <a data-filter-link href="{{ $shop($filter->toggle('category', $slug)) }}" class="filter-chip">
                        {{ $label }} <span aria-hidden="true">&times;</span>
                    </a>
                @endforeach

                @foreach ($filter->sizes as $code)
                    <a data-filter-link href="{{ $shop($filter->toggle('size', $code)) }}" class="filter-chip">
                        {{ strtoupper($code) }} <span aria-hidden="true">&times;</span>
                    </a>
                @endforeach

                @foreach ($filter->colors as $slug)
                    @php($label = $facets['colors']->firstWhere('slug', $slug)?->name ?? $slug)
                    <a data-filter-link href="{{ $shop($filter->toggle('color', $slug)) }}" class="filter-chip">
                        {{ $label }} <span aria-hidden="true">&times;</span>
                    </a>
                @endforeach

                @if ($filter->newArrivals)
                    <a data-filter-link href="{{ $shop($filter->toggle('new', '1')) }}" class="filter-chip">
                        {{ __('store.shop.facets.new_arrivals') }} <span aria-hidden="true">&times;</span>
                    </a>
                @endif

                @if ($filter->onSale)
                    <a data-filter-link href="{{ $shop($filter->toggle('sale', '1')) }}" class="filter-chip">
                        {{ __('store.shop.facets.on_sale') }} <span aria-hidden="true">&times;</span>
                    </a>
                @endif

                @if ($filter->inStockOnly)
                    <a data-filter-link href="{{ $shop($filter->toggle('in_stock', '1')) }}" class="filter-chip">
                        {{ __('store.shop.facets.in_stock') }} <span aria-hidden="true">&times;</span>
                    </a>
                @endif
            </div>
        </div>
    @endif

    {{-- Category --}}
    @if ($facets['categories']->isNotEmpty())
        <fieldset>
            <legend class="mb-3 text-xs font-semibold uppercase tracking-editorial text-hoor-muted">
                {{ __('store.shop.facets.category') }}
            </legend>

            <ul class="space-y-1">
                @foreach ($facets['categories'] as $category)
                    <li>
                        <a data-filter-link href="{{ $shop($filter->toggle('category', $category->slug)) }}"
                           @class([
                               'flex items-center justify-between rounded-sm px-2 py-1.5 text-sm transition',
                               'bg-hoor-navy-50 font-medium text-hoor-navy-700' => $filter->hasCategory($category->slug),
                               'text-hoor-navy-600 hover:bg-hoor-cream-100' => ! $filter->hasCategory($category->slug),
                           ])
                           @if ($filter->hasCategory($category->slug)) aria-current="true" @endif>
                            <span class="flex items-center gap-2">
                                <span @class([
                                    'flex h-4 w-4 shrink-0 items-center justify-center rounded-sm border',
                                    'border-hoor-navy-500 bg-hoor-navy-500 text-white' => $filter->hasCategory($category->slug),
                                    'border-hoor-cream-400' => ! $filter->hasCategory($category->slug),
                                ])>
                                    @if ($filter->hasCategory($category->slug))
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3"
                                             viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                </span>
                                {{ $category->name }}
                            </span>

                            <span class="text-xs text-hoor-muted">{{ $category->products_count }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </fieldset>
    @endif

    {{-- Size --}}
    @if ($facets['sizes']->isNotEmpty())
        <fieldset>
            <legend class="mb-3 text-xs font-semibold uppercase tracking-editorial text-hoor-muted">
                {{ __('store.shop.facets.size') }}
            </legend>

            <div class="flex flex-wrap gap-2">
                @foreach ($facets['sizes'] as $size)
                    <a data-filter-link href="{{ $shop($filter->toggle('size', $size->code)) }}"
                       @class([
                           'flex h-10 min-w-10 items-center justify-center rounded-sm border px-2.5 text-sm transition',
                           'border-hoor-navy-500 bg-hoor-navy-500 text-hoor-cream-50' => $filter->hasSize($size->code),
                           'border-hoor-cream-300 text-hoor-navy-600 hover:border-hoor-navy-400' => ! $filter->hasSize($size->code),
                       ])
                       dir="ltr"
                       @if ($filter->hasSize($size->code)) aria-current="true" @endif>
                        {{ $size->name }}
                    </a>
                @endforeach
            </div>
        </fieldset>
    @endif

    {{-- Colour --}}
    @if ($facets['colors']->isNotEmpty())
        <fieldset>
            <legend class="mb-3 text-xs font-semibold uppercase tracking-editorial text-hoor-muted">
                {{ __('store.shop.facets.color') }}
            </legend>

            <div class="flex flex-wrap gap-2.5">
                @foreach ($facets['colors'] as $color)
                    <a data-filter-link href="{{ $shop($filter->toggle('color', $color->slug)) }}"
                       class="group/swatch relative flex h-9 w-9 items-center justify-center rounded-full
                              border-2 transition {{ $filter->hasColor($color->slug)
                                  ? 'border-hoor-navy-500'
                                  : 'border-transparent hover:border-hoor-cream-400' }}"
                       title="{{ $color->name }}"
                       @if ($filter->hasColor($color->slug)) aria-current="true" @endif>
                        <span class="h-7 w-7 rounded-full border border-hoor-cream-300"
                              style="background-color: {{ $color->hex }}"></span>

                        @if ($filter->hasColor($color->slug))
                            {{-- Tick colour follows the swatch's luminance so it
                                 stays visible on both pale and dark colours. --}}
                            <svg class="absolute h-4 w-4 {{ $color->isLight() ? 'text-hoor-navy-700' : 'text-white' }}"
                                 fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        @endif

                        <span class="sr-only">{{ $color->name }}</span>
                    </a>
                @endforeach
            </div>
        </fieldset>
    @endif

    {{-- Price --}}
    <form method="GET" action="{{ route('store.shop') }}" @submit="submit($event)">
        {{-- Carry the other filters so submitting price does not reset them. --}}
        @foreach ($filter->toQuery() as $key => $value)
            @unless (in_array($key, ['min_price', 'max_price'], true))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endunless
        @endforeach

        <fieldset>
            <legend class="mb-3 text-xs font-semibold uppercase tracking-editorial text-hoor-muted">
                {{ __('store.shop.facets.price') }} ({{ __('common.currency') }})
            </legend>

            <div class="flex items-center gap-2">
                <label for="{{ $idPrefix }}-min" class="sr-only">{{ __('store.shop.facets.min') }}</label>
                <input type="number" id="{{ $idPrefix }}-min" name="min_price" min="0" step="1"
                       inputmode="numeric" dir="ltr"
                       value="{{ $filter->minPrice !== null ? (int) \App\Casts\Money::toMajor($filter->minPrice) : '' }}"
                       placeholder="{{ (int) \App\Casts\Money::toMajor($facets['price']['min']) }}"
                       class="form-input py-2 text-sm">

                <span class="text-hoor-muted" aria-hidden="true">&ndash;</span>

                <label for="{{ $idPrefix }}-max" class="sr-only">{{ __('store.shop.facets.max') }}</label>
                <input type="number" id="{{ $idPrefix }}-max" name="max_price" min="0" step="1"
                       inputmode="numeric" dir="ltr"
                       value="{{ $filter->maxPrice !== null ? (int) \App\Casts\Money::toMajor($filter->maxPrice) : '' }}"
                       placeholder="{{ (int) \App\Casts\Money::toMajor($facets['price']['max']) }}"
                       class="form-input py-2 text-sm">

                <button type="submit" class="btn-secondary btn-sm shrink-0">
                    {{ __('common.actions.confirm') }}
                </button>
            </div>
        </fieldset>
    </form>

    {{-- Availability and flags --}}
    <fieldset>
        <legend class="mb-3 text-xs font-semibold uppercase tracking-editorial text-hoor-muted">
            {{ __('store.shop.facets.availability') }}
        </legend>

        <ul class="space-y-1">
            @foreach ([
                'new'      => ['label' => __('store.shop.facets.new_arrivals'), 'on' => $filter->newArrivals],
                'sale'     => ['label' => __('store.shop.facets.on_sale'),      'on' => $filter->onSale],
                'in_stock' => ['label' => __('store.shop.facets.in_stock'),     'on' => $filter->inStockOnly],
            ] as $key => $toggleItem)
                <li>
                    <a data-filter-link href="{{ $shop($filter->toggle($key, '1')) }}"
                       @class([
                           'flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition',
                           'bg-hoor-navy-50 font-medium text-hoor-navy-700' => $toggleItem['on'],
                           'text-hoor-navy-600 hover:bg-hoor-cream-100' => ! $toggleItem['on'],
                       ])
                       @if ($toggleItem['on']) aria-current="true" @endif>
                        <span @class([
                            'flex h-4 w-4 shrink-0 items-center justify-center rounded-sm border',
                            'border-hoor-navy-500 bg-hoor-navy-500 text-white' => $toggleItem['on'],
                            'border-hoor-cream-400' => ! $toggleItem['on'],
                        ])>
                            @if ($toggleItem['on'])
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3"
                                     viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            @endif
                        </span>
                        {{ $toggleItem['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </fieldset>
</div>
