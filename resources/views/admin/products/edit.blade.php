<x-layouts.admin>
    @section('title', $product->name)
    @section('page-title', __('catalog.products.edit'))

    <x-admin.page-header :title="$product->name" :subtitle="$product->slug">
        <x-slot:actions>
            @can('delete', $product)
                <x-admin.delete-form
                    :action="route('admin.products.destroy', $product)"
                    :confirm="__('catalog.products.delete_confirm')"
                    class="btn-ghost btn-sm !text-red-600" />
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    @include('admin.products._form', [
        'product'    => $product,
        'categories' => $categories,
        'colors'     => $colors,
        'sizes'      => $sizes,
        'statuses'   => $statuses,
    ])
</x-layouts.admin>
