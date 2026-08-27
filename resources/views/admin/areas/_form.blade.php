@props(['governorate', 'area'])

@php($isEdit = $area->exists)

<form method="POST"
      action="{{ $isEdit
          ? route('admin.governorates.areas.update', [$governorate, $area])
          : route('admin.governorates.areas.store', $governorate) }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="card max-w-2xl">
        <div class="card-body grid gap-5 sm:grid-cols-2">
            <x-ui.input name="name_en" :label="__('shipping.fields.name_en')"
                        :value="$area->name_en" required dir="ltr" />

            <x-ui.input name="name_ar" :label="__('shipping.fields.name_ar')"
                        :value="$area->name_ar" required dir="rtl" />

            {{-- Blank means inherit, which is why the governorate's own fee is
                 shown as the placeholder rather than a zero. --}}
            <x-ui.input name="shipping_fee" type="number" step="0.01" min="0"
                        :label="__('shipping.fields.shipping_fee').' ('.__('common.currency').')'"
                        :value="$area->shipping_fee !== null
                            ? \App\Casts\Money::toMajor($area->shipping_fee)
                            : ''"
                        :hint="__('shipping.fields.fee_inherit')"
                        :placeholder="\App\Casts\Money::toMajor($governorate->shipping_fee)"
                        dir="ltr" />

            <x-ui.input name="sort_order" type="number" min="0"
                        :label="__('shipping.fields.sort_order')"
                        :value="$area->sort_order ?? 0" dir="ltr" />

            <div class="flex items-center sm:col-span-2">
                <x-ui.checkbox name="is_active" :label="__('shipping.fields.is_active')"
                               :checked="$area->is_active ?? true" />
            </div>
        </div>

        <div class="card-footer flex items-center justify-between">
            <a href="{{ route('admin.governorates.areas.index', $governorate) }}" class="btn-ghost btn-sm">
                {{ __('common.actions.cancel') }}
            </a>
            <x-ui.button type="submit" variant="primary">{{ __('common.actions.save') }}</x-ui.button>
        </div>
    </div>
</form>
