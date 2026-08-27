<x-layouts.admin>
    @section('title', __('content.banners.edit'))
    @section('page-title', __('content.banners.edit'))

    <x-admin.page-header :title="__('content.banners.edit')" />

    @include('admin.content.banners._form', ['banner' => $banner])
</x-layouts.admin>
