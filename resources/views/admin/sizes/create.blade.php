<x-layouts.admin>
    @section('title', __('catalog.sizes.create'))
    @section('page-title', __('catalog.sizes.create'))

    <x-admin.page-header :title="__('catalog.sizes.create')" />

    @include('admin.sizes._form', ['size' => $size])
</x-layouts.admin>
