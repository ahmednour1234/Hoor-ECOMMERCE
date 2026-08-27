<x-layouts.admin>
    @section('title', __('admin.nav.dashboard'))
    @section('page-title', __('admin.nav.dashboard'))

    <x-admin.page-header :title="__('admin.welcome', ['name' => auth()->user()->name])" />

    <x-admin.period-filter :period="$period" class="mb-6" />

    {{--
        Eight figures. The work queues (pending, awaiting shipping) and the
        stock counts link straight to the screen that acts on them, because a
        dashboard number nobody can act on is decoration.
    --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat :label="__('admin.stats.orders_period')"
                      :value="$cards['orders']['value']"
                      :change="$cards['orders']['change']" tone="navy" />

        <x-admin.stat :label="__('admin.stats.revenue_period')"
                      :value="$cards['revenue']['value']"
                      :change="$cards['revenue']['change']" money tone="gold">
            <x-slot:hint>{{ __('admin.stats.revenue_note') }}</x-slot:hint>
        </x-admin.stat>

        <x-admin.stat :label="__('admin.stats.pending_orders')"
                      :value="$cards['pending']['value']" tone="denim"
                      :href="route('admin.orders.index', ['status' => 'pending'])">
            <x-slot:hint>{{ __('admin.stats.needs_action') }}</x-slot:hint>
        </x-admin.stat>

        <x-admin.stat :label="__('admin.stats.awaiting_shipping')"
                      :value="$cards['awaiting_shipping']['value']" tone="denim"
                      :href="route('admin.orders.index', ['status' => 'ready_for_shipping'])">
            <x-slot:hint>{{ __('admin.stats.needs_action') }}</x-slot:hint>
        </x-admin.stat>

        <x-admin.stat :label="__('admin.stats.delivered')"
                      :value="$cards['delivered']['value']"
                      :change="$cards['delivered']['change']" tone="navy"
                      :href="route('admin.orders.index', ['status' => 'delivered'])" />

        <x-admin.stat :label="__('admin.stats.cancelled')"
                      :value="$cards['cancelled']['value']"
                      :change="$cards['cancelled']['change']" tone="danger" inverted
                      :href="route('admin.orders.index', ['status' => 'cancelled'])" />

        <x-admin.stat :label="__('admin.stats.low_stock')"
                      :value="$cards['low_stock']['value']" tone="denim">
            <x-slot:hint>{{ __('admin.stats.right_now') }}</x-slot:hint>
        </x-admin.stat>

        <x-admin.stat :label="__('admin.stats.out_of_stock')"
                      :value="$cards['out_of_stock']['value']" tone="danger">
            <x-slot:hint>{{ __('admin.stats.right_now') }}</x-slot:hint>
        </x-admin.stat>
    </div>

    {{-- ----------------------------------------------------------- Charts --}}
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-admin.panel :title="__('admin.dashboard.sales_over_time')">
            <p class="-mt-2 mb-4 text-xs text-hoor-muted">{{ __('admin.stats.revenue_note') }}</p>

            <x-admin.chart.bars :series="$charts['series']" metric="revenue" money
                                :grouping="$charts['grouping']" />
        </x-admin.panel>

        <x-admin.panel :title="__('admin.dashboard.orders_over_time')">
            <x-admin.chart.bars :series="$charts['series']" metric="orders"
                                :grouping="$charts['grouping']" class="mt-2" />
        </x-admin.panel>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-admin.panel :title="__('admin.dashboard.orders_by_status')">
            <x-admin.chart.breakdown :rows="collect($charts['status'])
                ->map(fn ($s) => ['label' => $s['label'], 'value' => $s['count'], 'variant' => $s['variant']])
                ->all()" />
        </x-admin.panel>

        {{-- --------------------------------------------------- Recent orders --}}
        <x-admin.panel :title="__('admin.dashboard.recent_orders')" class="lg:col-span-2">
            @if ($recent->isEmpty())
                <p class="py-8 text-center text-sm text-hoor-muted">{{ __('admin.dashboard.no_data') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-hoor-cream-300">
                            @foreach ($recent as $order)
                                <tr class="transition hover:bg-hoor-cream-100/50">
                                    <td class="py-2.5 pe-3">
                                        <a href="{{ route('admin.orders.show', $order) }}"
                                           class="font-mono text-xs font-medium text-hoor-navy-700 hover:text-hoor-gold-600"
                                           dir="ltr">{{ $order->number }}</a>
                                    </td>

                                    <td class="px-3 py-2.5">
                                        <span class="text-hoor-navy-700">{{ $order->address?->full_name }}</span>
                                    </td>

                                    <td class="whitespace-nowrap px-3 py-2.5 text-xs text-hoor-muted">
                                        {{ $order->created_at->diffForHumans() }}
                                    </td>

                                    <td class="px-3 py-2.5">
                                        <x-ui.badge :variant="$order->status->badge()">
                                            {{ $order->status->label() }}
                                        </x-ui.badge>
                                    </td>

                                    <td class="whitespace-nowrap py-2.5 ps-3 text-end font-medium text-hoor-navy-700"
                                        dir="ltr">
                                        {{ \App\Casts\Money::format($order->total) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <a href="{{ route('admin.orders.index') }}"
                   class="mt-4 inline-block text-sm text-hoor-denim-600 hover:text-hoor-denim-700">
                    {{ __('admin.dashboard.view_all_orders') }} &rarr;
                </a>
            @endif
        </x-admin.panel>
    </div>

    {{-- -------------------------------------------------------- Analytics --}}
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-admin.panel :title="__('admin.dashboard.best_selling')">
            <x-admin.chart.breakdown
                :rows="$analytics['products']->map(fn ($r) => [
                    'label' => $r->name, 'value' => (int) $r->units, 'variant' => 'denim',
                ])->all()"
                :empty="__('admin.dashboard.no_sales_yet')" />
        </x-admin.panel>

        <x-admin.panel :title="__('admin.dashboard.top_categories')">
            <x-admin.chart.breakdown
                :rows="$analytics['categories']->map(fn ($r) => [
                    'label' => $r->name, 'value' => (int) $r->units, 'variant' => 'navy',
                ])->all()"
                :empty="__('admin.dashboard.no_sales_yet')" />
        </x-admin.panel>

        <x-admin.panel :title="__('admin.dashboard.top_sizes')">
            <x-admin.chart.breakdown
                :rows="$analytics['sizes']->map(fn ($r) => [
                    'label' => $r->name, 'value' => (int) $r->units, 'variant' => 'navy',
                ])->all()"
                :empty="__('admin.dashboard.no_sales_yet')" />
        </x-admin.panel>

        <x-admin.panel :title="__('admin.dashboard.top_colors')">
            <x-admin.chart.breakdown
                :rows="$analytics['colors']->map(fn ($r) => [
                    'label' => $r->name, 'value' => (int) $r->units, 'variant' => 'denim',
                ])->all()"
                :empty="__('admin.dashboard.no_sales_yet')" />
        </x-admin.panel>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Revenue rather than order count: which governorates are worth the
             shipping rates we set for them. --}}
        <x-admin.panel :title="__('admin.dashboard.by_governorate')">
            <x-admin.chart.breakdown money
                :rows="$analytics['governorates']->map(fn ($r) => [
                    'label' => $r->name, 'value' => (int) $r->revenue, 'variant' => 'gold',
                ])->all()"
                :empty="__('admin.dashboard.no_sales_yet')" />
        </x-admin.panel>

        {{-- ------------------------------------------------------- Low stock --}}
        <x-admin.panel :title="__('admin.dashboard.low_stock')">
            @if ($lowStock->isEmpty())
                <p class="py-8 text-center text-sm text-hoor-muted">{{ __('admin.dashboard.stock_healthy') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-hoor-cream-300">
                            @foreach ($lowStock as $variant)
                                <tr class="transition hover:bg-hoor-cream-100/50">
                                    <td class="py-2.5 pe-3">
                                        <a href="{{ route('admin.products.edit', $variant->product) }}"
                                           class="text-hoor-navy-700 hover:text-hoor-gold-600">
                                            {{ $variant->product?->name }}
                                        </a>
                                        <p class="font-mono text-xs text-hoor-muted" dir="ltr">{{ $variant->sku }}</p>
                                    </td>

                                    <td class="whitespace-nowrap px-3 py-2.5 text-xs text-hoor-muted">
                                        {{ collect([$variant->size?->name, $variant->color?->name])->filter()->join(' / ') }}
                                    </td>

                                    <td class="whitespace-nowrap py-2.5 ps-3 text-end" dir="ltr">
                                        <span class="font-medium {{ $variant->stock_quantity <= 0 ? 'text-red-600' : 'text-amber-600' }}">
                                            {{ $variant->stock_quantity }}
                                        </span>
                                        <span class="text-xs text-hoor-muted">/ {{ $variant->low_stock_threshold }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-admin.panel>
    </div>
</x-layouts.admin>
