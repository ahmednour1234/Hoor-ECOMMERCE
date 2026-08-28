<x-layouts.admin>
    @section('title', __('catalog.products.title'))
    @section('page-title', __('catalog.products.title'))

    <x-admin.page-header
        :title="__('catalog.products.title')"
        :subtitle="__('catalog.products.subtitle')">
        <x-slot:actions>
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
        <x-admin.table :headings="[
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

        <div class="mt-6">{{ $products->links() }}</div>
    @endif
</x-layouts.admin>
