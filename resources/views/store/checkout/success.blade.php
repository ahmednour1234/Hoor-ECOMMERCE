<x-layouts.store>
    @section('title', __('checkout.success.title').' — '.__('common.brand'))
    @section('description', __('checkout.success.lead'))

    @php($address = $order->address)

    <div class="hoor-container py-10 lg:py-16">
        <div class="mx-auto max-w-2xl">

            {{-- Confirmation --}}
            <div class="text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full
                             bg-emerald-50 text-emerald-600" aria-hidden="true">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2.5"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>

                <h1 class="mt-5 font-display text-3xl text-hoor-navy-700 sm:text-4xl">
                    {{ __('checkout.success.title') }}
                </h1>

                <p class="mt-3 text-sm leading-relaxed text-hoor-muted">
                    {{ __('checkout.success.lead') }}
                </p>
            </div>

            {{-- The four facts a customer needs immediately. --}}
            <div class="card mt-8 p-6">
                <dl class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-editorial text-hoor-muted">
                            {{ __('checkout.success.number') }}
                        </dt>
                        <dd class="mt-1 font-mono text-lg font-medium text-hoor-navy-700" dir="ltr">
                            {{ $order->number }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-editorial text-hoor-muted">
                            {{ __('checkout.success.name') }}
                        </dt>
                        <dd class="mt-1 text-lg text-hoor-navy-700">{{ $order->customerName() }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-editorial text-hoor-muted">
                            {{ __('checkout.success.total') }}
                        </dt>
                        <dd class="mt-1 font-display text-lg text-hoor-navy-700" dir="ltr">
                            {{ $order->formattedTotal() }}
                        </dd>
                        <dd class="text-xs text-hoor-muted">{{ __('orders.payment.cash_on_delivery') }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-editorial text-hoor-muted">
                            {{ __('checkout.success.status') }}
                        </dt>
                        <dd class="mt-1">
                            <x-ui.badge :variant="$order->status->badge()">
                                {{ $order->status->label() }}
                            </x-ui.badge>
                        </dd>
                    </div>
                </dl>

                <p class="mt-5 rounded-sm bg-hoor-cream-100 px-4 py-3 text-xs text-hoor-muted">
                    {{ __('checkout.success.keep_number') }}
                </p>
            </div>

            {{-- What happens next --}}
            <section class="card mt-6 p-6">
                <h2 class="font-display text-lg text-hoor-navy-700">
                    {{ __('checkout.success.next_title') }}
                </h2>

                <ol class="mt-4 space-y-4">
                    @foreach ([
                        'call'    => ['phone' => $address?->phone],
                        'prepare' => [],
                        'deliver' => ['days' => $order->address?->governorate_id
                            ? \App\Models\Governorate::find($order->address->governorate_id)?->deliveryWindow()
                            : ''],
                        'pay'     => ['total' => $order->formattedTotal()],
                    ] as $step => $replacements)
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full
                                         bg-hoor-navy-500 text-xs font-medium text-hoor-cream-50">
                                {{ $loop->iteration }}
                            </span>
                            <span class="text-sm leading-relaxed text-hoor-muted">
                                {{ __("checkout.success.next.{$step}", $replacements) }}
                            </span>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- Delivery address --}}
            @if ($address)
                <section class="card mt-6 p-6">
                    <h2 class="text-xs uppercase tracking-editorial text-hoor-muted">
                        {{ __('checkout.success.delivering_to') }}
                    </h2>

                    <p class="mt-3 text-sm leading-relaxed text-hoor-navy-700">
                        {{ $address->full_name }}<br>
                        {{ $address->formatted() }}<br>
                        <span dir="ltr">{{ implode(' · ', $address->phones()) }}</span>
                    </p>
                </section>
            @endif

            {{-- Items --}}
            <section class="card mt-6 p-6">
                <ul class="divide-y divide-hoor-cream-300">
                    @foreach ($order->items as $item)
                        <li class="flex gap-3 py-3 first:pt-0 last:pb-0">
                            <span class="h-16 w-12 shrink-0 overflow-hidden rounded-sm bg-hoor-cream-100">
                                @if ($item->imageUrl())
                                    <img src="{{ $item->imageUrl() }}" alt=""
                                         loading="lazy" class="h-full w-full object-cover">
                                @endif
                            </span>

                            <span class="min-w-0 flex-1 text-sm">
                                {{-- Read from the order's own snapshot, so this
                                     stays correct even if the catalog changes. --}}
                                <span class="block text-hoor-navy-700">{{ $item->product_name }}</span>
                                <span class="block text-xs text-hoor-muted">
                                    {{ $item->variantLabel() }} &times; {{ $item->quantity }}
                                </span>
                            </span>

                            <span class="shrink-0 text-sm text-hoor-navy-700" dir="ltr">
                                {{ $item->formattedLineTotal() }}
                            </span>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-4 space-y-2 border-t border-hoor-cream-300 pt-4 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-hoor-muted">{{ __('checkout.summary.subtotal') }}</dt>
                        <dd class="text-hoor-navy-700" dir="ltr">
                            {{ \App\Casts\Money::format($order->subtotal) }}
                        </dd>
                    </div>

                    @if ($order->discount > 0)
                        <div class="flex justify-between">
                            <dt class="text-hoor-gold-600">{{ __('checkout.summary.discount') }}</dt>
                            <dd class="text-hoor-gold-600" dir="ltr">
                                &minus;{{ \App\Casts\Money::format($order->discount) }}
                            </dd>
                        </div>
                    @endif

                    <div class="flex justify-between">
                        <dt class="text-hoor-muted">{{ __('checkout.summary.shipping') }}</dt>
                        <dd class="text-hoor-navy-700" dir="ltr">
                            {{ \App\Casts\Money::format($order->shipping) }}
                        </dd>
                    </div>

                    <div class="flex justify-between border-t border-hoor-cream-300 pt-2">
                        <dt class="font-medium text-hoor-navy-700">{{ __('checkout.summary.total') }}</dt>
                        <dd class="font-display text-lg text-hoor-navy-700" dir="ltr">
                            {{ $order->formattedTotal() }}
                        </dd>
                    </div>
                </dl>
            </section>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <x-ui.button variant="outline" :href="route('store.shop')">
                    {{ __('checkout.success.continue') }}
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layouts.store>
