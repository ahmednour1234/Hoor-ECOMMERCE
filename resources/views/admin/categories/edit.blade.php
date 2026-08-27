<x-layouts.admin>
    @section('title', $category->name)
    @section('page-title', __('catalog.categories.edit'))

    <x-admin.page-header :title="$category->name" :subtitle="$category->slug" />

    @include('admin.categories._form', ['category' => $category, 'parents' => $parents])
</x-layouts.admin>
