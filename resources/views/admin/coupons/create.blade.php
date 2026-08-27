<x-layouts.admin>
    @section('title', __('coupons.admin.add'))
    @section('page-title', __('coupons.admin.add'))

    <x-admin.page-header :title="__('coupons.admin.add')" />

    @include('admin.coupons._form', ['coupon' => null])
</x-layouts.admin>
