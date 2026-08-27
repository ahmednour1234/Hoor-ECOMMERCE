<x-layouts.admin>
    @section('title', __('returns.admin.title'))
    @section('page-title', __('returns.admin.title'))

    <x-admin.page-header :title="__('returns.admin.title')" />

    <div class="card mb-6 overflow-hidden">
        <nav class="flex gap-1 overflow-x-auto no-scrollbar px-2" aria-label="{{ __('returns.admin.title') }}">
            @php
                $tabs = collect([['status' => null, 'label' => __('returns.admin.all'), 'count' => $counts['all']]])
                    ->concat(collect($statuses)->map(fn ($s) => [
                        'status' => $s,
                        'label'  => $s->label(),
                        'count'  => $counts[$s->value],
                    ]));
            @endphp

            @foreach ($tabs as $tab)
                @php $isActive = $active === $tab['status']; @endphp

                <a href="{{ route('admin.returns.index', array_filter(['status' => $tab['status']?->value])) }}"
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
    </div>

    @if ($requests->isEmpty())
        <x-admin.empty-state :title="__('returns.admin.empty')" />
    @else
        <x-admin.table :headings="[
            __('returns.customer.number'),
            __('returns.admin.order'),
            __('returns.admin.customer'),
            __('returns.admin.type'),
            __('returns.admin.reason'),
            __('returns.admin.pieces'),
            __('returns.admin.raised'),
            __('orders.fields.status'),
            '',
        ]">
            @foreach ($requests as $request)
                <tr class="transition hover:bg-hoor-cream-100/50">
                    <td class="whitespace-nowrap px-4 py-3">
                        <a href="{{ route('admin.returns.show', $request) }}"
                           class="font-mono text-sm font-medium text-hoor-navy-700 hover:text-hoor-gold-600"
                           dir="ltr">{{ $request->number }}</a>
                    </td>

                    <td class="whitespace-nowrap px-4 py-3">
                        <a href="{{ route('admin.orders.show', $request->order) }}"
                           class="font-mono text-xs text-hoor-denim-600 hover:text-hoor-denim-700" dir="ltr">
                            {{ $request->order?->number }}
                        </a>
                    </td>

                    <td class="px-4 py-3 text-hoor-navy-700">
                        {{ $request->order?->address?->full_name ?? $request->user?->name }}
                    </td>

                    <td class="whitespace-nowrap px-4 py-3 text-hoor-muted">{{ $request->type->label() }}</td>

                    <td class="px-4 py-3 text-xs text-hoor-muted">{{ $request->reason->label() }}</td>

                    <td class="px-4 py-3 text-hoor-navy-700">{{ $request->totalQuantity() }}</td>

                    <td class="whitespace-nowrap px-4 py-3 text-xs text-hoor-muted">
                        {{ $request->created_at->translatedFormat('d M Y') }}
                    </td>

                    <td class="px-4 py-3">
                        <x-ui.badge :variant="$request->status->badge()">
                            {{ $request->status->label() }}
                        </x-ui.badge>
                    </td>

                    <td class="px-4 py-3 text-end">
                        <x-ui.button variant="ghost" size="sm" :href="route('admin.returns.show', $request)">
                            {{ __('returns.admin.view') }}
                        </x-ui.button>
                    </td>
                </tr>
            @endforeach

            <x-slot:footer>{{ $requests->links() }}</x-slot:footer>
        </x-admin.table>
    @endif
</x-layouts.admin>
