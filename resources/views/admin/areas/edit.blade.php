<x-layouts.admin>
    @section('title', $area->name)
    @section('page-title', __('shipping.areas.edit'))

    <x-admin.page-header
        :title="$area->name"
        :subtitle="$governorate->name" />

    @include('admin.areas._form', ['governorate' => $governorate, 'area' => $area])
</x-layouts.admin>
