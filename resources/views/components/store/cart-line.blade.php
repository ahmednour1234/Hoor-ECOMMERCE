{{--
    One line in the cart.

    Quantity and removal go through the shared cart store, so nothing reloads.
    The markup is still server-rendered and every control remains a real form,
    so the page works unchanged with JavaScript disabled.

    Figures shown after an update come from the server's response, never from
    arithmetic in the browser.
--}}
@props(['line'])

@php
    $variant = $line->variant;
    $product = $line->product();
    $image = $product->primaryImage;
    $unavailable = ! $line->isAvailable();
@endphp

<div x-data="cartLine({
        id: {{ $variant->id }},
        quantity: {{ $line->quantity }},
        max: {{ $variant->stock_quantity }},
     })"
     x-show="! removed"
     x-transition:leave="transition ease-hoor duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="flex gap-4 transition-opacity"
     :class="(busy || {{ $unavailable ? 'true' : 'false' }}) && 'opacity-60'">

    {{-- Thumbnail --}}
    <a href="{{ route('store.products.show', $product) }}"
       class="block h-28 w-22 shrink-0 overflow-hidden rounded-sm bg-hoor-cream-100 sm:h-32 sm:w-26">
        @if ($image)
            <img src="{{ $image->url() }}"
                 alt="{{ $image->alt ?? $product->name }}"
                 width="1122" height="1402"
                 loading="lazy" decoding="async"
                 class="h-full w-full object-cover">
        @endif
    </a>

    {{-- Details --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1">
            <div class="min-w-0">
                @if ($product->category)
                    <p class="text-xs text-hoor-muted">{{ $product->category->name }}</p>
                @endif

                <h3 class="mt-0.5 text-sm font-medium text-hoor-navy-700">
                    <a href="{{ route('store.products.show', $product) }}"
                       class="transition hover:text-hoor-gold-600">
                        {{ $product->name }}
                    </a>
                </h3>

                <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-hoor-muted">
                    <span>{{ $variant->label() }}</span>
                    <span class="text-hoor-cream-400" aria-hidden="true">|</span>
                    <span class="font-mono" dir="ltr">{{ $variant->sku }}</span>
                </p>
            </div>

            {{-- Line total: server-rendered, then replaced from each response. --}}
            <div class="text-end">
                <p class="text-sm font-medium {{ $line->isOnSale() ? 'text-hoor-gold-600' : 'text-hoor-navy-700' }}"
                   dir="ltr"
                   x-text="totalFormatted">{{ $line->formattedLineTotal() }}</p>

                <p class="mt-0.5 text-xs text-hoor-muted" dir="ltr"
                   x-show="quantity > 1" @if ($line->quantity <= 1) x-cloak @endif>
                    <span x-text="unitFormatted">{{ $line->formattedUnitPrice() }}</span>
                    &times;
                    <span x-text="quantity">{{ $line->quantity }}</span>
                </p>
            </div>
        </div>

        {{-- Availability --}}
        @php($stock = $line->stockStatus())

        @if ($unavailable)
            <p class="mt-2 text-xs text-red-600">{{ __('catalog.stock.out_of_stock') }}</p>
        @elseif ($line->wasReduced())
            <p class="mt-2 text-xs text-amber-700">
                {{ trans_choice('store.product.only_left', $line->availableQuantity, ['count' => $line->availableQuantity]) }}
            </p>
        @elseif ($stock === \App\Enums\StockStatus::LowStock)
            <p class="mt-2 text-xs text-amber-700">
                {{ trans_choice('store.product.only_left', $variant->stock_quantity, ['count' => $variant->stock_quantity]) }}
            </p>
        @endif

        {{-- Quantity and removal --}}
        <div class="mt-auto flex flex-wrap items-center gap-3 pt-3">
            {{-- Still a real form: submitting it without JavaScript performs the
                 same update through the same route. --}}
            <form method="POST" action="{{ route('store.cart.update') }}"
                  @submit.prevent="setQuantity(quantity)"
                  class="flex items-center gap-2">
                @csrf
                @method('PATCH')
                <input type="hidden" name="variant_id" value="{{ $variant->id }}">

                <label for="qty-{{ $variant->id }}" class="sr-only">{{ __('cart.quantity') }}</label>

                <div class="flex items-center rounded-sm border border-hoor-cream-300">
                    <button type="submit" name="quantity" :value="Math.max(0, quantity - 1)"
                            @click.prevent="step(-1)"
                            :disabled="busy"
                            class="flex h-9 w-9 items-center justify-center text-hoor-navy-600
                                   transition hover:bg-hoor-cream-100 disabled:opacity-40"
                            aria-label="&minus;">&minus;</button>

                    <input type="number" id="qty-{{ $variant->id }}" name="quantity"
                           x-model.number="quantity"
                           @change="setQuantity(quantity)"
                           min="0" max="{{ $variant->stock_quantity }}"
                           inputmode="numeric" dir="ltr"
                           :disabled="busy"
                           class="h-9 w-12 border-0 bg-transparent p-0 text-center text-sm focus:ring-0
                                  disabled:opacity-60">

                    <button type="submit" name="quantity" :value="quantity + 1"
                            @click.prevent="step(1)"
                            :disabled="busy || quantity >= max"
                            class="flex h-9 w-9 items-center justify-center text-hoor-navy-600
                                   transition hover:bg-hoor-cream-100 disabled:opacity-40"
                            aria-label="+">+</button>
                </div>
            </form>

            <form method="POST" action="{{ route('store.cart.destroy', $variant->id) }}"
                  @submit.prevent="remove()">
                @csrf
                @method('DELETE')
                <button type="submit"
                        :disabled="busy"
                        class="text-xs text-hoor-muted underline-offset-2 transition
                               hover:text-red-600 hover:underline disabled:opacity-40">
                    {{ __('cart.remove') }}
                </button>
            </form>
        </div>
    </div>
</div>
