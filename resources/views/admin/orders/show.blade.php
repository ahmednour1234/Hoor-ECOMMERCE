<x-layouts.admin>
    @section('title', $order->number)
    @section('page-title', __('orders.admin.number').' '.$order->number)

    <x-admin.page-header :title="$order->number">
        <x-slot:subtitle>
            {{ $order->created_at->translatedFormat('d M Y — H:i') }}
        </x-slot:subtitle>

        <x-slot:actions>
            <x-ui.badge :variant="$order->status->badge()">{{ $order->status->label() }}</x-ui.badge>

            <x-ui.button variant="ghost" size="sm" :href="route('admin.orders.index')">
                {{ __('orders.admin.back') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->has('status'))
        <x-ui.alert variant="danger" class="mb-6">{{ $errors->first('status') }}</x-ui.alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- ------------------------------------------------- Left: the order --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Products, with the snapshot values the order was placed at --}}
            <x-admin.panel :title="__('orders.admin.products')">
                <x-admin.table :headings="[
                    __('orders.admin.product'),
                    __('orders.admin.variant'),
                    __('orders.admin.unit_price'),
                    __('orders.admin.quantity'),
                    __('orders.admin.line_total'),
                ]" class="border-0 shadow-none">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-hoor-navy-700">{{ $item->product_name }}</p>
                                <p class="font-mono text-xs text-hoor-muted" dir="ltr">{{ $item->sku }}</p>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-hoor-muted">
                                {{ collect([$item->size_name, $item->color_name])->filter()->join(' / ') ?: '—' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-hoor-navy-700" dir="ltr">
                                {{ \App\Casts\Money::format($item->unit_price) }}
                            </td>

                            <td class="px-4 py-3 text-hoor-navy-700">{{ $item->quantity }}</td>

                            <td class="whitespace-nowrap px-4 py-3 font-medium text-hoor-navy-700" dir="ltr">
                                {{ \App\Casts\Money::format($item->line_total) }}
                            </td>
                        </tr>
                    @endforeach
                </x-admin.table>

                {{-- Totals as stored on the order: the money the customer owes. --}}
                <dl class="mt-4 space-y-2 border-t border-hoor-cream-300 pt-4 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-hoor-muted">{{ __('orders.admin.subtotal') }}</dt>
                        <dd class="text-hoor-navy-700" dir="ltr">{{ \App\Casts\Money::format($order->subtotal) }}</dd>
                    </div>

                    <div class="flex justify-between">
                        <dt class="text-hoor-muted">{{ __('orders.admin.shipping') }}</dt>
                        <dd class="text-hoor-navy-700" dir="ltr">{{ \App\Casts\Money::format($order->shipping) }}</dd>
                    </div>

                    @if ($order->discount > 0)
                        <div class="flex justify-between">
                            <dt class="text-hoor-muted">
                                {{ __('orders.admin.discount') }}
                                @if ($order->coupon_code)
                                    <span class="font-mono text-xs" dir="ltr">({{ $order->coupon_code }})</span>
                                @endif
                            </dt>
                            <dd class="text-hoor-gold-600" dir="ltr">
                                −{{ \App\Casts\Money::format($order->discount) }}
                            </dd>
                        </div>
                    @endif

                    <div class="flex justify-between border-t border-hoor-cream-300 pt-2 text-base font-semibold">
                        <dt class="text-hoor-navy-700">{{ __('orders.admin.grand_total') }}</dt>
                        <dd class="text-hoor-navy-700" dir="ltr">{{ \App\Casts\Money::format($order->total) }}</dd>
                    </div>

                    <div class="flex justify-between pt-2">
                        <dt class="text-hoor-muted">{{ __('orders.admin.payment') }}</dt>
                        <dd class="text-hoor-navy-700">{{ $order->payment_method->label() }}</dd>
                    </div>
                </dl>
            </x-admin.panel>

            <x-admin.panel :title="__('orders.admin.notes')">
                <p class="whitespace-pre-line text-sm {{ $order->notes ? 'text-hoor-navy-700' : 'text-hoor-muted' }}">
                    {{ $order->notes ?: __('orders.admin.no_notes') }}
                </p>
            </x-admin.panel>

            {{--
                Status history, newest last so it reads as a story.

                Appended to, never rewritten: this is the record of how the
                order actually progressed.
            --}}
            <x-admin.panel :title="__('orders.admin.history')">
                <ol class="space-y-4">
                    <li class="flex gap-3">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-hoor-cream-400"></span>
                        <div>
                            <p class="text-sm text-hoor-navy-700">{{ __('orders.admin.history_initial') }}</p>
                            <p class="text-xs text-hoor-muted">
                                {{ $order->created_at->translatedFormat('d M Y — H:i') }}
                            </p>
                        </div>
                    </li>

                    @foreach ($order->statusHistory as $entry)
                        <li class="flex gap-3">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-hoor-denim-500"></span>
                            <div>
                                <p class="flex flex-wrap items-center gap-2 text-sm">
                                    @if ($entry->from_status)
                                        <span class="text-hoor-muted">{{ $entry->from_status->label() }}</span>
                                        <span class="text-hoor-muted" aria-hidden="true">&rarr;</span>
                                    @endif

                                    <x-ui.badge :variant="$entry->to_status->badge()">
                                        {{ $entry->to_status->label() }}
                                    </x-ui.badge>
                                </p>

                                <p class="text-xs text-hoor-muted">
                                    {{ $entry->created_at->translatedFormat('d M Y — H:i') }}
                                    · {{ __('orders.admin.history_by', ['actor' => $entry->actorName()]) }}
                                </p>

                                @if ($entry->note)
                                    <p class="mt-1 text-sm text-hoor-navy-600">{{ $entry->note }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </x-admin.panel>
        </div>

        {{-- ------------------------------------- Right: who, where, what next --}}
        <div class="space-y-6">

            <x-admin.panel :title="__('orders.admin.change_status')">
                @if ($transitions === [])
                    <p class="text-sm text-hoor-muted">{{ __('orders.admin.no_transitions') }}</p>
                @else
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <x-ui.select name="status"
                                     :label="__('orders.admin.new_status')"
                                     :options="$transitions"
                                     required />

                        <x-ui.textarea name="note"
                                       :label="__('orders.admin.internal_note')"
                                       :value="old('note')"
                                       rows="3"
                                       maxlength="500" />

                        <x-ui.button type="submit" variant="primary" size="sm" class="w-full">
                            {{ __('orders.admin.apply') }}
                        </x-ui.button>
                    </form>
                @endif
            </x-admin.panel>

            <x-admin.panel :title="__('orders.admin.customer_details')">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-hoor-muted">{{ __('orders.admin.name') }}</dt>
                        <dd class="text-hoor-navy-700">{{ $order->address?->full_name }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs text-hoor-muted">{{ __('orders.admin.phone') }}</dt>
                        <dd class="text-hoor-navy-700" dir="ltr">
                            <a href="tel:{{ $order->address?->phone }}" class="hover:text-hoor-gold-600">
                                {{ $order->address?->phone }}
                            </a>
                        </dd>
                    </div>

                    @if ($order->address?->phone_alt)
                        <div>
                            <dt class="text-xs text-hoor-muted">{{ __('orders.admin.phone_alt') }}</dt>
                            <dd class="text-hoor-navy-700" dir="ltr">
                                <a href="tel:{{ $order->address->phone_alt }}" class="hover:text-hoor-gold-600">
                                    {{ $order->address->phone_alt }}
                                </a>
                            </dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs text-hoor-muted">{{ __('orders.admin.account') }}</dt>
                        <dd class="text-hoor-navy-700">
                            @if ($order->user)
                                {{ $order->user->name }}
                                <span class="text-xs text-hoor-muted" dir="ltr">({{ $order->user->email }})</span>
                            @else
                                <span class="text-hoor-muted">{{ __('orders.admin.guest') }}</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-admin.panel>

            <x-admin.panel :title="__('orders.admin.address')">
                {{-- Snapshot names, so the address reads as it did when placed. --}}
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-hoor-muted">{{ __('orders.admin.governorate') }}</dt>
                        <dd class="text-hoor-navy-700">{{ $order->address?->governorate_name }}</dd>
                    </div>

                    @if ($order->address?->area_name)
                        <div>
                            <dt class="text-xs text-hoor-muted">{{ __('orders.admin.area') }}</dt>
                            <dd class="text-hoor-navy-700">{{ $order->address->area_name }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs text-hoor-muted">{{ __('orders.admin.street') }}</dt>
                        <dd class="whitespace-pre-line text-hoor-navy-700">{{ $order->address?->address }}</dd>
                    </div>

                    @if ($order->address?->landmark)
                        <div>
                            <dt class="text-xs text-hoor-muted">{{ __('orders.admin.landmark') }}</dt>
                            <dd class="text-hoor-navy-700">{{ $order->address->landmark }}</dd>
                        </div>
                    @endif

                    {{-- The pin the customer dropped, if she did. Opens in the
                         staff member's own maps app, so the courier can be sent
                         a location rather than only a description. --}}
                    @if ($order->address?->latitude && $order->address?->longitude)
                        <div>
                            <dt class="text-xs text-hoor-muted">{{ __('orders.admin.pin') }}</dt>
                            <dd>
                                <a href="https://maps.google.com/?q={{ $order->address->latitude }},{{ $order->address->longitude }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 text-hoor-denim-600 transition hover:text-hoor-denim-700">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11z" />
                                        <circle cx="12" cy="10" r="2.5" />
                                    </svg>
                                    {{ __('orders.admin.open_map') }}
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-admin.panel>
        </div>
    </div>
</x-layouts.admin>
