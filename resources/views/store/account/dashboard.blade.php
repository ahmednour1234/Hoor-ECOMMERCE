<x-layouts.account :title="__('account.welcome', ['name' => auth()->user()->name])">

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['key' => 'orders',       'value' => $orderCount,    'route' => 'store.account.orders.index'],
            ['key' => 'open_returns', 'value' => $openReturns,   'route' => 'store.account.returns.index'],
            ['key' => 'wishlist',     'value' => $wishlistCount, 'route' => 'store.account.wishlist.index'],
            ['key' => 'addresses',    'value' => $addressCount,  'route' => 'store.account.addresses.index'],
        ] as $tile)
            <a href="{{ route($tile['route']) }}"
               class="card p-5 transition hover:border-hoor-cream-400 hover:shadow-sm">
                <p class="text-xs font-medium uppercase tracking-editorial text-hoor-muted">
                    {{ __('account.summary.'.$tile['key']) }}
                </p>
                <p class="mt-2 font-display text-2xl text-hoor-navy-700" dir="ltr">{{ $tile['value'] }}</p>
            </a>
        @endforeach
    </div>

    <section class="card mt-6 p-5">
        <div class="mb-4 flex items-baseline justify-between gap-4">
            <h2 class="font-display text-lg text-hoor-navy-700">{{ __('account.summary.recent') }}</h2>

            @if ($recentOrders->isNotEmpty())
                <a href="{{ route('store.account.orders.index') }}"
                   class="text-sm text-hoor-denim-600 hover:text-hoor-denim-700">
                    {{ __('account.summary.view_all') }}
                </a>
            @endif
        </div>

        @if ($recentOrders->isEmpty())
            <div class="py-8 text-center">
                <p class="text-sm text-hoor-muted">{{ __('account.orders.empty') }}</p>

                <x-ui.button variant="primary" size="sm" class="mt-4" :href="route('store.shop')">
                    {{ __('account.orders.empty_cta') }}
                </x-ui.button>
            </div>
        @else
            <ul class="divide-y divide-hoor-cream-300">
                @foreach ($recentOrders as $order)
                    <li>
                        <a href="{{ route('store.account.orders.show', $order) }}"
                           class="flex flex-wrap items-center justify-between gap-3 py-3 transition hover:opacity-80">
                            <div>
                                <p class="font-mono text-sm text-hoor-navy-700" dir="ltr">{{ $order->number }}</p>
                                <p class="text-xs text-hoor-muted">
                                    {{ $order->created_at->translatedFormat('d M Y') }}
                                    · {{ trans_choice('account.pieces_count', $order->items_count, ['count' => $order->items_count]) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <x-ui.badge :variant="$order->status->badge()">
                                    {{ $order->status->label() }}
                                </x-ui.badge>

                                <span class="font-medium text-hoor-navy-700" dir="ltr">
                                    {{ \App\Casts\Money::format($order->total) }}
                                </span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.account>
