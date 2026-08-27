<x-layouts.admin>
    @section('title', __('content.slides.edit'))
    @section('page-title', __('content.slides.edit'))

    <x-admin.page-header :title="__('content.slides.edit')" />

    @include('admin.content.slides._form', ['slide' => $slide])
</x-layouts.admin>
