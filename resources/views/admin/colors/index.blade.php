<x-layouts.admin>
    @section('title', __('catalog.colors.title'))
    @section('page-title', __('catalog.colors.title'))

    <x-admin.page-header
        :title="__('catalog.colors.title')"
        :subtitle="__('catalog.colors.subtitle')">
        <x-slot:actions>
            <x-ui.button variant="primary" :href="route('admin.colors.create')">
                {{ __('catalog.colors.create') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->has('color'))
        <x-ui.alert variant="danger" class="mb-6">{{ $errors->first('color') }}</x-ui.alert>
    @endif

    @if ($colors->isEmpty())
        <x-admin.empty-state :title="__('catalog.colors.none')">
            <x-slot:action>
                <x-ui.button variant="primary" :href="route('admin.colors.create')">
                    {{ __('catalog.colors.create') }}
                </x-ui.button>
            </x-slot:action>
        </x-admin.empty-state>
    @else
        <x-admin.table :headings="[
            '',
            __('catalog.fields.name_en'),
            __('catalog.fields.hex'),
            __('catalog.variants.title'),
            __('catalog.fields.sort_order'),
            __('catalog.fields.is_active'),
            '',
        ]">
            @foreach ($colors as $color)
                <tr class="transition hover:bg-hoor-cream-100/50">
                    <td class="px-4 py-3">
                        <span class="block h-8 w-8 rounded-full border border-hoor-cream-300"
                              style="background-color: {{ $color->hex }}"
                              title="{{ $color->hex }}"></span>
                    </td>

                    <td class="px-4 py-3">
                        <a href="{{ route('admin.colors.edit', $color) }}"
                           class="font-medium text-hoor-navy-700 hover:text-hoor-gold-600">
                            {{ $color->name }}
                        </a>
                        <p class="text-xs text-hoor-muted" dir="ltr">{{ $color->slug }}</p>
                    </td>

                    <td class="px-4 py-3 font-mono text-xs text-hoor-muted" dir="ltr">{{ $color->hex }}</td>

                    <td class="px-4 py-3 text-hoor-muted">
                        {{ __('catalog.colors.variants_count', ['count' => $color->variants_count]) }}
                    </td>

                    <td class="px-4 py-3 text-hoor-muted" dir="ltr">{{ $color->sort_order }}</td>

                    <td class="px-4 py-3">
                        <x-ui.badge :variant="$color->is_active ? 'success' : 'neutral'">
                            {{ $color->is_active ? __('common.states.active') : __('common.states.inactive') }}
                        </x-ui.badge>
                    </td>

                    <td class="whitespace-nowrap px-4 py-3 text-end">
                        <a href="{{ route('admin.colors.edit', $color) }}"
                           class="text-sm font-medium text-hoor-denim-600 hover:text-hoor-denim-700">
                            {{ __('common.actions.edit') }}
                        </a>

                        {{-- Colours attached to variants are deactivated, not deleted. --}}
                        @if ($color->variants_count === 0)
                            <span class="mx-2 text-hoor-cream-400">|</span>
                            <x-admin.delete-form
                                :action="route('admin.colors.destroy', $color)"
                                :confirm="__('catalog.colors.delete_confirm')" />
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-admin.table>

        <div class="mt-6">{{ $colors->links() }}</div>
    @endif
</x-layouts.admin>
