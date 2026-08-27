<x-layouts.account :title="__('account.orders.number').' '.$order->number">
    <x-slot:subtitle>
        {{ $order->created_at->translatedFormat('d M Y') }}
    </x-slot:subtitle>

    <div class="mb-6 flex flex-wrap gap-2">
        <x-ui.button variant="ghost" size="sm" :href="route('store.account.orders.index')">
            {{ __('account.orders.back') }}
        </x-ui.button>

        @if ($canReturn)
            <x-ui.button variant="outline" size="sm"
                         :href="route('store.account.returns.create', $order)">
                {{ __('account.orders.request_return') }}
            </x-ui.button>
        @endif
    </div>

    {{-- Requests already raised against this order, so she is not left
         wondering whether the last one went through. --}}
    @if ($returns->isNotEmpty())
        <section class="card mb-6 p-5">
            <h2 class="mb-3 font-display text-lg text-hoor-navy-700">
                {{ __('account.orders.returns_on_order') }}
            </h2>

            <ul class="divide-y divide-hoor-cream-300">
                @foreach ($returns as $return)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div>
                            <a href="{{ route('store.account.returns.show', $return) }}"
                               class="font-mono text-sm text-hoor-navy-700 hover:text-hoor-gold-600" dir="ltr">
                                {{ $return->number }}
                            </a>
                            <p class="text-xs text-hoor-muted">
                                {{ $return->type->label() }}
                                · {{ $return->created_at->translatedFormat('d M Y') }}
                            </p>
                        </div>

                        <x-ui.badge :variant="$return->status->badge()">
                            {{ $return->status->label() }}
                        </x-ui.badge>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <x-store.order-summary :order="$order" />

    @if ($order->notes)
        <section class="card mt-6 p-5">
            <h2 class="mb-2 font-display text-lg text-hoor-navy-700">{{ __('checkout.fields.notes') }}</h2>
            <p class="whitespace-pre-line text-sm text-hoor-muted">{{ $order->notes }}</p>
        </section>
    @endif
</x-layouts.account>
