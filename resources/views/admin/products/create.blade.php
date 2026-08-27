<x-layouts.admin>
    @section('title', __('catalog.products.new'))
    @section('page-title', __('catalog.products.new'))

    <x-admin.page-header
        :title="__('catalog.products.new')"
        :subtitle="__('catalog.products.subtitle')" />

    @include('admin.products._form', [
        'product'    => $product,
        'categories' => $categories,
        'colors'     => $colors,
        'sizes'      => $sizes,
        'statuses'   => $statuses,
    ])
</x-layouts.admin>
