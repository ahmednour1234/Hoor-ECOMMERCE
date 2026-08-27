<x-layouts.admin>
    @section('title', __('shipping.governorates.create'))
    @section('page-title', __('shipping.governorates.create'))

    <x-admin.page-header :title="__('shipping.governorates.create')" />

    @include('admin.governorates._form', ['governorate' => $governorate])
</x-layouts.admin>
