<x-layouts.admin>
    @section('title', __('content.faqs.edit'))
    @section('page-title', __('content.faqs.edit'))

    <x-admin.page-header :title="__('content.faqs.edit')" />

    @include('admin.content.faqs._form', ['faq' => $faq])
</x-layouts.admin>
