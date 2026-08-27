<x-layouts.admin>
    @section('title', __('coupons.admin.edit'))
    @section('page-title', $coupon->code)

    <x-admin.page-header :title="__('coupons.admin.edit')">
        <x-slot:subtitle>
            <span class="font-mono" dir="ltr">{{ $coupon->code }}</span>
        </x-slot:subtitle>

        <x-slot:actions>
            <x-ui.button variant="ghost" size="sm" :href="route('admin.coupons.show', $coupon)">
                {{ __('coupons.admin.view') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @include('admin.coupons._form', ['coupon' => $coupon])
</x-layouts.admin>
