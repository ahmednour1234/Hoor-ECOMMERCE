<x-layouts.store>
    @section('title', __('checkout.title').' — '.__('common.brand'))
    @section('description', __('checkout.subtitle'))

    <div class="hoor-container py-10 lg:py-14"
         x-data="checkoutPage({
             governorates: @js($governorates->map(fn ($g) => [
                 'id'    => $g->id,
                 'areas' => $g->areas->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values(),
             ])->keyBy('id')),
         })">

        <div class="mb-8">
            <h1 class="section-title">{{ __('checkout.title') }}</h1>
            <p class="mt-2 text-sm text-hoor-muted">{{ __('checkout.subtitle') }}</p>
        </div>

        @if ($errors->any() && ! $errors->has('cart'))
            <x-ui.alert variant="danger" class="mb-6">
                {{ __('catalog.messages.has_errors') }}
            </x-ui.alert>
        @endif

        <form method="POST" action="{{ route('store.checkout.store') }}"
              @submit="submitting = true"
              class="lg:flex lg:items-start lg:gap-10">
            @csrf

            <div class="min-w-0 flex-1 space-y-8">

                {{-- Contact --}}
                <section class="card">
                    <div class="card-header">
                        <h2 class="font-display text-lg text-hoor-navy-700">
                            {{ __('checkout.sections.contact') }}
                        </h2>
                    </div>

                    <div class="card-body grid gap-5 sm:grid-cols-2">
                        <x-ui.input name="full_name" :label="__('checkout.fields.full_name')"
                                    :value="old('full_name', auth()->user()?->name)"
                                    autocomplete="name" required class="sm:col-span-2" />

                        <x-ui.input name="phone" type="tel" inputmode="numeric" dir="ltr"
                                    :label="__('checkout.fields.phone')"
                                    :hint="__('checkout.fields.phone_hint')"
                                    :value="old('phone', auth()->user()?->phone)"
                                    autocomplete="tel" placeholder="01xxxxxxxxx" required />

                        <x-ui.input name="phone_alt" type="tel" inputmode="numeric" dir="ltr"
                                    :label="__('checkout.fields.phone_alt')"
                                    :hint="__('checkout.fields.phone_alt_hint')"
                                    :value="old('phone_alt')" placeholder="01xxxxxxxxx" />
                    </div>
                </section>

                {{-- Delivery --}}
                <section class="card">
                    <div class="card-header">
                        <h2 class="font-display text-lg text-hoor-navy-700">
                            {{ __('checkout.sections.delivery') }}
                        </h2>
                    </div>

                    <div class="card-body grid gap-5 sm:grid-cols-2">
                        <x-ui.select name="governorate_id"
                                     :label="__('checkout.fields.governorate')"
                                     :options="$governorates->mapWithKeys(fn ($g) => [$g->id => $g->name])->all()"
                                     :selected="old('governorate_id')"
                                     :placeholder="__('shipping.checkout.choose')"
                                     required
                                     x-model.number="governorateId"
                                     @change="onGovernorateChange()" />

                        {{-- Areas depend on the governorate, so the list is
                             rebuilt whenever that changes. --}}
                        <div>
                            <label for="area_id" class="form-label">
                                {{ __('checkout.fields.area_optional') }}
                            </label>

                            <select name="area_id" id="area_id" class="form-select"
                                    x-model.number="areaId"
                                    @change="refreshQuote()"
                                    :disabled="!areas.length">
                                <option value="">{{ __('shipping.checkout.choose_area') }}</option>
                                <template x-for="area in areas" :key="area.id">
                                    <option :value="area.id" x-text="area.name"></option>
                                </template>
                            </select>

                            @error('area_id')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <x-ui.textarea name="address" rows="3"
                                       :label="__('checkout.fields.address')"
                                       :hint="__('checkout.fields.address_hint')"
                                       :value="old('address')"
                                       autocomplete="street-address" required class="sm:col-span-2" />

                        <x-ui.input name="landmark"
                                    :label="__('checkout.fields.landmark')"
                                    :hint="__('checkout.fields.landmark_hint')"
                                    :value="old('landmark')" class="sm:col-span-2" />
                    </div>
                </section>

                {{-- Notes --}}
                <section class="card">
                    <div class="card-header">
                        <h2 class="font-display text-lg text-hoor-navy-700">
                            {{ __('checkout.sections.notes') }}
                        </h2>
                    </div>

                    <div class="card-body">
                        <x-ui.textarea name="notes" rows="3"
                                       :label="__('checkout.fields.notes')"
                                       :hint="__('checkout.fields.notes_hint')"
                                       :value="old('notes')" />
                    </div>
                </section>

                {{-- Payment: one method, stated plainly rather than as a choice
                     the customer does not have. --}}
                <section class="card">
                    <div class="card-header">
                        <h2 class="font-display text-lg text-hoor-navy-700">
                            {{ __('checkout.sections.payment') }}
                        </h2>
                    </div>

                    <div class="card-body">
                        <input type="hidden" name="payment_method" value="cash_on_delivery">

                        <div class="flex items-start gap-3 rounded-sm border-2 border-hoor-navy-500
                                    bg-hoor-navy-50 p-4">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center
                                         rounded-full bg-hoor-navy-500 text-white" aria-hidden="true">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>

                            <div>
                                <p class="text-sm font-medium text-hoor-navy-700">
                                    {{ __('checkout.payment.cod') }}
                                </p>
                                <p class="mt-1 text-sm text-hoor-muted">
                                    {{ __('checkout.payment.cod_note') }}
                                </p>
                                <p class="mt-1 text-xs text-hoor-muted">
                                    {{ __('checkout.payment.only') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Summary --}}
            <aside class="mt-8 w-full shrink-0 lg:mt-0 lg:w-80">
                <div class="card p-5 lg:sticky lg:top-24">
                    <h2 class="font-display text-lg text-hoor-navy-700">
                        {{ __('checkout.sections.summary') }}
                    </h2>

                    <p class="mt-1 text-xs text-hoor-muted">
                        {{ trans_choice('checkout.summary.items', $cart->totalQuantity(), [
                            'count' => $cart->totalQuantity(),
                        ]) }}
                    </p>

                    {{-- Lines, compactly --}}
                    <ul class="mt-4 space-y-3 border-t border-hoor-cream-300 pt-4">
                        @foreach ($cart->lines as $line)
                            <li class="flex gap-3 text-sm">
                                <span class="h-14 w-11 shrink-0 overflow-hidden rounded-sm bg-hoor-cream-100">
                                    @if ($line->product()->primaryImage)
                                        <img src="{{ $line->product()->primaryImage->url() }}" alt=""
                                             loading="lazy" class="h-full w-full object-cover">
                                    @endif
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-hoor-navy-700">
                                        {{ $line->product()->name }}
                                    </span>
                                    <span class="block text-xs text-hoor-muted">
                                        {{ $line->variant->label() }} &times; {{ $line->quantity }}
                                    </span>
                                </span>

                                <span class="shrink-0 text-hoor-navy-700" dir="ltr">
                                    {{ $line->formattedLineTotal() }}
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Totals: server-computed, refreshed as the destination
                         changes. The browser displays them, never calculates. --}}
                    <dl class="mt-4 space-y-2.5 border-t border-hoor-cream-300 pt-4 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-hoor-muted">{{ __('checkout.summary.subtotal') }}</dt>
                            <dd class="text-hoor-navy-700" dir="ltr"
                                x-text="totals.subtotal">{{ \App\Casts\Money::format($summary['subtotal']) }}</dd>
                        </div>

                        <div class="flex justify-between" x-show="totals.has_discount" x-cloak>
                            <dt class="text-hoor-gold-600">{{ __('checkout.summary.discount') }}</dt>
                            <dd class="text-hoor-gold-600" dir="ltr">
                                &minus;<span x-text="totals.discount"></span>
                            </dd>
                        </div>

                        <div class="flex justify-between">
                            <dt class="text-hoor-muted">{{ __('checkout.summary.shipping') }}</dt>
                            <dd dir="ltr">
                                <span x-show="totals.shipping" x-cloak
                                      class="text-hoor-navy-700" x-text="totals.shipping"></span>
                                <span x-show="!totals.shipping"
                                      class="text-xs text-hoor-muted">
                                    {{ __('checkout.summary.shipping_hint') }}
                                </span>
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex items-baseline justify-between border-t border-hoor-cream-300 pt-4">
                        <span class="font-medium text-hoor-navy-700">{{ __('checkout.summary.total') }}</span>
                        <span class="font-display text-xl text-hoor-navy-700" dir="ltr"
                              x-text="totals.total">{{ \App\Casts\Money::format($summary['total']) }}</span>
                    </div>

                    <p class="mt-2 text-xs text-hoor-muted" x-show="totals.delivery_days" x-cloak
                       x-text="deliveryNote"></p>

                    <button type="submit" class="btn-primary mt-5 w-full" :disabled="submitting"
                            x-text="submitting
                                ? @js(__('checkout.placing'))
                                : @js(__('checkout.place_order'))">
                        {{ __('checkout.place_order') }}
                    </button>

                    <p class="mt-3 text-center text-xs text-hoor-muted">
                        {{ __('checkout.payment.cod_note') }}
                    </p>
                </div>
            </aside>
        </form>
    </div>

    @include('store.partials.checkout-script')
</x-layouts.store>
