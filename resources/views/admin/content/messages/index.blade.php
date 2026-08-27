<x-layouts.admin>
    @section('title', __('content.messages.title'))
    @section('page-title', __('content.messages.title'))

    <x-admin.page-header :title="__('content.messages.title')" />

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="card mb-6 overflow-hidden">
        <nav class="flex gap-1 px-2" aria-label="{{ __('content.messages.title') }}">
            @foreach ([
                ['unread' => false, 'label' => __('content.messages.all'), 'count' => null],
                ['unread' => true,  'label' => __('content.messages.unread'), 'count' => $unreadCount],
            ] as $tab)
                @php $isActive = $unreadOnly === $tab['unread']; @endphp

                <a href="{{ route('admin.messages.index', array_filter(['unread' => $tab['unread'] ? 1 : null])) }}"
                   @if ($isActive) aria-current="page" @endif
                   class="flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition
                          {{ $isActive
                              ? 'border-hoor-navy-500 text-hoor-navy-700'
                              : 'border-transparent text-hoor-muted hover:border-hoor-cream-400 hover:text-hoor-navy-600' }}">
                    {{ $tab['label'] }}

                    @if ($tab['count'] !== null)
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold
                                     {{ $isActive ? 'bg-hoor-navy-700 text-hoor-cream-50' : 'bg-hoor-cream-200 text-hoor-muted' }}">
                            {{ $tab['count'] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>

    @if ($messages->isEmpty())
        <x-admin.empty-state :title="__('content.messages.empty')" />
    @else
        <x-admin.table :headings="[
            __('content.messages.from'),
            __('content.messages.subject'),
            __('content.messages.received'),
            '',
        ]">
            @foreach ($messages as $message)
                <tr class="transition hover:bg-hoor-cream-100/50 {{ $message->isRead() ? '' : 'bg-hoor-cream-100/40' }}">
                    <td class="px-4 py-3">
                        <p class="{{ $message->isRead() ? 'text-hoor-navy-700' : 'font-semibold text-hoor-navy-700' }}">
                            {{ $message->name }}
                        </p>
                        <p class="text-xs text-hoor-muted" dir="ltr">
                            {{ $message->email ?: $message->phone }}
                        </p>
                    </td>

                    <td class="px-4 py-3">
                        <p class="text-hoor-navy-700">{{ $message->subject ?: '—' }}</p>
                        <p class="truncate text-xs text-hoor-muted">{{ $message->excerpt() }}</p>
                    </td>

                    <td class="whitespace-nowrap px-4 py-3 text-xs text-hoor-muted">
                        {{ $message->created_at->translatedFormat('d M Y — H:i') }}
                    </td>

                    <td class="px-4 py-3 text-end">
                        <x-ui.button variant="ghost" size="sm" :href="route('admin.messages.show', $message)">
                            {{ __('content.messages.view') }}
                        </x-ui.button>
                    </td>
                </tr>
            @endforeach

            <x-slot:footer>{{ $messages->links() }}</x-slot:footer>
        </x-admin.table>
    @endif
</x-layouts.admin>
