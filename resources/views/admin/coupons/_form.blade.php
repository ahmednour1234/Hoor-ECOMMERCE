{{--
    The coupon form, shared by create and edit.

    Fields that only make sense for one kind of coupon are shown only for that
    kind: a maximum discount on a fixed amount is meaningless, and offering it
    would invite an admin to set a limit that never applies.
--}}
@props(['coupon' => null])

@php
    $type = old('type', $coupon?->type?->value ?? \App\Enums\CouponType::Fixed->value);

    // Amounts are stored in piastres and typed in EGP.
    $toMajor = fn (?int $piastres): ?string => $piastres === null
        ? null
        : rtrim(rtrim(number_format(\App\Casts\Money::toMajor($piastres), 2, '.', ''), '0'), '.');
@endphp

<form method="POST"
      action="{{ $coupon ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}"
      x-data="{ type: @js($type) }"
      class="card space-y-6 p-6">

    @csrf
    @if ($coupon)
        @method('PATCH')
    @endif

    @if ($errors->any())
        <x-ui.alert variant="danger">{{ __('catalog.messages.has_errors') }}</x-ui.alert>
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="code" class="form-label">
                {{ __('coupons.fields.code') }} <span class="text-red-600">*</span>
            </label>

            {{-- Upper-cased as she types, matching how it is stored, so what
                 she sees is what customers will type. --}}
            <input type="text" name="code" id="code" dir="ltr" required
                   value="{{ old('code', $coupon?->code) }}"
                   oninput="this.value = this.value.toUpperCase()"
                   class="form-input font-mono">

            <p class="form-hint">{{ __('coupons.hints.code') }}</p>

            @error('code')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <x-ui.select name="type"
                     :label="__('coupons.fields.type')"
                     :options="\App\Enums\CouponType::options()"
                     :selected="$type"
                     required
                     x-model="type" />
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        @foreach (['ar', 'en'] as $locale)
            @php $name = 'name_'.$locale; @endphp

            <div>
                <label for="{{ $name }}" class="form-label">
                    {{ __('coupons.fields.name') }}
                    <span class="text-xs font-normal text-hoor-muted">({{ strtoupper($locale) }})</span>
                </label>

                <input type="text" name="{{ $name }}" id="{{ $name }}"
                       value="{{ old($name, $coupon?->{$name}) }}"
                       @if ($locale === 'ar') dir="rtl" @else dir="ltr" @endif
                       class="form-input">

                @error($name)<p class="form-error">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label for="value" class="form-label">
                {{ __('coupons.fields.value') }} <span class="text-red-600">*</span>
            </label>

            <div class="relative">
                <input type="number" name="value" id="value" dir="ltr" required
                       :step="type === 'percentage' ? '1' : '0.01'"
                       :min="type === 'percentage' ? '1' : '0.01'"
                       :max="type === 'percentage' ? '100' : '1000000'"
                       value="{{ old('value', $coupon
                           ? ($coupon->type === \App\Enums\CouponType::Percentage
                               ? $coupon->value
                               : $toMajor($coupon->value))
                           : null) }}"
                       class="form-input pe-12">

                <span class="pointer-events-none absolute end-3 top-1/2 -translate-y-1/2 text-xs text-hoor-muted"
                      x-text="type === 'percentage' ? '%' : @js(__('common.currency'))"></span>
            </div>

            <p class="form-hint" x-text="type === 'percentage'
                ? @js(__('coupons.hints.value_percentage'))
                : @js(__('coupons.hints.value_fixed'))"></p>

            @error('value')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        {{-- Only a percentage can run away on a large basket. --}}
        <div x-show="type === 'percentage'" x-cloak>
            <label for="max_discount" class="form-label">{{ __('coupons.fields.max_discount') }}</label>

            <input type="number" name="max_discount" id="max_discount" dir="ltr" step="0.01" min="0.01"
                   value="{{ old('max_discount', $toMajor($coupon?->max_discount)) }}"
                   class="form-input">

            <p class="form-hint">{{ __('coupons.hints.max_discount') }}</p>

            @error('max_discount')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="min_order" class="form-label">{{ __('coupons.fields.min_order') }}</label>

            <input type="number" name="min_order" id="min_order" dir="ltr" step="0.01" min="0"
                   value="{{ old('min_order', $toMajor($coupon?->min_order)) }}"
                   class="form-input">

            <p class="form-hint">{{ __('coupons.hints.min_order') }}</p>

            @error('min_order')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="starts_at" class="form-label">{{ __('coupons.fields.starts_at') }}</label>
            <input type="datetime-local" name="starts_at" id="starts_at" dir="ltr"
                   value="{{ old('starts_at', $coupon?->starts_at?->format('Y-m-d\TH:i')) }}"
                   class="form-input">
            @error('starts_at')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="expires_at" class="form-label">{{ __('coupons.fields.expires_at') }}</label>
            <input type="datetime-local" name="expires_at" id="expires_at" dir="ltr"
                   value="{{ old('expires_at', $coupon?->expires_at?->format('Y-m-d\TH:i')) }}"
                   class="form-input">
            @error('expires_at')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <p class="-mt-3 text-xs text-hoor-muted">{{ __('coupons.hints.schedule') }}</p>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="usage_limit" class="form-label">{{ __('coupons.fields.usage_limit') }}</label>
            <input type="number" name="usage_limit" id="usage_limit" dir="ltr" min="1"
                   value="{{ old('usage_limit', $coupon?->usage_limit) }}" class="form-input">
            <p class="form-hint">{{ __('coupons.hints.usage_limit') }}</p>
            @error('usage_limit')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="per_customer_limit" class="form-label">
                {{ __('coupons.fields.per_customer_limit') }}
            </label>
            <input type="number" name="per_customer_limit" id="per_customer_limit" dir="ltr" min="1"
                   value="{{ old('per_customer_limit', $coupon?->per_customer_limit) }}" class="form-input">
            <p class="form-hint">{{ __('coupons.hints.per_customer_limit') }}</p>
            @error('per_customer_limit')<p class="form-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm text-hoor-navy-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1"
               class="rounded border-hoor-cream-300 text-hoor-navy-500 focus:ring-hoor-denim-500"
               @checked(old('is_active', $coupon?->is_active ?? true))>
        <span>{{ __('coupons.fields.is_active') }}</span>
    </label>

    <div class="flex gap-3 border-t border-hoor-cream-300 pt-5">
        <x-ui.button type="submit" variant="primary">{{ __('common.actions.save') }}</x-ui.button>

        <x-ui.button variant="ghost" :href="route('admin.coupons.index')">
            {{ __('common.actions.cancel') }}
        </x-ui.button>
    </div>
</form>
