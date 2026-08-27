<x-layouts.admin>
    @section('title', __('catalog.sizes.title'))
    @section('page-title', __('catalog.sizes.title'))

    <x-admin.page-header
        :title="__('catalog.sizes.title')"
        :subtitle="__('catalog.sizes.subtitle')">
        <x-slot:actions>
            <x-ui.button variant="primary" :href="route('admin.sizes.create')">
                {{ __('catalog.sizes.create') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->has('size'))
        <x-ui.alert variant="danger" class="mb-6">{{ $errors->first('size') }}</x-ui.alert>
    @endif

    @if ($sizes->isEmpty())
        <x-admin.empty-state :title="__('catalog.sizes.none')">
            <x-slot:action>
                <x-ui.button variant="primary" :href="route('admin.sizes.create')">
                    {{ __('catalog.sizes.create') }}
                </x-ui.button>
            </x-slot:action>
        </x-admin.empty-state>
    @else
        <x-admin.table :headings="[
            __('catalog.fields.code'),
            __('catalog.fields.name_en'),
            __('catalog.variants.title'),
            __('catalog.fields.sort_order'),
            __('catalog.fields.is_active'),
            '',
        ]">
            @foreach ($sizes as $size)
                <tr class="transition hover:bg-hoor-cream-100/50">
                    <td class="px-4 py-3">
                        <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-sm
                                     border border-hoor-cream-300 px-2 font-mono text-sm text-hoor-navy-700"
                              dir="ltr">{{ $size->code }}</span>
                    </td>

                    <td class="px-4 py-3">
                        <a href="{{ route('admin.sizes.edit', $size) }}"
                           class="font-medium text-hoor-navy-700 hover:text-hoor-gold-600">
                            {{ $size->name }}
                        </a>
                    </td>

                    <td class="px-4 py-3 text-hoor-muted">
                        {{ __('catalog.sizes.variants_count', ['count' => $size->variants_count]) }}
                    </td>

                    <td class="px-4 py-3 text-hoor-muted" dir="ltr">{{ $size->sort_order }}</td>

                    <td class="px-4 py-3">
                        <x-ui.badge :variant="$size->is_active ? 'success' : 'neutral'">
                            {{ $size->is_active ? __('common.states.active') : __('common.states.inactive') }}
                        </x-ui.badge>
                    </td>

                    <td class="whitespace-nowrap px-4 py-3 text-end">
                        <a href="{{ route('admin.sizes.edit', $size) }}"
                           class="text-sm font-medium text-hoor-denim-600 hover:text-hoor-denim-700">
                            {{ __('common.actions.edit') }}
                        </a>

                        @if ($size->variants_count === 0)
                            <span class="mx-2 text-hoor-cream-400">|</span>
                            <x-admin.delete-form
                                :action="route('admin.sizes.destroy', $size)"
                                :confirm="__('catalog.sizes.delete_confirm')" />
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-admin.table>

        <div class="mt-6">{{ $sizes->links() }}</div>
    @endif
</x-layouts.admin>
