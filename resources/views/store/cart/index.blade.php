<x-layouts.store>
    @section('title', __('cart.title').' — '.__('common.brand'))
    @section('description', __('cart.subtitle'))

    <div class="hoor-container py-10 lg:py-14"
         x-data="cartSummary({ empty: @js($cart->isEmpty()), ready: @js($cart->isCheckoutReady()), hasSavings: @js($cart->hasSavings()) })">

        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="section-title">{{ __('cart.title') }}</h1>
                @if ($cart->isNotEmpty())
                    <p class="mt-2 text-sm text-hoor-muted">{{ __('cart.subtitle') }}</p>
                @endif
            </div>

            @if ($cart->isNotEmpty())
                <form method="POST" action="{{ route('store.cart.clear') }}"
                      @submit.prevent="confirm(@js(__('cart.clear_confirm'))) && $store.cart.clear()">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-hoor-muted transition hover:text-red-600">
                        {{ __('cart.clear') }}
                    </button>
                </form>
            @endif
        </div>

        {{-- Notices raised while loading: something the customer was holding is
             no longer available at the quantity they wanted. --}}
        @if ($cart->hasNotices())
            <x-ui.alert variant="warning" class="mb-6" :title="__('cart.notices.title')">
                <ul class="mt-1 space-y-1">
                    @foreach ($cart->notices as $notice)
                        <li>{{ $notice }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @if (session('cart_status'))
            <x-ui.alert variant="success" class="mb-6">{{ session('cart_status') }}</x-ui.alert>
        @endif

        @error('cart')
            <x-ui.alert variant="danger" class="mb-6">{{ $message }}</x-ui.alert>
        @enderror

        <div x-show="empty" @if (! $cart->isEmpty()) x-cloak @endif>
            <x-admin.empty-state :title="__('cart.empty')" :message="__('cart.empty_hint')">
                <x-slot:action>
                    <x-ui.button variant="primary" :href="route('store.shop')">
                        {{ __('cart.continue') }}
                    </x-ui.button>
                </x-slot:action>
            </x-admin.empty-state>
        </div>

        @if ($cart->isNotEmpty())
            <div class="lg:flex lg:items-start lg:gap-10" x-show="! empty">

                {{-- Lines --}}
                <div class="min-w-0 flex-1">
                    <ul class="divide-y divide-hoor-cream-300 border-y border-hoor-cream-300">
                        @foreach ($cart->lines as $line)
                            <li class="py-5">
                                <x-store.cart-line :line="$line" />
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-6">
                        <a href="{{ route('store.shop') }}"
                           class="group inline-flex items-center gap-1.5 text-sm font-medium
                                  text-hoor-navy-600 transition hover:text-hoor-gold-600">
                            <span class="rtl:rotate-180" aria-hidden="true">&larr;</span>
                            {{ __('cart.continue') }}
                        </a>
                    </div>
                </div>

                {{-- Summary --}}
                <aside class="mt-10 w-full shrink-0 lg:mt-0 lg:w-80">
                    <div class="card p-5 lg:sticky lg:top-24">
                        <h2 class="font-display text-lg text-hoor-navy-700">
                            {{ __('cart.summary.title') }}
                        </h2>

                        <dl class="mt-4 space-y-2.5 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-hoor-muted">{{ __('cart.summary.subtotal') }}</dt>
                                <dd class="font-medium text-hoor-navy-700" dir="ltr"
                                    x-text="subtotal">{{ $cart->formattedSubtotal() }}</dd>
                            </div>

                            <div class="flex justify-between" x-show="hasSavings"
                                 @if (! $cart->hasSavings()) x-cloak @endif>
                                <dt class="text-hoor-gold-600">{{ __('cart.summary.savings') }}</dt>
                                <dd class="font-medium text-hoor-gold-600" dir="ltr">
                                    &minus;<span x-text="savings">{{ $cart->formattedSavings() }}</span>
                                </dd>
                            </div>

                            {{-- The discount, computed server-side by the same
                                 CouponService checkout uses. --}}
                            @if ($coupon['valid'])
                                <div class="flex justify-between">
                                    <dt class="text-hoor-gold-600">
                                        {{ __('coupons.cart.applied', ['code' => $coupon['code']]) }}
                                    </dt>
                                    <dd class="font-medium text-hoor-gold-600" dir="ltr">
                                        &minus;{{ \App\Casts\Money::format($coupon['discount']) }}
                                    </dd>
                                </div>
                            @endif

                            <div class="flex justify-between">
                                <dt class="text-hoor-muted">{{ __('cart.summary.shipping') }}</dt>
                                <dd class="text-xs text-hoor-muted">
                                    {{ __('cart.summary.shipping_at_checkout') }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex items-baseline justify-between border-t border-hoor-cream-300 pt-4">
                            <span class="font-medium text-hoor-navy-700">{{ __('cart.summary.total') }}</span>

                            {{-- With a discount applied the total is rendered
                                 server-side: Alpine's running subtotal knows
                                 nothing about coupons, and showing the
                                 undiscounted figure here would be a lie. --}}
                            @if ($coupon['valid'])
                                <span class="font-display text-xl text-hoor-navy-700" dir="ltr">
                                    {{ \App\Casts\Money::format(max(0, $cart->subtotal() - $coupon['discount'])) }}
                                </span>
                            @else
                                <span class="font-display text-xl text-hoor-navy-700" dir="ltr"
                                      x-text="subtotal">{{ $cart->formattedSubtotal() }}</span>
                            @endif
                        </div>

                        <p class="mt-1 text-xs text-hoor-muted">{{ __('cart.summary.total_note') }}</p>

                        {{--
                            Discount code.

                            A real form post, so it works without JavaScript.
                            The code is all the customer submits — what it is
                            worth is decided server-side on every read.
                        --}}
                        <div class="mt-5 border-t border-hoor-cream-300 pt-5">
                            @if ($coupon['valid'])
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="font-mono text-sm text-hoor-navy-700" dir="ltr">
                                            {{ $coupon['code'] }}
                                        </p>
                                        <p class="text-xs text-hoor-gold-600">
                                            &minus;{{ \App\Casts\Money::format($coupon['discount']) }}
                                        </p>
                                    </div>

                                    <form method="POST" action="{{ route('store.cart.coupon.remove') }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="ghost" size="sm">
                                            {{ __('coupons.cart.remove') }}
                                        </x-ui.button>
                                    </form>
                                </div>
                            @else
                                <form method="POST" action="{{ route('store.cart.coupon.apply') }}">
                                    @csrf

                                    <label for="coupon_code" class="form-label text-xs">
                                        {{ __('coupons.cart.have_code') }}
                                    </label>

                                    <div class="flex gap-2">
                                        <input type="text" id="coupon_code" name="coupon_code" dir="ltr"
                                               value="{{ old('coupon_code') }}"
                                               placeholder="{{ __('coupons.cart.placeholder') }}"
                                               class="form-input py-2 font-mono text-sm">

                                        <x-ui.button type="submit" variant="outline" size="sm">
                                            {{ __('coupons.cart.apply') }}
                                        </x-ui.button>
                                    </div>

                                    {{-- A code that was entered but is not
                                         currently worth anything says why,
                                         rather than silently doing nothing. --}}
                                    @error('coupon_code')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </form>
                            @endif
                        </div>

                        {{-- Checkout is blocked while any line cannot be fulfilled. --}}
                        @php($checkoutUrl = \Illuminate\Support\Facades\Route::has('store.checkout.index')
                            ? route('store.checkout.index')
                            : null)

                        <div class="mt-5">
                            @if (! $cart->isCheckoutReady())
                                <button type="button" class="btn-primary w-full" disabled>
                                    {{ __('cart.checkout') }}
                                </button>
                                <p class="form-error mt-2">{{ __('cart.errors.unavailable_lines') }}</p>
                            @elseif ($checkoutUrl)
                                <a href="{{ $checkoutUrl }}" class="btn-primary w-full">
                                    {{ __('cart.checkout') }}
                                </a>
                            @else
                                <button type="button" class="btn-primary w-full" disabled>
                                    {{ __('cart.checkout') }}
                                </button>
                                <p class="form-hint mt-2">{{ __('common.states.coming_soon') }}</p>
                            @endif
                        </div>

                        <p class="mt-4 flex items-start gap-2 text-xs text-hoor-muted">
                            <span class="mt-1 h-1 w-1 shrink-0 rounded-full bg-hoor-gold-500"
                                  aria-hidden="true"></span>
                            {{ __('cart.summary.cod') }}
                        </p>
                    </div>
                </aside>
            </div>
        @endif
    </div>

    @include('store.partials.cart-page-script')
</x-layouts.store>
