<x-layouts.account :title="__('returns.customer.create_title')">
    <x-slot:subtitle>
        {{ __('returns.customer.create_intro') }}
    </x-slot:subtitle>

    <p class="mb-6 font-mono text-sm text-hoor-muted" dir="ltr">{{ $order->number }}</p>

    @if ($errors->any())
        <x-ui.alert variant="danger" class="mb-6">
            {{ $errors->first() }}
        </x-ui.alert>
    @endif

    @php $anyAvailable = collect($lines)->contains(fn ($line) => $line['remaining'] > 0); @endphp

    {{-- The replacement pickers only make sense for an exchange, so the whole
         form knows which type is selected rather than each row asking. --}}
    <form method="POST" action="{{ route('store.account.returns.store', $order) }}"
          x-data="{ type: @js(old('type', \App\Enums\ReturnType::Return_->value)) }"
          class="space-y-6">
        @csrf

        <section class="card p-5">
            <h2 class="mb-4 font-display text-lg text-hoor-navy-700">{{ __('returns.fields.items') }}</h2>

            @if (! $anyAvailable)
                <p class="py-6 text-center text-sm text-hoor-muted">
                    {{ __('returns.customer.nothing_left') }}
                </p>
            @else
                <ul class="divide-y divide-hoor-cream-300">
                    @foreach ($lines as $line)
                        @php
                            $item = $line['item'];
                            $replacements = $line['replacements'];
                        @endphp

                        <li class="py-4">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm text-hoor-navy-700">{{ $item->product_name }}</p>

                                    <p class="mt-0.5 text-xs text-hoor-muted">
                                        {{ collect([$item->size_name, $item->color_name])->filter()->join(' / ') }}
                                        · <span dir="ltr">× {{ $item->quantity }}</span>
                                    </p>
                                </div>

                                @if ($line['remaining'] > 0)
                                    <label class="flex items-center gap-2 text-sm">
                                        <span class="text-hoor-muted">{{ __('returns.fields.quantity') }}</span>

                                        <select name="quantities[{{ $item->id }}]" class="form-select w-20">
                                            @for ($i = 0; $i <= $line['remaining']; $i++)
                                                <option value="{{ $i }}"
                                                    @selected((int) old('quantities.'.$item->id) === $i)>{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </label>
                                @else
                                    <span class="text-xs text-hoor-muted">
                                        {{ __('returns.customer.returned_already') }}
                                    </span>
                                @endif
                            </div>

                            {{-- What she would like instead. Shown only for an
                                 exchange, and only listing what is genuinely
                                 in stock — offering a sold-out size would be a
                                 promise the shop cannot keep. --}}
                            @if ($line['remaining'] > 0)
                                <div x-show="type === 'exchange'" x-cloak class="mt-3 ps-1">
                                    @if ($replacements->isEmpty())
                                        <p class="text-xs text-hoor-muted">{{ __('returns.customer.no_stock') }}</p>
                                    @else
                                        <label class="block text-xs text-hoor-muted"
                                               for="replacement-{{ $item->id }}">
                                            {{ __('returns.fields.replacement') }}
                                        </label>

                                        <select name="replacements[{{ $item->id }}]"
                                                id="replacement-{{ $item->id }}"
                                                class="form-select mt-1 max-w-xs">
                                            <option value="">{{ __('shipping.checkout.choose') }}</option>

                                            @foreach ($replacements as $variant)
                                                <option value="{{ $variant->id }}"
                                                    @selected((int) old('replacements.'.$item->id) === $variant->id)>
                                                    {{ collect([$variant->size?->name, $variant->color?->name])
                                                        ->filter()->join(' / ') ?: $variant->sku }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <p x-show="type === 'exchange'" x-cloak
                   class="mt-4 border-t border-hoor-cream-300 pt-3 text-xs text-hoor-muted">
                    {{ __('returns.fields.replacement_hint') }}
                </p>
            @endif
        </section>

        @if ($anyAvailable)
            <section class="card space-y-5 p-5">
                <x-ui.select name="type"
                             :label="__('returns.fields.type')"
                             :options="$types"
                             :selected="old('type', \App\Enums\ReturnType::Return_->value)"
                             required
                             x-model="type" />

                <x-ui.select name="reason"
                             :label="__('returns.fields.reason')"
                             :options="$reasons"
                             :selected="old('reason')"
                             :placeholder="__('shipping.checkout.choose')"
                             required />

                <x-ui.textarea name="note" rows="4"
                               :label="__('returns.fields.note')"
                               :value="old('note')" />
            </section>

            <div class="flex gap-3">
                <x-ui.button type="submit" variant="primary">
                    {{ __('returns.customer.submit') }}
                </x-ui.button>

                <x-ui.button variant="ghost" :href="route('store.account.orders.show', $order)">
                    {{ __('common.actions.cancel') }}
                </x-ui.button>
            </div>
        @endif
    </form>
</x-layouts.account>
