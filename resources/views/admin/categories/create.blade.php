<x-layouts.admin>
    @section('title', __('catalog.categories.create'))
    @section('page-title', __('catalog.categories.create'))

    <x-admin.page-header :title="__('catalog.categories.create')" />

    @include('admin.categories._form', ['category' => $category, 'parents' => $parents])
</x-layouts.admin>
