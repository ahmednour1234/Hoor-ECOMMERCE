<x-layouts.admin>
    @section('title', __('catalog.colors.create'))
    @section('page-title', __('catalog.colors.create'))

    <x-admin.page-header :title="__('catalog.colors.create')" />

    @include('admin.colors._form', ['color' => $color])
</x-layouts.admin>
