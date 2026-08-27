<x-layouts.admin>
    @section('title', __('content.banners.add'))
    @section('page-title', __('content.banners.add'))

    <x-admin.page-header :title="__('content.banners.add')" />

    @include('admin.content.banners._form', ['banner' => null])
</x-layouts.admin>
