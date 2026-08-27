@props(['governorate'])

@php($isEdit = $governorate->exists)

<form method="POST"
      action="{{ $isEdit ? route('admin.governorates.update', $governorate) : route('admin.governorates.store') }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="card max-w-3xl">
        <div class="card-body grid gap-5 sm:grid-cols-2">
            <x-ui.input name="name_en" :label="__('shipping.fields.name_en')"
                        :value="$governorate->name_en" required dir="ltr" />

            <x-ui.input name="name_ar" :label="__('shipping.fields.name_ar')"
                        :value="$governorate->name_ar" required dir="rtl" />

            <x-ui.input name="code" :label="__('shipping.fields.code')"
                        :value="$governorate->code" :hint="__('shipping.fields.code_hint')"
                        required dir="ltr" class="font-mono" />

            {{-- Entered in EGP; the controller converts to piastres. --}}
            <x-ui.input name="shipping_fee" type="number" step="0.01" min="0"
                        :label="__('shipping.fields.shipping_fee').' ('.__('common.currency').')'"
                        :value="$governorate->shipping_fee !== null
                            ? \App\Casts\Money::toMajor($governorate->shipping_fee)
                            : ''"
                        required dir="ltr" />

            <div class="sm:col-span-2">
                <span class="form-label">{{ __('shipping.fields.delivery_days') }}</span>

                <div class="flex items-center gap-3">
                    <x-ui.input name="delivery_days_min" type="number" min="1" max="60"
                                :label="__('shipping.fields.days_min')"
                                :value="$governorate->delivery_days_min ?? 2" required dir="ltr" />

                    <x-ui.input name="delivery_days_max" type="number" min="1" max="60"
                                :label="__('shipping.fields.days_max')"
                                :value="$governorate->delivery_days_max ?? 5" required dir="ltr" />
                </div>
            </div>

            <x-ui.input name="sort_order" type="number" min="0"
                        :label="__('shipping.fields.sort_order')"
                        :value="$governorate->sort_order ?? 0" dir="ltr" />

            <div class="flex items-center">
                <x-ui.checkbox name="is_active" :label="__('shipping.fields.is_active')"
                               :checked="$governorate->is_active ?? true" />
            </div>
        </div>

        <div class="card-footer flex items-center justify-between">
            <a href="{{ route('admin.governorates.index') }}" class="btn-ghost btn-sm">
                {{ __('common.actions.cancel') }}
            </a>
            <x-ui.button type="submit" variant="primary">{{ __('common.actions.save') }}</x-ui.button>
        </div>
    </div>
</form>
