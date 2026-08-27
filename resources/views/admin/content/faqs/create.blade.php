<x-layouts.admin>
    @section('title', __('content.faqs.add'))
    @section('page-title', __('content.faqs.add'))

    <x-admin.page-header :title="__('content.faqs.add')" />

    @include('admin.content.faqs._form', ['faq' => null])
</x-layouts.admin>
