<x-layouts.admin>
    @section('title', __('coupons.title'))
    @section('page-title', __('coupons.title'))

    <x-admin.page-header :title="__('coupons.title')">
        <x-slot:actions>
            <x-ui.button variant="primary" size="sm" :href="route('admin.coupons.create')">
                {{ __('coupons.admin.add') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->has('code'))
        <x-ui.alert variant="danger" class="mb-6">{{ $errors->first('code') }}</x-ui.alert>
    @endif

    <form method="GET" class="card mb-6 flex gap-3 p-4">
        <input type="text" name="search" value="{{ $search }}" dir="ltr"
               placeholder="{{ __('coupons.admin.search') }}" class="form-input flex-1">
        <x-ui.button type="submit" variant="outline" size="sm">
            {{ __('common.actions.search') }}
        </x-ui.button>
    </form>

    @if ($coupons->isEmpty())
        <x-admin.empty-state :title="__('coupons.admin.empty')" />
    @else
        <x-admin.table :headings="[
            __('coupons.fields.code'),
            __('coupons.admin.terms'),
            __('coupons.admin.window'),
            __('coupons.admin.usage'),
            __('orders.fields.status'),
            '',
        ]">
            @foreach ($coupons as $coupon)
                <tr class="transition hover:bg-hoor-cream-100/50">
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.coupons.show', $coupon) }}"
                           class="font-mono text-sm font-medium text-hoor-navy-700 hover:text-hoor-gold-600"
                           dir="ltr">{{ $coupon->code }}</a>

                        @if ($coupon->name)
                            <p class="text-xs text-hoor-muted">{{ $coupon->name }}</p>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-xs text-hoor-muted" dir="ltr">{{ $coupon->summary() }}</td>

                    <td class="whitespace-nowrap px-4 py-3 text-xs text-hoor-muted">
                        {{ $coupon->starts_at?->translatedFormat('d M Y') ?? '—' }}
                        →
                        {{ $coupon->expires_at?->translatedFormat('d M Y') ?? '—' }}
                    </td>

                    <td class="whitespace-nowrap px-4 py-3 text-sm text-hoor-navy-700" dir="ltr">
                        {{ $coupon->used_count }}@if ($coupon->usage_limit) / {{ $coupon->usage_limit }}@endif

                        @if ($coupon->usage_limit === null)
                            <span class="block text-xs text-hoor-muted">{{ __('coupons.admin.unlimited') }}</span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        @php $status = $coupon->statusKey(); @endphp
                        <x-ui.badge :variant="$status === 'live' ? 'success' : ($status === 'scheduled' ? 'denim' : 'neutral')">
                            {{ __('coupons.status.'.$status) }}
                        </x-ui.badge>
                    </td>

                    <td class="px-4 py-3 text-end">
                        <div class="flex items-center justify-end gap-2">
                            <x-ui.button variant="ghost" size="sm" :href="route('admin.coupons.edit', $coupon)">
                                {{ __('common.actions.edit') }}
                            </x-ui.button>

                            <form method="POST" action="{{ route('admin.coupons.toggle', $coupon) }}">
                                @csrf
                                @method('PATCH')
                                <x-ui.button type="submit" variant="ghost" size="sm">
                                    {{ $coupon->is_active ? __('coupons.admin.disable') : __('coupons.admin.enable') }}
                                </x-ui.button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach

            <x-slot:footer>{{ $coupons->links() }}</x-slot:footer>
        </x-admin.table>
    @endif
</x-layouts.admin>
