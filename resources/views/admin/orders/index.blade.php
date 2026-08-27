<x-layouts.admin>
    @section('title', __('orders.admin.title'))
    @section('page-title', __('orders.admin.title'))

    <x-admin.page-header :title="__('orders.admin.title')" />

    {{--
        Status tabs.

        These are the "All orders" plus per-status pages, as links rather than
        separate templates: each carries its own ?status= so the URL stays
        shareable, and its count comes from one grouped query.
    --}}
    <div class="card mb-6 overflow-hidden">
        <nav class="flex gap-1 overflow-x-auto no-scrollbar border-b border-hoor-cream-300 px-2"
             aria-label="{{ __('orders.admin.title') }}">
            @php
                $tabs = collect([['status' => null, 'label' => __('orders.admin.all'), 'count' => $counts['all']]])
                    ->concat(collect($statuses)->map(fn ($s) => [
                        'status' => $s,
                        'label'  => $s->label(),
                        'count'  => $counts[$s->value],
                    ]));
            @endphp

            @foreach ($tabs as $tab)
                @php $isActive = $active === $tab['status']; @endphp

                <a href="{{ route('admin.orders.index', array_filter([
                        'status' => $tab['status']?->value,
                        'search' => $filters['search'] ?? null,
                    ])) }}"
                   @if ($isActive) aria-current="page" @endif
                   class="flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition
                          {{ $isActive
                              ? 'border-hoor-navy-500 text-hoor-navy-700'
                              : 'border-transparent text-hoor-muted hover:border-hoor-cream-400 hover:text-hoor-navy-600' }}">
                    {{ $tab['label'] }}

                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold
                                 {{ $isActive ? 'bg-hoor-navy-700 text-hoor-cream-50' : 'bg-hoor-cream-200 text-hoor-muted' }}">
                        {{ $tab['count'] }}
                    </span>
                </a>
            @endforeach
        </nav>

        {{-- Filters keep the active status, so narrowing a tab does not leave it. --}}
        <form method="GET" class="flex flex-wrap items-end gap-3 p-4">
            @if ($active !== null)
                <input type="hidden" name="status" value="{{ $active->value }}">
            @endif

            <div class="min-w-56 flex-1">
                <label for="search" class="form-label">{{ __('orders.admin.search') }}</label>
                <input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                       placeholder="{{ __('orders.admin.search_hint') }}" class="form-input">
            </div>

            <div>
                <label for="from" class="form-label">{{ __('orders.admin.from') }}</label>
                <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-input">
            </div>

            <div>
                <label for="to" class="form-label">{{ __('orders.admin.to') }}</label>
                <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-input">
            </div>

            <div class="flex gap-2">
                <x-ui.button type="submit" variant="secondary" size="sm">
                    {{ __('orders.admin.filter') }}
                </x-ui.button>

                <x-ui.button variant="ghost" size="sm" :href="route('admin.orders.index')">
                    {{ __('orders.admin.reset') }}
                </x-ui.button>
            </div>
        </form>
    </div>

    @if ($orders->isEmpty())
        <x-admin.empty-state :title="__('orders.admin.empty')" />
    @else
        <x-admin.table :headings="[
            __('orders.admin.number'),
            __('orders.admin.customer'),
            __('orders.admin.placed'),
            __('orders.admin.items'),
            __('orders.admin.total'),
            __('orders.fields.status'),
            '',
        ]">
            @foreach ($orders as $order)
                <tr class="transition hover:bg-hoor-cream-100/50">
                    <td class="whitespace-nowrap px-4 py-3">
                        <a href="{{ route('admin.orders.show', $order) }}"
                           class="font-mono text-sm font-medium text-hoor-navy-700 hover:text-hoor-gold-600"
                           dir="ltr">
                            {{ $order->number }}
                        </a>
                    </td>

                    <td class="px-4 py-3">
                        <p class="font-medium text-hoor-navy-700">{{ $order->address?->full_name }}</p>
                        <p class="text-xs text-hoor-muted" dir="ltr">{{ $order->address?->phone }}</p>
                    </td>

                    <td class="whitespace-nowrap px-4 py-3 text-hoor-muted">
                        {{ $order->created_at->translatedFormat('d M Y — H:i') }}
                    </td>

                    <td class="px-4 py-3 text-hoor-navy-700">{{ $order->items_count }}</td>

                    <td class="whitespace-nowrap px-4 py-3 font-medium text-hoor-navy-700" dir="ltr">
                        {{ \App\Casts\Money::format($order->total) }}
                    </td>

                    <td class="px-4 py-3">
                        <x-ui.badge :variant="$order->status->badge()">{{ $order->status->label() }}</x-ui.badge>
                    </td>

                    <td class="px-4 py-3 text-end">
                        <x-ui.button variant="ghost" size="sm" :href="route('admin.orders.show', $order)">
                            {{ __('orders.admin.view') }}
                        </x-ui.button>
                    </td>
                </tr>
            @endforeach

            <x-slot:footer>
                {{ $orders->links() }}
            </x-slot:footer>
        </x-admin.table>
    @endif
</x-layouts.admin>
