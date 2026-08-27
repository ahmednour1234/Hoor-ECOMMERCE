<x-layouts.admin>
    @section('title', $coupon->code)
    @section('page-title', $coupon->code)

    <x-admin.page-header :title="$coupon->code">
        <x-slot:subtitle>
            {{ $coupon->name ?: $coupon->summary() }}
        </x-slot:subtitle>

        <x-slot:actions>
            @php $status = $coupon->statusKey(); @endphp

            <x-ui.badge :variant="$status === 'live' ? 'success' : ($status === 'scheduled' ? 'denim' : 'neutral')">
                {{ __('coupons.status.'.$status) }}
            </x-ui.badge>

            <x-ui.button variant="outline" size="sm" :href="route('admin.coupons.edit', $coupon)">
                {{ __('common.actions.edit') }}
            </x-ui.button>

            <x-ui.button variant="ghost" size="sm" :href="route('admin.coupons.index')">
                {{ __('coupons.admin.back') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->has('code'))
        <x-ui.alert variant="danger" class="mb-6">{{ $errors->first('code') }}</x-ui.alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            {{-- Who actually redeemed it. Guests appear by phone, which is the
                 key the per-customer limit is enforced on. --}}
            <x-admin.panel :title="__('coupons.admin.redemptions')">
                @if ($redemptions->isEmpty())
                    <p class="py-6 text-center text-sm text-hoor-muted">
                        {{ __('coupons.admin.no_redemptions') }}
                    </p>
                @else
                    <x-admin.table :headings="[
                        __('coupons.admin.customer'),
                        __('coupons.admin.order'),
                        __('coupons.fields.discount'),
                        __('coupons.admin.when'),
                    ]" class="border-0 shadow-none">
                        @foreach ($redemptions as $redemption)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-hoor-navy-700">
                                        {{ $redemption->user?->name ?? __('coupons.admin.guest') }}
                                    </p>

                                    @if ($redemption->phone)
                                        <p class="font-mono text-xs text-hoor-muted" dir="ltr">
                                            {{ $redemption->phone }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    @if ($redemption->order)
                                        <a href="{{ route('admin.orders.show', $redemption->order) }}"
                                           class="font-mono text-xs text-hoor-denim-600 hover:text-hoor-denim-700"
                                           dir="ltr">{{ $redemption->order->number }}</a>
                                    @else
                                        <span class="text-hoor-muted">&mdash;</span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-hoor-navy-700" dir="ltr">
                                    {{ \App\Casts\Money::format($redemption->discount) }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-xs text-hoor-muted">
                                    {{ $redemption->created_at->translatedFormat('d M Y') }}
                                </td>
                            </tr>
                        @endforeach

                        <x-slot:footer>{{ $redemptions->links() }}</x-slot:footer>
                    </x-admin.table>
                @endif
            </x-admin.panel>
        </div>

        <div class="space-y-6">
            <x-admin.panel :title="__('coupons.admin.terms')">
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        'type'               => $coupon->type->label(),
                        'value'              => $coupon->type->formatValue($coupon->value),
                        'max_discount'       => $coupon->max_discount ? \App\Casts\Money::format($coupon->max_discount) : null,
                        'min_order'          => $coupon->min_order ? \App\Casts\Money::format($coupon->min_order) : null,
                        'starts_at'          => $coupon->starts_at?->translatedFormat('d M Y — H:i'),
                        'expires_at'         => $coupon->expires_at?->translatedFormat('d M Y — H:i'),
                        'usage_limit'        => $coupon->usage_limit ?? __('coupons.admin.unlimited'),
                        'per_customer_limit' => $coupon->per_customer_limit ?? __('coupons.admin.unlimited'),
                    ] as $field => $value)
                        @if ($value !== null)
                            <div class="flex justify-between gap-3">
                                <dt class="text-xs text-hoor-muted">{{ __('coupons.fields.'.$field) }}</dt>
                                <dd class="text-end text-hoor-navy-700" dir="ltr">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </x-admin.panel>

            <x-admin.panel :title="__('coupons.fields.used')">
                <p class="font-display text-2xl text-hoor-navy-700" dir="ltr">
                    {{ $coupon->used_count }}@if ($coupon->usage_limit) / {{ $coupon->usage_limit }}@endif
                </p>

                @if ($coupon->remainingUses() !== null)
                    <p class="mt-1 text-xs text-hoor-muted">
                        {{ __('coupons.admin.remaining', ['count' => $coupon->remainingUses()]) }}
                    </p>
                @endif

                <div class="mt-4 space-y-2 border-t border-hoor-cream-300 pt-4">
                    <form method="POST" action="{{ route('admin.coupons.toggle', $coupon) }}">
                        @csrf
                        @method('PATCH')

                        <x-ui.button type="submit" variant="outline" size="sm" class="w-full">
                            {{ $coupon->is_active ? __('coupons.admin.disable') : __('coupons.admin.enable') }}
                        </x-ui.button>
                    </form>

                    {{-- A coupon with redemptions is switched off rather than
                         deleted; the controller refuses either way. --}}
                    @can('delete', $coupon)
                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}"
                              onsubmit="return confirm({{ Illuminate\Support\Js::from(__('coupons.admin.confirm')) }})">
                            @csrf
                            @method('DELETE')

                            <x-ui.button type="submit" variant="ghost" size="sm"
                                         class="w-full text-red-600 hover:text-red-700">
                                {{ __('common.actions.delete') }}
                            </x-ui.button>
                        </form>
                    @endcan
                </div>
            </x-admin.panel>
        </div>
    </div>
</x-layouts.admin>
