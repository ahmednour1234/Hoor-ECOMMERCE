<x-layouts.admin>
    @section('title', __('content.subscribers.title'))
    @section('page-title', __('content.subscribers.title'))

    <x-admin.page-header :title="__('content.subscribers.title')">
        <x-slot:subtitle>
            {{ __('content.subscribers.total', ['count' => $total]) }}
        </x-slot:subtitle>

        <x-slot:actions>
            @if ($total > 0)
                <x-ui.button variant="outline" size="sm" :href="route('admin.newsletter.export')">
                    {{ __('content.subscribers.export') }}
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if ($subscribers->isEmpty())
        <x-admin.empty-state :title="__('content.subscribers.empty')" />
    @else
        <x-admin.table :headings="[
            __('content.subscribers.email'),
            __('content.subscribers.joined'),
            __('content.subscribers.status'),
        ]">
            @foreach ($subscribers as $subscriber)
                <tr class="transition hover:bg-hoor-cream-100/50">
                    <td class="px-4 py-3 text-hoor-navy-700" dir="ltr">{{ $subscriber->email }}</td>

                    <td class="whitespace-nowrap px-4 py-3 text-xs text-hoor-muted">
                        {{ $subscriber->created_at->translatedFormat('d M Y') }}
                    </td>

                    <td class="px-4 py-3">
                        <x-ui.badge :variant="$subscriber->isSubscribed() ? 'success' : 'neutral'">
                            {{ $subscriber->isSubscribed()
                                ? __('content.subscribers.subscribed')
                                : __('content.subscribers.unsubscribed') }}
                        </x-ui.badge>
                    </td>
                </tr>
            @endforeach

            <x-slot:footer>{{ $subscribers->links() }}</x-slot:footer>
        </x-admin.table>
    @endif
</x-layouts.admin>
