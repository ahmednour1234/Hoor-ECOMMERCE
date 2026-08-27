<x-layouts.admin>
    @section('title', __('content.slides.add'))
    @section('page-title', __('content.slides.add'))

    <x-admin.page-header :title="__('content.slides.add')" />

    @include('admin.content.slides._form', ['slide' => null])
</x-layouts.admin>
