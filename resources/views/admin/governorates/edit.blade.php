<x-layouts.admin>
    @section('title', $governorate->name)
    @section('page-title', __('shipping.governorates.edit'))

    <x-admin.page-header :title="$governorate->name" :subtitle="$governorate->code">
        <x-slot:actions>
            <x-ui.button variant="outline" size="sm"
                         :href="route('admin.governorates.areas.index', $governorate)">
                {{ __('shipping.governorates.manage_areas') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @include('admin.governorates._form', ['governorate' => $governorate])
</x-layouts.admin>
