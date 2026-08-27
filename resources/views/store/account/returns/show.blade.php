<x-layouts.account :title="$request->number">
    <x-slot:subtitle>
        {{ $request->type->label() }}
        · {{ $request->created_at->translatedFormat('d M Y') }}
    </x-slot:subtitle>

    @if ($errors->has('status'))
        <x-ui.alert variant="danger" class="mb-6">{{ $errors->first('status') }}</x-ui.alert>
    @endif

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-ui.badge :variant="$request->status->badge()" class="text-sm">
            {{ $request->status->label() }}
        </x-ui.badge>

        <a href="{{ route('store.account.orders.show', $request->order) }}"
           class="font-mono text-sm text-hoor-denim-600 hover:text-hoor-denim-700" dir="ltr">
            {{ $request->order?->number }}
        </a>
    </div>

    <section class="card p-5">
        <h2 class="mb-4 font-display text-lg text-hoor-navy-700">{{ __('returns.fields.items') }}</h2>

        <ul class="divide-y divide-hoor-cream-300">
            @foreach ($request->items as $line)
                <li class="flex items-center justify-between gap-4 py-3 text-sm">
                    <div>
                        <p class="text-hoor-navy-700">{{ $line->orderItem?->product_name }}</p>
                        <p class="mt-0.5 text-xs text-hoor-muted">
                            {{ collect([$line->orderItem?->size_name, $line->orderItem?->color_name])
                                ->filter()->join(' / ') }}
                        </p>

                        {{-- Read from the snapshot, so a size since retired
                             still shows what was agreed. --}}
                        @if ($line->isExchange())
                            <p class="mt-1 text-xs text-hoor-denim-600">
                                {{ __('returns.customer.replacement') }}:
                                {{ $line->replacementLabel() }}
                            </p>
                        @endif
                    </div>

                    <span class="text-hoor-muted" dir="ltr">× {{ $line->quantity }}</span>
                </li>
            @endforeach
        </ul>

        <dl class="mt-4 space-y-3 border-t border-hoor-cream-300 pt-4 text-sm">
            <div>
                <dt class="text-xs text-hoor-muted">{{ __('returns.fields.reason') }}</dt>
                <dd class="text-hoor-navy-700">{{ $request->reason->label() }}</dd>
            </div>

            @if ($request->customer_note)
                <div>
                    <dt class="text-xs text-hoor-muted">{{ __('returns.customer.your_note') }}</dt>
                    <dd class="whitespace-pre-line text-hoor-navy-700">{{ $request->customer_note }}</dd>
                </div>
            @endif
        </dl>
    </section>

    {{-- Our answer, once there is one. --}}
    @if ($request->status->isDecided())
        <section class="card mt-6 p-5">
            <h2 class="mb-2 font-display text-lg text-hoor-navy-700">{{ __('returns.customer.our_reply') }}</h2>

            @if ($request->decided_at)
                <p class="mb-3 text-xs text-hoor-muted">
                    {{ __('returns.customer.decided_on', [
                        'date' => $request->decided_at->translatedFormat('d M Y'),
                    ]) }}
                </p>
            @endif

            <p class="whitespace-pre-line text-sm text-hoor-navy-700">
                {{ $request->admin_note ?: $request->status->label() }}
            </p>
        </section>
    @endif

    <div class="mt-6 flex flex-wrap gap-3">
        <x-ui.button variant="ghost" size="sm" :href="route('store.account.returns.index')">
            {{ __('returns.customer.title') }}
        </x-ui.button>

        @can('withdraw', $request)
            <form method="POST" action="{{ route('store.account.returns.destroy', $request) }}"
                  onsubmit="return confirm(@js(__('returns.customer.confirm')))">
                @csrf
                @method('DELETE')

                <x-ui.button type="submit" variant="ghost" size="sm"
                             class="text-red-600 hover:text-red-700">
                    {{ __('returns.customer.withdraw') }}
                </x-ui.button>
            </form>
        @endcan
    </div>
</x-layouts.account>
