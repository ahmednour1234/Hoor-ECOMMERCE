<x-layouts.account :title="__('account.orders.title')">

    @if ($orders->isEmpty())
        <div class="card p-10 text-center">
            <p class="text-hoor-muted">{{ __('account.orders.empty') }}</p>

            <x-ui.button variant="primary" class="mt-6" :href="route('store.shop')">
                {{ __('account.orders.empty_cta') }}
            </x-ui.button>
        </div>
    @else
        <ul class="space-y-4">
            @foreach ($orders as $order)
                <li class="card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="font-mono text-sm font-medium text-hoor-navy-700" dir="ltr">
                                {{ $order->number }}
                            </p>

                            <p class="mt-1 text-xs text-hoor-muted">
                                {{ __('account.orders.placed') }}
                                {{ $order->created_at->translatedFormat('d M Y') }}
                                · {{ trans_choice('account.pieces_count', $order->items_count, ['count' => $order->items_count]) }}
                            </p>
                        </div>

                        <div class="flex flex-col items-end gap-2">
                            <x-ui.badge :variant="$order->status->badge()">
                                {{ $order->status->label() }}
                            </x-ui.badge>

                            <span class="font-medium text-hoor-navy-700" dir="ltr">
                                {{ \App\Casts\Money::format($order->total) }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2 border-t border-hoor-cream-300 pt-4">
                        <x-ui.button variant="outline" size="sm"
                                     :href="route('store.account.orders.show', $order)">
                            {{ __('account.orders.view') }}
                        </x-ui.button>

                        @if ($order->isReturnable())
                            <x-ui.button variant="ghost" size="sm"
                                         :href="route('store.account.returns.create', $order)">
                                {{ __('account.orders.request_return') }}
                            </x-ui.button>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</x-layouts.account>
