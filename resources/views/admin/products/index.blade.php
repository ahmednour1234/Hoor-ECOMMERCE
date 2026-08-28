<x-layouts.admin>
    @section('title', __('catalog.products.title'))
    @section('page-title', __('catalog.products.title'))

    <x-admin.page-header
        :title="__('catalog.products.title')"
        :subtitle="__('catalog.products.subtitle')">
        <x-slot:actions>
            {{-- The export carries the filters currently on screen, so
                 "export" means what the page is showing rather than always
                 the whole catalogue. --}}
            <x-ui.button variant="outline"
                         :href="route('admin.products.export', request()->only('status', 'category_id'))">
                {{ __('import.export.products') }}
            </x-ui.button>

            <x-ui.button variant="outline" :href="route('admin.products.import.create')">
                {{ __('import.title') }}
            </x-ui.button>

            <x-ui.button variant="primary" :href="route('admin.products.create')">
                {{ __('catalog.products.create') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Catalog health at a glance --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.card :label="__('catalog.products.title')" :value="$statistics['total']" tone="navy" />
        <x-admin.card :label="__('catalog.status.published')" :value="$statistics['published']" tone="denim" />
        <x-admin.card :label="__('catalog.status.draft')" :value="$statistics['draft']" tone="navy" />
        <x-admin.card :label="__('admin.stats.low_stock')" :value="$statistics['low_stock']" tone="gold" />
    </div>

    {{-- Filters: GET so the current view is always shareable and bookmarkable. --}}
    <form method="GET" action="{{ route('admin.products.index') }}" class="card mb-6 p-4">
        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <input type="search"
                       name="search"
                       value="{{ $filters['search'] ?? '' }}"
                       placeholder="{{ __('catalog.products.search') }}"
                       class="form-input">
            </div>

            <select name="category" class="form-select">
                <option value="">{{ __('catalog.products.all_categories') }}</option>
                @foreach ($categories as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['category'] ?? null) == $id)>{{ $name }}</option>
                @endforeach
            </select>

            <select name="status" class="form-select">
                <option value="">{{ __('catalog.products.all_statuses') }}</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <div class="flex gap-2">
                <select name="stock" class="form-select">
                    <option value="">{{ __('catalog.products.all_stock') }}</option>
                    <option value="in"  @selected(($filters['stock'] ?? null) === 'in')>{{ __('catalog.stock.in_stock') }}</option>
                    <option value="low" @selected(($filters['stock'] ?? null) === 'low')>{{ __('catalog.stock.low_stock') }}</option>
                    <option value="out" @selected(($filters['stock'] ?? null) === 'out')>{{ __('catalog.stock.out_of_stock') }}</option>
                </select>

                <x-ui.button type="submit" variant="secondary" size="sm">
                    {{ __('common.actions.search') }}
                </x-ui.button>
            </div>
        </div>
    </form>

    @if ($products->isEmpty())
        <x-admin.empty-state
            :title="request()->hasAny(['search', 'category', 'status', 'stock'])
                ? __('catalog.products.no_matches')
                : __('catalog.products.none')"
            :message="__('catalog.products.none_hint')">
            <x-slot:action>
                <x-ui.button variant="primary" :href="route('admin.products.create')">
                    {{ __('catalog.products.create') }}
                </x-ui.button>
            </x-slot:action>
        </x-admin.empty-state>
    @else
        {{--
            Bulk status changes.

            The form wraps nothing.

            Wrapping the table put each row's delete form inside this one, and
            nested forms are invalid HTML: the browser discards the inner form
            and the submit falls through to the outer one — which sent a GET to
            a PATCH-only route.

            So the checkboxes join this form through the HTML5 `form`
            attribute instead. A field may belong to a form it does not sit
            inside, and the delete forms stay independent.
        --}}
        <form method="POST" action="{{ route('admin.products.bulk') }}" id="bulk-status">
            @csrf
        </form>

        <div x-data="{
                 selected: [],
                 get count() { return this.selected.length },
             }">

            {{-- Sticky, so a long list does not mean scrolling back up to act
                 on what was ticked at the bottom. --}}
            <div x-show="count > 0" x-cloak
                 x-transition:enter="transition ease-hoor duration-200"
                 x-transition:enter-start="-translate-y-2 opacity-0"
                 x-transition:enter-end="translate-y-0 opacity-100"
                 class="sticky top-4 z-20 mb-4 flex flex-wrap items-center gap-3 rounded-md
                        bg-hoor-navy-500 px-4 py-3 shadow-card">

                <span class="text-sm font-medium text-hoor-cream-50"
                      x-text="count + ' ' + @js(__('catalog.products.bulk_selected', ['count' => '']))"></span>

                <span class="text-sm text-hoor-cream-50/60">
                    {{ __('catalog.products.bulk_action') }}
                </span>

                <div class="flex flex-wrap gap-2">
                    @foreach ($statuses as $value => $label)
                        <button type="submit" name="action" value="{{ $value }}"
                                form="bulk-status"
                                class="rounded-sm bg-white/10 px-3 py-1.5 text-sm font-medium
                                       text-hoor-cream-50 transition hover:bg-white/20
                                       focus-visible:outline focus-visible:outline-2
                                       focus-visible:outline-offset-2 focus-visible:outline-hoor-gold-500">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <button type="button" x-on:click="selected = []"
                        class="ms-auto text-sm text-hoor-cream-50/70 transition hover:text-hoor-cream-50">
                    {{ __('common.actions.cancel') }}
                </button>
            </div>

        <x-admin.table :headings="[
            '',
            '',
            __('catalog.fields.name_en'),
            __('catalog.fields.category'),
            __('catalog.fields.base_price'),
            __('catalog.fields.stock'),
            __('catalog.fields.status'),
            '',
        ]">
            @foreach ($products as $product)
                <tr class="transition hover:bg-hoor-cream-100/50">
                    <td class="ps-4">
                        <input type="checkbox" name="products[]" value="{{ $product->id }}"
                               form="bulk-status"
                               x-model="selected"
                               class="rounded border-hoor-cream-300 text-hoor-navy-500
                                      focus:ring-hoor-denim-500"
                               aria-label="{{ __('catalog.products.select_one') }}">
                    </td>

                    {{-- Thumbnail --}}
                    <td class="px-4 py-3">
                        <div class="h-12 w-12 overflow-hidden rounded-sm border border-hoor-cream-300 bg-hoor-cream-100">
                            @if ($product->primaryImage)
                                {{-- Falls back to the empty tile if the file is
                                     missing, rather than a broken-image icon. --}}
                                <img src="{{ $product->primaryImage->url() }}"
                                     alt="{{ $product->primaryImage->alt ?? $product->name }}"
                                     loading="lazy"
                                     onerror="this.remove()"
                                     class="h-full w-full object-cover">
                            @endif
                        </div>
                    </td>

                    {{-- Name --}}
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.products.edit', $product) }}"
                           class="font-medium text-hoor-navy-700 hover:text-hoor-gold-600">
                            {{ $product->name }}
                        </a>
                        <p class="mt-0.5 text-xs text-hoor-muted" dir="ltr">{{ $product->slug }}</p>

                        <div class="mt-1 flex flex-wrap gap-1">
                            @if ($product->is_featured)
                                <x-ui.badge variant="gold">{{ __('catalog.labels.featured') }}</x-ui.badge>
                            @endif
                            @if ($product->is_new)
                                <x-ui.badge variant="denim">{{ __('catalog.labels.new') }}</x-ui.badge>
                            @endif
                            @if ($product->isOnSale())
                                <x-ui.badge variant="danger">-{{ $product->discountPercentage() }}%</x-ui.badge>
                            @endif
                        </div>
                    </td>

                    <td class="px-4 py-3 text-hoor-muted">{{ $product->category?->name }}</td>

                    {{-- Price --}}
                    <td class="whitespace-nowrap px-4 py-3">
                        @if ($product->isOnSale())
                            <span class="font-medium text-hoor-navy-700">
                                {{ \App\Casts\Money::format($product->effectivePrice()) }}
                            </span>
                            <s class="ms-1 text-xs text-hoor-muted">
                                {{ \App\Casts\Money::format($product->base_price) }}
                            </s>
                        @else
                            <span class="text-hoor-navy-700">
                                {{ \App\Casts\Money::format($product->base_price) }}
                            </span>
                        @endif
                    </td>

                    {{-- Stock is always derived from the variant rows. --}}
                    <td class="whitespace-nowrap px-4 py-3">
                        @php($stock = $product->stockStatus())
                        <x-ui.badge :variant="$stock->badge()">{{ $stock->label() }}</x-ui.badge>
                        <span class="ms-1 text-xs text-hoor-muted">{{ $product->totalStock() }}</span>
                    </td>

                    <td class="px-4 py-3">
                        <x-ui.badge :variant="$product->status->isVisible() ? 'success' : 'neutral'">
                            {{ $product->status->label() }}
                        </x-ui.badge>
                    </td>

                    <td class="whitespace-nowrap px-4 py-3 text-end">
                        <a href="{{ route('admin.products.edit', $product) }}"
                           class="text-sm font-medium text-hoor-denim-600 hover:text-hoor-denim-700">
                            {{ __('common.actions.edit') }}
                        </a>

                        @can('delete', $product)
                            <span class="mx-2 text-hoor-cream-400">|</span>
                            <x-admin.delete-form
                                :action="route('admin.products.destroy', $product)"
                                :confirm="__('catalog.products.delete_confirm')" />
                        @endcan
                    </td>
                </tr>
            @endforeach
        </x-admin.table>
        </div>

        <div class="mt-6">{{ $products->links() }}</div>
    @endif
</x-layouts.admin>
