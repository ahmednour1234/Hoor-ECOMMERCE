<x-layouts.account :title="__('returns.customer.title')">

    @if ($requests->isEmpty())
        <div class="card p-10 text-center">
            <p class="text-hoor-muted">{{ __('returns.customer.empty') }}</p>

            <x-ui.button variant="ghost" size="sm" class="mt-6" :href="route('store.account.orders.index')">
                {{ __('account.orders.title') }}
            </x-ui.button>
        </div>
    @else
        <ul class="space-y-4">
            @foreach ($requests as $request)
                <li class="card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <a href="{{ route('store.account.returns.show', $request) }}"
                               class="font-mono text-sm font-medium text-hoor-navy-700 hover:text-hoor-gold-600"
                               dir="ltr">
                                {{ $request->number }}
                            </a>

                            <p class="mt-1 text-xs text-hoor-muted">
                                {{ $request->type->label() }}
                                · {{ __('returns.customer.order') }}
                                <span dir="ltr">{{ $request->order?->number }}</span>
                                · {{ $request->created_at->translatedFormat('d M Y') }}
                            </p>
                        </div>

                        <x-ui.badge :variant="$request->status->badge()">
                            {{ $request->status->label() }}
                        </x-ui.badge>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">{{ $requests->links() }}</div>
    @endif
</x-layouts.account>
