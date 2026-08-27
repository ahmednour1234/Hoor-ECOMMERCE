<x-layouts.account :title="__('account.addresses.title')">

    <div class="mb-6">
        <x-ui.button variant="primary" size="sm" :href="route('store.account.addresses.create')">
            {{ __('account.addresses.add') }}
        </x-ui.button>
    </div>

    @if ($addresses->isEmpty())
        <div class="card p-10 text-center">
            <p class="text-hoor-muted">{{ __('account.addresses.empty') }}</p>
        </div>
    @else
        <ul class="grid gap-4 sm:grid-cols-2">
            @foreach ($addresses as $address)
                <li class="card flex flex-col p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            @if ($address->label)
                                <p class="font-medium text-hoor-navy-700">{{ $address->label }}</p>
                            @endif

                            <p class="text-sm text-hoor-navy-700">{{ $address->full_name }}</p>
                        </div>

                        @if ($address->is_default)
                            <x-ui.badge variant="gold">{{ __('account.addresses.default') }}</x-ui.badge>
                        @endif
                    </div>

                    <address class="mt-3 flex-1 space-y-1 text-sm not-italic text-hoor-muted">
                        <p dir="ltr">{{ $address->phone }}</p>
                        <p>{{ $address->address }}</p>
                        <p>{{ collect([$address->area?->name, $address->governorate?->name])->filter()->join('، ') }}</p>

                        @if ($address->landmark)
                            <p class="text-xs">{{ $address->landmark }}</p>
                        @endif
                    </address>

                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-hoor-cream-300 pt-4">
                        <x-ui.button variant="outline" size="sm"
                                     :href="route('store.account.addresses.edit', $address)">
                            {{ __('common.actions.edit') }}
                        </x-ui.button>

                        @unless ($address->is_default)
                            <form method="POST" action="{{ route('store.account.addresses.default', $address) }}">
                                @csrf
                                @method('PATCH')
                                <x-ui.button type="submit" variant="ghost" size="sm">
                                    {{ __('account.addresses.make_default') }}
                                </x-ui.button>
                            </form>
                        @endunless

                        <form method="POST" action="{{ route('store.account.addresses.destroy', $address) }}"
                              onsubmit="return confirm(@js(__('account.addresses.confirm_delete')))"
                              class="ms-auto">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="ghost" size="sm"
                                         class="text-red-600 hover:text-red-700">
                                {{ __('common.actions.delete') }}
                            </x-ui.button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.account>
