<x-layouts.admin>
    @section('title', __('content.slides.title'))
    @section('page-title', __('content.slides.title'))

    <x-admin.page-header :title="__('content.slides.title')">
        <x-slot:actions>
            <x-ui.button variant="primary" size="sm" :href="route('admin.slides.create')">
                {{ __('content.slides.add') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($slides->isEmpty())
        <x-admin.empty-state :title="__('content.slides.empty')" />
    @else
        <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($slides as $slide)
                <li class="card overflow-hidden">
                    <img src="{{ $slide->imageUrl() }}" alt=""
                         class="aspect-[12/5] w-full object-cover {{ $slide->is_active ? '' : 'opacity-40' }}"
                         loading="lazy" decoding="async">

                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-hoor-navy-700">
                                    {{ $slide->headline ?: __('content.slides.image') }}
                                </p>
                                @if ($slide->eyebrow)
                                    <p class="truncate text-xs text-hoor-muted">{{ $slide->eyebrow }}</p>
                                @endif
                            </div>

                            <x-ui.badge :variant="$slide->is_active ? 'success' : 'neutral'">
                                {{ $slide->is_active ? __('content.slides.active') : __('common.actions.edit') }}
                            </x-ui.badge>
                        </div>

                        <div class="mt-4 flex items-center gap-2 border-t border-hoor-cream-300 pt-3">
                            <x-ui.button variant="outline" size="sm" :href="route('admin.slides.edit', $slide)">
                                {{ __('common.actions.edit') }}
                            </x-ui.button>

                            <span class="ms-auto text-xs text-hoor-muted" dir="ltr">#{{ $slide->position }}</span>

                            <form method="POST" action="{{ route('admin.slides.destroy', $slide) }}"
                                  onsubmit="return confirm(@js(__('content.slides.confirm')))">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm"
                                             class="text-red-600 hover:text-red-700">
                                    {{ __('common.actions.delete') }}
                                </x-ui.button>
                            </form>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.admin>
