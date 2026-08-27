<x-layouts.admin>
    @section('title', $request->number)
    @section('page-title', $request->number)

    <x-admin.page-header :title="$request->number">
        <x-slot:subtitle>
            {{ $request->type->label() }} · {{ $request->created_at->translatedFormat('d M Y — H:i') }}
        </x-slot:subtitle>

        <x-slot:actions>
            <x-ui.badge :variant="$request->status->badge()">{{ $request->status->label() }}</x-ui.badge>

            <x-ui.button variant="ghost" size="sm" :href="route('admin.returns.index')">
                {{ __('returns.admin.back') }}
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->has('decision'))
        <x-ui.alert variant="danger" class="mb-6">{{ $errors->first('decision') }}</x-ui.alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">

            <x-admin.panel :title="__('returns.admin.items')">
                <x-admin.table :headings="[
                    __('orders.admin.product'),
                    __('orders.admin.variant'),
                    __('returns.admin.replacement'),
                    __('returns.fields.quantity'),
                ]" class="border-0 shadow-none">
                    @foreach ($request->items as $line)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-hoor-navy-700">
                                    {{ $line->orderItem?->product_name }}
                                </p>
                                <p class="font-mono text-xs text-hoor-muted" dir="ltr">
                                    {{ $line->orderItem?->sku }}
                                </p>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-hoor-muted">
                                {{ collect([$line->orderItem?->size_name, $line->orderItem?->color_name])
                                    ->filter()->join(' / ') ?: '—' }}
                            </td>

                            {{-- Read from the snapshot, so a variant since
                                 retired still shows what was agreed. --}}
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($line->isExchange())
                                    <span class="text-hoor-navy-700">{{ $line->replacementLabel() }}</span>
                                    <span class="block font-mono text-xs text-hoor-muted" dir="ltr">
                                        {{ $line->replacement_sku }}
                                    </span>
                                @else
                                    <span class="text-hoor-muted">—</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-hoor-navy-700" dir="ltr">
                                {{ $line->quantity }} / {{ $line->orderItem?->quantity }}

                                @if ($line->received_quantity !== null)
                                    <span class="block text-xs text-hoor-muted">
                                        {{ __('returns.fields.received') }}: {{ $line->received_quantity }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-admin.table>
            </x-admin.panel>

            <x-admin.panel :title="__('returns.fields.reason')">
                <p class="text-sm text-hoor-navy-700">{{ $request->reason->label() }}</p>

                @if ($request->customer_note)
                    <p class="mt-3 whitespace-pre-line border-t border-hoor-cream-300 pt-3 text-sm text-hoor-muted">
                        {{ $request->customer_note }}
                    </p>
                @endif
            </x-admin.panel>

            @if ($request->admin_note)
                <x-admin.panel :title="__('returns.customer.our_reply')">
                    <p class="whitespace-pre-line text-sm text-hoor-navy-700">{{ $request->admin_note }}</p>
                </x-admin.panel>
            @endif
        </div>

        <div class="space-y-6">
            {{-- What can be done next comes from the enum, so the buttons here
                 cannot offer a move the action would refuse. --}}
            <x-admin.panel :title="__('returns.admin.decide')">
                @if ($transitions === [])
                    <p class="text-sm text-hoor-muted">
                        @if ($request->decidedBy)
                            {{ __('returns.admin.decided_by', [
                                'name' => $request->decidedBy->name,
                                'date' => $request->decided_at?->translatedFormat('d M Y') ?? '',
                            ]) }}
                        @else
                            {{ $request->status->label() }}
                        @endif
                    </p>
                @else
                    <form method="POST" action="{{ route('admin.returns.decide', $request) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        {{-- Receiving asks what actually turned up, because the
                             box does not always match the request. --}}
                        @if (array_key_exists(\App\Enums\ReturnStatus::Received->value, $transitions))
                            <fieldset class="space-y-2 rounded-sm bg-hoor-cream-100/60 p-3">
                                <legend class="text-xs font-medium text-hoor-navy-700">
                                    {{ __('returns.fields.received') }}
                                </legend>

                                @foreach ($request->items as $line)
                                    <label class="flex items-center justify-between gap-3 text-xs">
                                        <span class="truncate text-hoor-muted">
                                            {{ $line->orderItem?->product_name }}
                                        </span>

                                        <select name="received[{{ $line->id }}]" class="form-select w-16 py-1 text-xs">
                                            @for ($i = 0; $i <= $line->quantity; $i++)
                                                <option value="{{ $i }}" @selected($i === $line->quantity)>{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </label>
                                @endforeach

                                @if ($request->hasReplacements())
                                    <p class="pt-1 text-xs text-hoor-muted">
                                        {{ __('returns.admin.exchange_note') }}
                                    </p>
                                @endif
                            </fieldset>
                        @endif

                        <x-ui.textarea name="note" rows="3"
                                       :label="__('returns.admin.note')"
                                       :value="old('note')" />

                        @if ($request->status === \App\Enums\ReturnStatus::Requested)
                            <p class="text-xs text-hoor-muted">{{ __('returns.admin.restock_note') }}</p>
                        @endif

                        <div class="flex flex-col gap-2">
                            @foreach ($transitions as $value => $label)
                                @php
                                    $decision = match ($value) {
                                        'approved'  => 'approve',
                                        'rejected'  => 'reject',
                                        'received'  => 'receive',
                                        'completed' => 'complete',
                                        default     => null,
                                    };

                                    $isRefusal = $value === 'rejected';
                                @endphp

                                @if ($decision)
                                    <x-ui.button type="submit" name="decision" :value="$decision"
                                                 :variant="$isRefusal ? 'ghost' : 'primary'"
                                                 size="sm"
                                                 class="w-full {{ $isRefusal ? 'text-red-600 hover:text-red-700' : '' }}">
                                        {{ $value === 'received' ? __('returns.admin.receive') : $label }}
                                    </x-ui.button>
                                @endif
                            @endforeach
                        </div>
                    </form>
                @endif

                @if ($request->received_at && $request->receivedBy)
                    <p class="mt-4 border-t border-hoor-cream-300 pt-3 text-xs text-hoor-muted">
                        {{ __('returns.admin.received_by', [
                            'name' => $request->receivedBy->name,
                            'date' => $request->received_at->translatedFormat('d M Y'),
                        ]) }}
                    </p>
                @endif
            </x-admin.panel>

            <x-admin.panel :title="__('returns.admin.customer')">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-hoor-muted">{{ __('orders.admin.name') }}</dt>
                        <dd class="text-hoor-navy-700">
                            {{ $request->order?->address?->full_name ?? $request->user?->name }}
                        </dd>
                    </div>

                    @if ($request->order?->address?->phone)
                        <div>
                            <dt class="text-xs text-hoor-muted">{{ __('orders.admin.phone') }}</dt>
                            <dd class="text-hoor-navy-700" dir="ltr">
                                <a href="tel:{{ $request->order->address->phone }}"
                                   class="hover:text-hoor-gold-600">{{ $request->order->address->phone }}</a>
                            </dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs text-hoor-muted">{{ __('returns.admin.order') }}</dt>
                        <dd>
                            <a href="{{ route('admin.orders.show', $request->order) }}"
                               class="font-mono text-hoor-denim-600 hover:text-hoor-denim-700" dir="ltr">
                                {{ $request->order?->number }}
                            </a>
                        </dd>
                    </div>
                </dl>
            </x-admin.panel>
        </div>
    </div>
</x-layouts.admin>
