<x-layouts.admin>
    @section('title', $color->name)
    @section('page-title', __('catalog.colors.edit'))

    <x-admin.page-header :title="$color->name" :subtitle="$color->hex" />

    @include('admin.colors._form', ['color' => $color])
</x-layouts.admin>
