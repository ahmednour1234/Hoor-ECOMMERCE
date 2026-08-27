<x-layouts.admin>
    @section('title', __('shipping.areas.create'))
    @section('page-title', __('shipping.areas.create'))

    <x-admin.page-header
        :title="__('shipping.areas.create')"
        :subtitle="$governorate->name" />

    @include('admin.areas._form', ['governorate' => $governorate, 'area' => $area])
</x-layouts.admin>
