<x-layouts.admin>
    @section('title', __('content.banners.title'))
    @section('page-title', __('content.banners.title'))

    <x-admin.page-header :title="__('content.banners.title')">
        <x-slot:actions>
            <x-ui.button variant="primary" size="sm" :href="route('admin.banners.create')">
                {{ __('content.banners.add') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($banners->isEmpty())
        <x-admin.empty-state :title="__('content.banners.empty')" />
    @else
        {{-- Grouped by placement, which is how staff think about them: "what is
             on the announcement bar right now". --}}
        @foreach ($banners as $placement => $group)
            <section class="mb-8">
                <h2 class="mb-3 font-display text-lg text-hoor-navy-700">
                    {{ __('content.banners.placements.'.$placement) }}
                </h2>

                <x-admin.table :headings="[
                    __('content.banners.heading'),
                    __('content.banners.starts_at'),
                    __('content.banners.ends_at'),
                    __('orders.fields.status'),
                    '',
                ]">
                    @foreach ($group as $banner)
                        <tr class="transition hover:bg-hoor-cream-100/50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-hoor-navy-700">
                                    {{ $banner->title ?: '—' }}
                                </p>
                                @if ($banner->body)
                                    <p class="truncate text-xs text-hoor-muted">{{ $banner->body }}</p>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-xs text-hoor-muted">
                                {{ $banner->starts_at?->translatedFormat('d M Y') ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-xs text-hoor-muted">
                                {{ $banner->ends_at?->translatedFormat('d M Y') ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                @if (! $banner->is_active)
                                    <x-ui.badge variant="neutral">{{ __('content.banners.active') }}</x-ui.badge>
                                @elseif ($banner->hasExpired())
                                    <x-ui.badge variant="neutral">{{ __('content.banners.expired') }}</x-ui.badge>
                                @elseif ($banner->isScheduled())
                                    <x-ui.badge variant="denim">{{ __('content.banners.scheduled') }}</x-ui.badge>
                                @else
                                    <x-ui.badge variant="success">{{ __('content.banners.live') }}</x-ui.badge>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-end">
                                <div class="flex items-center justify-end gap-2">
                                    <x-ui.button variant="ghost" size="sm" :href="route('admin.banners.edit', $banner)">
                                        {{ __('common.actions.edit') }}
                                    </x-ui.button>

                                    <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}"
                                          onsubmit="return confirm(@js(__('content.banners.confirm')))">
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
            </section>
        @endforeach
    @endif
</x-layouts.admin>
