{{--
    An order as the customer sees it.

    Shared by the account order page and the public tracking page: both show
    the same thing, and the only difference is how the visitor proved they may
    see it. Keeping one component means a change to how an order reads happens
    once.
--}}
@props(['order', 'showHistory' => true])

<div {{ $attributes->merge(['class' => 'space-y-6']) }}>

    {{-- Progress. The current status is emphasised; the rest of the journey is
         shown so she knows what happens next. --}}
    <section class="card p-5">
        <h2 class="mb-4 font-display text-lg text-hoor-navy-700">{{ __('tracking.show.progress') }}</h2>

        <div class="flex flex-wrap items-center gap-3">
            <x-ui.badge :variant="$order->status->badge()" class="text-sm">
                {{ $order->status->label() }}
            </x-ui.badge>

            <span class="text-sm text-hoor-muted">
                {{ __('tracking.show.placed', ['date' => $order->created_at->translatedFormat('d M Y')]) }}
            </span>
        </div>

        @if ($showHistory && $order->relationLoaded('statusHistory') && $order->statusHistory->isNotEmpty())
            <ol class="mt-5 space-y-3 border-t border-hoor-cream-300 pt-4">
                @foreach ($order->statusHistory as $entry)
                    <li class="flex gap-3 text-sm">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-hoor-denim-500"></span>

                        <div>
                            <p class="text-hoor-navy-700">{{ $entry->to_status->label() }}</p>
                            <p class="text-xs text-hoor-muted">
                                {{ $entry->created_at->translatedFormat('d M Y — H:i') }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    {{-- What she ordered, from the snapshot the order holds. --}}
    <section class="card p-5">
        <h2 class="mb-4 font-display text-lg text-hoor-navy-700">{{ __('tracking.show.items') }}</h2>

        <ul class="divide-y divide-hoor-cream-300">
            @foreach ($order->items as $item)
                <li class="flex items-start justify-between gap-4 py-3">
                    <div>
                        <p class="text-sm text-hoor-navy-700">{{ $item->product_name }}</p>

                        <p class="mt-0.5 text-xs text-hoor-muted">
                            {{ collect([$item->size_name, $item->color_name])->filter()->join(' / ') }}
                            @if ($item->quantity > 1)
                                <span dir="ltr">× {{ $item->quantity }}</span>
                            @endif
                        </p>
                    </div>

                    <span class="shrink-0 text-sm font-medium text-hoor-navy-700" dir="ltr">
                        {{ \App\Casts\Money::format($item->line_total) }}
                    </span>
                </li>
            @endforeach
        </ul>

        <dl class="mt-4 space-y-2 border-t border-hoor-cream-300 pt-4 text-sm">
            <div class="flex justify-between">
                <dt class="text-hoor-muted">{{ __('checkout.summary.subtotal') }}</dt>
                <dd class="text-hoor-navy-700" dir="ltr">{{ \App\Casts\Money::format($order->subtotal) }}</dd>
            </div>

            <div class="flex justify-between">
                <dt class="text-hoor-muted">{{ __('checkout.summary.shipping') }}</dt>
                <dd class="text-hoor-navy-700" dir="ltr">{{ \App\Casts\Money::format($order->shipping) }}</dd>
            </div>

            @if ($order->discount > 0)
                <div class="flex justify-between">
                    <dt class="text-hoor-muted">{{ __('checkout.summary.discount') }}</dt>
                    <dd class="text-hoor-gold-600" dir="ltr">
                        −{{ \App\Casts\Money::format($order->discount) }}
                    </dd>
                </div>
            @endif

            <div class="flex justify-between border-t border-hoor-cream-300 pt-2 text-base font-semibold">
                <dt class="text-hoor-navy-700">{{ __('checkout.summary.total') }}</dt>
                <dd class="text-hoor-navy-700" dir="ltr">{{ \App\Casts\Money::format($order->total) }}</dd>
            </div>
        </dl>
    </section>

    {{-- Where it is going. --}}
    @if ($order->address)
        <section class="card p-5">
            <h2 class="mb-3 font-display text-lg text-hoor-navy-700">{{ __('tracking.show.delivery') }}</h2>

            <address class="space-y-1 text-sm not-italic text-hoor-navy-700">
                <p>{{ $order->address->full_name }}</p>
                <p dir="ltr">{{ $order->address->phone }}</p>
                <p class="text-hoor-muted">{{ $order->address->address }}</p>
                <p class="text-hoor-muted">
                    {{ collect([$order->address->area_name, $order->address->governorate_name])->filter()->join('، ') }}
                </p>

                @if ($order->address->landmark)
                    <p class="text-xs text-hoor-muted">{{ $order->address->landmark }}</p>
                @endif
            </address>
        </section>
    @endif
</div>
