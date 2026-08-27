<x-layouts.admin>
    @section('title', $size->name)
    @section('page-title', __('catalog.sizes.edit'))

    <x-admin.page-header :title="$size->name" :subtitle="$size->code" />

    @include('admin.sizes._form', ['size' => $size])
</x-layouts.admin>
