{{--
    Size and colour selection, quantity, and add to bag.

    The matrix handed to Alpine contains only real, active variant rows. A pair
    with no row renders disabled — not merely unselected — so a non-existent
    combination cannot be submitted from the page at all. A pair that exists but
    has no stock stays selectable and reports itself as sold out, because the
    customer should be able to see what the piece costs and which sizes have
    gone.

    None of this is trusted: AddToCartRequest repeats every check server-side.
--}}
@props(['product', 'colors', 'sizes', 'matrix', 'selected'])

<form method="POST"
      action="{{ route('store.cart.store', $product) }}"
      @submit.prevent="submit($el.action)"
      x-data="variantSelector({
          matrix: @js($matrix),
          colorId: @js($selected?->color_id),
          sizeId: @js($selected?->size_id),
          hasColors: @js($colors->isNotEmpty()),
          hasSizes: @js($sizes->isNotEmpty()),
      })">
    @csrf

    {{-- The chosen variant, resolved from the matrix. Empty until a valid
         combination is picked, so the form cannot post a guess. --}}
    <input type="hidden" name="variant_id" :value="variant?.id ?? ''">

    {{-- Price, driven by the selected variant so overrides are reflected. --}}
    <div class="mb-6 flex flex-wrap items-baseline gap-3">
        <template x-if="variant">
            <span class="font-display text-2xl"
                  :class="variant.on_sale ? 'text-hoor-gold-600' : 'text-hoor-navy-700'"
                  dir="ltr"
                  x-text="money(variant.price)"></span>
        </template>

        <template x-if="variant && variant.on_sale">
            <s class="text-sm text-hoor-muted" dir="ltr" x-text="money(variant.base_price)"></s>
        </template>

        {{-- Before a selection exists, show the product's own range. --}}
        <template x-if="!variant">
            <span class="font-display text-2xl text-hoor-navy-700" dir="ltr">
                {{ \App\Casts\Money::format($product->effectivePrice()) }}
            </span>
        </template>
    </div>

    {{-- Colour --}}
    @if ($colors->isNotEmpty())
        <fieldset class="mb-6">
            <legend class="mb-3 flex items-center gap-2 text-sm font-medium text-hoor-navy-700">
                {{ __('store.product.choose_color') }}
                <span class="text-hoor-muted" x-text="colorName ? '— ' + colorName : ''"></span>
            </legend>

            <div class="flex flex-wrap gap-2.5">
                @foreach ($colors as $color)
                    <button type="button"
                            @click="selectColor({{ $color->id }})"
                            :disabled="!colorHasAnyVariant({{ $color->id }})"
                            class="group/swatch relative flex h-10 w-10 items-center justify-center
                                   rounded-full border-2 transition disabled:cursor-not-allowed
                                   disabled:opacity-35"
                            :class="colorId === {{ $color->id }}
                                ? 'border-hoor-navy-500'
                                : 'border-transparent hover:border-hoor-cream-400'"
                            :aria-pressed="colorId === {{ $color->id }}"
                            title="{{ $color->name }}">
                        <span class="h-8 w-8 rounded-full border border-hoor-cream-300"
                              style="background-color: {{ $color->hex }}"></span>

                        <template x-if="colorId === {{ $color->id }}">
                            <svg class="absolute h-4 w-4 {{ $color->isLight() ? 'text-hoor-navy-700' : 'text-white' }}"
                                 fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </template>

                        <span class="sr-only">{{ $color->name }}</span>
                    </button>
                @endforeach
            </div>
        </fieldset>
    @endif

    {{-- Size --}}
    @if ($sizes->isNotEmpty())
        <fieldset class="mb-6">
            <legend class="mb-3 flex items-center justify-between gap-2 text-sm font-medium text-hoor-navy-700">
                <span class="flex items-center gap-2">
                    {{ __('store.product.choose_size') }}
                    <span class="text-hoor-muted" x-text="sizeName ? '— ' + sizeName : ''"></span>
                </span>

                <a href="#size-guide" class="text-xs font-normal text-hoor-gold-600 hover:text-hoor-gold-700">
                    {{ __('store.product.size_guide') }}
                </a>
            </legend>

            <div class="flex flex-wrap gap-2">
                @foreach ($sizes as $size)
                    <button type="button"
                            @click="selectSize({{ $size->id }})"
                            :disabled="!sizeExists({{ $size->id }})"
                            class="relative flex h-11 min-w-11 items-center justify-center rounded-sm
                                   border px-3 text-sm transition disabled:cursor-not-allowed"
                            :class="sizeClasses({{ $size->id }})"
                            :aria-pressed="sizeId === {{ $size->id }}"
                            :title="sizeTitle({{ $size->id }}, @js($size->name))"
                            dir="ltr">
                        {{ $size->name }}

                        {{-- A size that exists in this colour but has sold out is
                             struck through, so the shopper sees it was offered. --}}
                        <template x-if="sizeExists({{ $size->id }}) && !sizeInStock({{ $size->id }})">
                            <span class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
                                <span class="h-px w-full rotate-[-20deg] bg-hoor-muted/50"></span>
                            </span>
                        </template>
                    </button>
                @endforeach
            </div>
        </fieldset>
    @endif

    {{-- Availability --}}
    <p class="mb-5 flex items-center gap-2 text-sm" aria-live="polite">
        <template x-if="variant && variant.in_stock && variant.status === 'low_stock'">
            <span class="text-amber-700"
                  x-text="@js(trans_choice('store.product.only_left', 2, ['count' => ':n'])).replace(':n', variant.stock)"></span>
        </template>

        <template x-if="variant && variant.in_stock && variant.status !== 'low_stock'">
            <span class="text-emerald-700">{{ __('store.product.in_stock') }}</span>
        </template>

        <template x-if="variant && !variant.in_stock">
            <span class="text-red-600">{{ __('store.product.sold_out') }}</span>
        </template>

        <template x-if="!variant">
            <span class="text-hoor-muted">{{ __('store.product.select_first') }}</span>
        </template>

        <template x-if="variant">
            <span class="ms-auto font-mono text-xs text-hoor-muted" dir="ltr" x-text="variant.sku"></span>
        </template>
    </p>

    {{-- Quantity and add to bag --}}
    <div class="flex flex-wrap items-stretch gap-3">
        <div class="flex items-center rounded-sm border border-hoor-cream-300">
            <button type="button" @click="decrement()"
                    class="flex h-12 w-11 items-center justify-center text-hoor-navy-600
                           transition hover:bg-hoor-cream-100 disabled:opacity-40"
                    :disabled="quantity <= 1"
                    aria-label="-">&minus;</button>

            <label for="quantity" class="sr-only">{{ __('store.product.quantity') }}</label>
            <input type="number" id="quantity" name="quantity" min="1" :max="maxQuantity"
                   x-model.number="quantity" @change="clampQuantity()"
                   dir="ltr" inputmode="numeric"
                   class="h-12 w-14 border-0 bg-transparent p-0 text-center text-sm focus:ring-0">

            <button type="button" @click="increment()"
                    class="flex h-12 w-11 items-center justify-center text-hoor-navy-600
                           transition hover:bg-hoor-cream-100 disabled:opacity-40"
                    :disabled="quantity >= maxQuantity"
                    aria-label="+">+</button>
        </div>

        <button type="submit"
                class="btn-primary h-12 flex-1"
                :disabled="!canAddToCart || adding"
                x-text="adding ? @js(__('common.states.loading')) : addToCartLabel">{{ __('store.product.add_to_cart') }}</button>

        <x-store.wishlist-button :product="$product"
                                 class="!relative !end-auto !top-auto h-12 w-12 border border-hoor-cream-300 !bg-white" />
    </div>

    {{-- Server-side errors, shown even though the UI tries to prevent them. --}}
    @error('variant_id')
        <p class="form-error mt-3">{{ $message }}</p>
    @enderror
    @error('quantity')
        <p class="form-error mt-3">{{ $message }}</p>
    @enderror

    @if (session('cart_status'))
        <p class="mt-3 rounded-sm bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('cart_status') }}
        </p>
    @endif
</form>
