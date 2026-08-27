<x-layouts.admin>
    @section('title', __('shipping.governorates.title'))
    @section('page-title', __('shipping.governorates.title'))

    <x-admin.page-header
        :title="__('shipping.governorates.title')"
        :subtitle="__('shipping.governorates.subtitle')">
        <x-slot:actions>
            <x-ui.button variant="primary" :href="route('admin.governorates.create')">
                {{ __('shipping.governorates.create') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->has('governorate'))
        <x-ui.alert variant="danger" class="mb-6">{{ $errors->first('governorate') }}</x-ui.alert>
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

    @if ($governorates->isEmpty())
        <x-admin.empty-state :title="__('shipping.governorates.none')">
            <x-slot:action>
                <x-ui.button variant="primary" :href="route('admin.governorates.create')">
                    {{ __('shipping.governorates.create') }}
                </x-ui.button>
            </x-slot:action>
        </x-admin.empty-state>
    @else
        <x-admin.table :headings="[
            __('shipping.fields.code'),
            __('shipping.fields.name_en'),
            __('shipping.fields.shipping_fee'),
            __('shipping.fields.delivery_days'),
            __('shipping.fields.areas'),
            __('shipping.fields.is_active'),
            '',
        ]">
            @foreach ($governorates as $governorate)
                <tr class="transition hover:bg-hoor-cream-100/50">
                    <td class="px-4 py-3">
                        <span class="inline-flex h-8 items-center rounded-sm border border-hoor-cream-300
                                     px-2 font-mono text-xs text-hoor-navy-700" dir="ltr">
                            {{ $governorate->code }}
                        </span>
                    </td>

                    <td class="px-4 py-3">
                        <a href="{{ route('admin.governorates.edit', $governorate) }}"
                           class="font-medium text-hoor-navy-700 hover:text-hoor-gold-600">
                            {{ $governorate->name }}
                        </a>
                        @if ($governorate->name !== $governorate->name_en)
                            <p class="text-xs text-hoor-muted" dir="ltr">{{ $governorate->name_en }}</p>
                        @endif
                    </td>

                    <td class="whitespace-nowrap px-4 py-3 text-hoor-navy-700" dir="ltr">
                        {{ \App\Casts\Money::format($governorate->shipping_fee) }}
                    </td>

                    <td class="whitespace-nowrap px-4 py-3 text-hoor-muted" dir="ltr">
                        {{ $governorate->deliveryWindow() }}
                    </td>

                    <td class="px-4 py-3">
                        <a href="{{ route('admin.governorates.areas.index', $governorate) }}"
                           class="text-sm text-hoor-denim-600 hover:text-hoor-denim-700">
                            {{ trans_choice('shipping.governorates.areas_link', $governorate->areas_count, [
                                'count' => $governorate->areas_count,
                            ]) }}
                        </a>

                        {{-- Flag inactive areas, which are invisible at checkout. --}}
                        @if ($governorate->areas_count > $governorate->active_areas_count)
                            <p class="text-xs text-hoor-muted">
                                {{ $governorate->active_areas_count }} {{ __('shipping.states.active') }}
                            </p>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <x-ui.badge :variant="$governorate->is_active ? 'success' : 'neutral'">
                            {{ $governorate->is_active ? __('shipping.states.active') : __('shipping.states.inactive') }}
                        </x-ui.badge>
                    </td>

                    <td class="whitespace-nowrap px-4 py-3 text-end">
                        {{-- Toggling is the everyday action, so it sits first. --}}
                        <form method="POST" action="{{ route('admin.governorates.toggle', $governorate) }}"
                              class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="text-sm font-medium text-hoor-navy-600 hover:text-hoor-navy-800">
                                {{ $governorate->is_active
                                    ? __('shipping.states.deactivate')
                                    : __('shipping.states.activate') }}
                            </button>
                        </form>

                        <span class="mx-2 text-hoor-cream-400">|</span>

                        <a href="{{ route('admin.governorates.edit', $governorate) }}"
                           class="text-sm font-medium text-hoor-denim-600 hover:text-hoor-denim-700">
                            {{ __('common.actions.edit') }}
                        </a>

                        {{-- Deletion is only offered when nothing depends on it. --}}
                        @if ($governorate->areas_count === 0)
                            @can('delete', $governorate)
                                <span class="mx-2 text-hoor-cream-400">|</span>
                                <x-admin.delete-form
                                    :action="route('admin.governorates.destroy', $governorate)"
                                    :confirm="__('shipping.governorates.delete_confirm')" />
                            @endcan
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-admin.table>

        <div class="mt-6">{{ $governorates->links() }}</div>
    @endif
</x-layouts.admin>
