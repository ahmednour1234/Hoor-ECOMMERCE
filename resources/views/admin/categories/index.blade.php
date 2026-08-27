<x-layouts.admin>
    @section('title', __('catalog.categories.title'))
    @section('page-title', __('catalog.categories.title'))

    <x-admin.page-header
        :title="__('catalog.categories.title')"
        :subtitle="__('catalog.categories.subtitle')">
        <x-slot:actions>
            <x-ui.button variant="primary" :href="route('admin.categories.create')">
                {{ __('catalog.categories.create') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->has('category'))
        <x-ui.alert variant="danger" class="mb-6">{{ $errors->first('category') }}</x-ui.alert>
    @endif

    <form method="GET" class="card mb-6 p-4">
        <div class="flex gap-2">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                   placeholder="{{ __('common.actions.search') }}" class="form-input">
            <x-ui.button type="submit" variant="secondary" size="sm">
                {{ __('common.actions.search') }}
            </x-ui.button>
        </div>
    </form>

    @if ($categories->isEmpty())
        <x-admin.empty-state :title="__('catalog.categories.none')">
            <x-slot:action>
                <x-ui.button variant="primary" :href="route('admin.categories.create')">
                    {{ __('catalog.categories.create') }}
                </x-ui.button>
            </x-slot:action>
        </x-admin.empty-state>
    @else
        <x-admin.table :headings="[
            __('catalog.fields.name_en'),
            __('catalog.fields.parent'),
            __('catalog.products.title'),
            __('catalog.fields.sort_order'),
            __('catalog.fields.is_active'),
            '',
        ]">
            @foreach ($categories as $category)
                <tr class="transition hover:bg-hoor-cream-100/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            @if ($category->image)
                                <img src="{{ $category->imageUrl() }}" alt=""
                                     class="h-10 w-10 rounded-sm border border-hoor-cream-300 object-cover">
                            @endif

                            <div>
                                <a href="{{ route('admin.categories.edit', $category) }}"
                                   class="font-medium text-hoor-navy-700 hover:text-hoor-gold-600">
                                    {{ $category->name }}
                                </a>
                                <p class="text-xs text-hoor-muted" dir="ltr">{{ $category->slug }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-4 py-3 text-hoor-muted">
                        {{ $category->parent?->name ?? __('catalog.fields.no_parent') }}
                    </td>

                    <td class="px-4 py-3 text-hoor-muted">
                        {{ __('catalog.categories.products_count', ['count' => $category->products_count]) }}
                        @if ($category->children_count > 0)
                            <span class="block text-xs">
                                {{ __('catalog.categories.children_count', ['count' => $category->children_count]) }}
                            </span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-hoor-muted" dir="ltr">{{ $category->sort_order }}</td>

                    <td class="px-4 py-3">
                        <x-ui.badge :variant="$category->is_active ? 'success' : 'neutral'">
                            {{ $category->is_active ? __('common.states.active') : __('common.states.inactive') }}
                        </x-ui.badge>
                    </td>

                    <td class="whitespace-nowrap px-4 py-3 text-end">
                        <a href="{{ route('admin.categories.edit', $category) }}"
                           class="text-sm font-medium text-hoor-denim-600 hover:text-hoor-denim-700">
                            {{ __('common.actions.edit') }}
                        </a>

                        @can('delete', $category)
                            <span class="mx-2 text-hoor-cream-400">|</span>
                            <x-admin.delete-form
                                :action="route('admin.categories.destroy', $category)"
                                :confirm="__('catalog.categories.delete_confirm')" />
                        @endcan
                    </td>
                </tr>
            @endforeach
        </x-admin.table>

        <div class="mt-6">{{ $categories->links() }}</div>
    @endif
</x-layouts.admin>
