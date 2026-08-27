<x-layouts.admin>
    @section('title', __('content.faqs.title'))
    @section('page-title', __('content.faqs.title'))

    <x-admin.page-header :title="__('content.faqs.title')">
        <x-slot:actions>
            <x-ui.button variant="primary" size="sm" :href="route('admin.faqs.create')">
                {{ __('content.faqs.add') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($faqs->isEmpty())
        <x-admin.empty-state :title="__('content.faqs.empty')" />
    @else
        <x-admin.table :headings="[
            __('content.faqs.position'),
            __('content.faqs.question'),
            __('orders.fields.status'),
            '',
        ]">
            @foreach ($faqs as $faq)
                <tr class="transition hover:bg-hoor-cream-100/50">
                    <td class="whitespace-nowrap px-4 py-3 text-xs text-hoor-muted" dir="ltr">
                        {{ $faq->position }}
                    </td>

                    <td class="px-4 py-3">
                        <p class="text-hoor-navy-700">{{ $faq->question_en }}</p>
                        <p class="text-xs text-hoor-muted" dir="rtl">{{ $faq->question_ar }}</p>
                    </td>

                    <td class="px-4 py-3">
                        <x-ui.badge :variant="$faq->is_active ? 'success' : 'neutral'">
                            {{ $faq->is_active ? __('content.faqs.active') : __('coupons.status.inactive') }}
                        </x-ui.badge>
                    </td>

                    <td class="px-4 py-3 text-end">
                        <div class="flex items-center justify-end gap-2">
                            <x-ui.button variant="ghost" size="sm" :href="route('admin.faqs.edit', $faq)">
                                {{ __('common.actions.edit') }}
                            </x-ui.button>

                            <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}"
                                  onsubmit="return confirm({{ Illuminate\Support\Js::from(__('content.faqs.confirm')) }})">
                                @csrf
                                @method('DELETE')

                                <x-ui.button type="submit" variant="ghost" size="sm"
                                             class="text-red-600 hover:text-red-700">
                                    {{ __('common.actions.delete') }}
                                </x-ui.button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-admin.table>
    @endif
</x-layouts.admin>
