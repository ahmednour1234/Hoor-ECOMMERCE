<x-layouts.admin>
    @section('title', __('shipping.areas.title', ['governorate' => $governorate->name]))
    @section('page-title', __('shipping.fields.areas'))

    <x-admin.page-header
        :title="__('shipping.areas.title', ['governorate' => $governorate->name])"
        :subtitle="__('shipping.areas.subtitle')">
        <x-slot:actions>
            <a href="{{ route('admin.governorates.index') }}" class="btn-ghost btn-sm">
                {{ __('shipping.areas.back') }}
            </a>
            <x-ui.button variant="primary"
                         :href="route('admin.governorates.areas.create', $governorate)">
                {{ __('shipping.areas.create') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- The governorate fee is what a blank area fee inherits, so it belongs on
         screen while areas are being edited. --}}
    <x-ui.alert variant="info" class="mb-6">
        {{ $governorate->name }} — {{ __('shipping.fields.shipping_fee') }}:
        <span dir="ltr">{{ \App\Casts\Money::format($governorate->shipping_fee) }}</span>
    </x-ui.alert>

    @if ($areas->isEmpty())
        <x-admin.empty-state :title="__('shipping.areas.none')" :message="__('shipping.areas.none_hint')">
            <x-slot:action>
                <x-ui.button variant="primary"
                             :href="route('admin.governorates.areas.create', $governorate)">
                    {{ __('shipping.areas.create') }}
                </x-ui.button>
            </x-slot:action>
        </x-admin.empty-state>
    @else
        <x-admin.table :headings="[
            __('shipping.fields.name_en'),
            __('shipping.fields.shipping_fee'),
            __('shipping.fields.sort_order'),
            __('shipping.fields.is_active'),
            '',
        ]">
            @foreach ($areas as $area)
                <tr class="transition hover:bg-hoor-cream-100/50">
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.governorates.areas.edit', [$governorate, $area]) }}"
                           class="font-medium text-hoor-navy-700 hover:text-hoor-gold-600">
                            {{ $area->name }}
                        </a>
                        @if ($area->name !== $area->name_en)
                            <p class="text-xs text-hoor-muted" dir="ltr">{{ $area->name_en }}</p>
                        @endif
                    </td>

                    <td class="whitespace-nowrap px-4 py-3">
                        @if ($area->overridesFee())
                            <span class="text-hoor-navy-700" dir="ltr">
                                {{ \App\Casts\Money::format($area->shipping_fee) }}
                            </span>
                        @else
                            <span class="text-hoor-muted" dir="ltr">
                                {{ \App\Casts\Money::format($governorate->shipping_fee) }}
                            </span>
                            <span class="ms-1 text-xs text-hoor-muted">
                                ({{ __('shipping.fields.inherits') }})
                            </span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-hoor-muted" dir="ltr">{{ $area->sort_order }}</td>

                    <td class="px-4 py-3">
                        <x-ui.badge :variant="$area->is_active ? 'success' : 'neutral'">
                            {{ $area->is_active ? __('shipping.states.active') : __('shipping.states.inactive') }}
                        </x-ui.badge>
                    </td>

                    <td class="whitespace-nowrap px-4 py-3 text-end">
                        <form method="POST"
                              action="{{ route('admin.governorates.areas.toggle', [$governorate, $area]) }}"
                              class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="text-sm font-medium text-hoor-navy-600 hover:text-hoor-navy-800">
                                {{ $area->is_active
                                    ? __('shipping.states.deactivate')
                                    : __('shipping.states.activate') }}
                            </button>
                        </form>

                        <span class="mx-2 text-hoor-cream-400">|</span>

                        <a href="{{ route('admin.governorates.areas.edit', [$governorate, $area]) }}"
                           class="text-sm font-medium text-hoor-denim-600 hover:text-hoor-denim-700">
                            {{ __('common.actions.edit') }}
                        </a>

                        @can('delete', $area)
                            <span class="mx-2 text-hoor-cream-400">|</span>
                            <x-admin.delete-form
                                :action="route('admin.governorates.areas.destroy', [$governorate, $area])"
                                :confirm="__('shipping.areas.delete_confirm')" />
                        @endcan
                    </td>
                </tr>
            @endforeach
        </x-admin.table>

        <div class="mt-6">{{ $areas->links() }}</div>
    @endif
</x-layouts.admin>
