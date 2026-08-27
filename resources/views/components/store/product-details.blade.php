{{--
    Description, fabric and care, shipping, and the size guide.

    Rendered as disclosures so the page stays scannable, but every panel is in
    the DOM and the first opens by default, so the copy is readable to search
    engines and to anyone without JavaScript.
--}}
@props(['product'])

@php
    /*
     * Denim sizing, in centimetres. Held here rather than per product because
     * HOOR cuts to one size chart across the range; a product-specific chart
     * would live on the product record.
     */
    $sizeChart = [
        ['size' => 'XS',  'waist' => '62–66',  'hip' => '86–90',   'length' => '76'],
        ['size' => 'S',   'waist' => '66–70',  'hip' => '90–94',   'length' => '77'],
        ['size' => 'M',   'waist' => '71–75',  'hip' => '95–99',   'length' => '78'],
        ['size' => 'L',   'waist' => '76–81',  'hip' => '100–105', 'length' => '79'],
        ['size' => 'XL',  'waist' => '82–88',  'hip' => '106–112', 'length' => '80'],
        ['size' => 'XXL', 'waist' => '89–96',  'hip' => '113–120', 'length' => '81'],
    ];

    $sections = array_filter([
        'description' => $product->description,
        'material'    => $product->fabric || $product->care,
        'shipping'    => true,
        'size_guide'  => true,
    ]);
@endphp

<div x-data="{ open: 'description' }" class="mt-10 divide-y divide-hoor-cream-300 border-y border-hoor-cream-300">

    @foreach (array_keys($sections) as $key)
        <section @if ($key === 'size_guide') id="size-guide" class="scroll-mt-28" @endif>
            <h2>
                <button type="button"
                        @click="open = open === '{{ $key }}' ? null : '{{ $key }}'"
                        class="flex w-full items-center justify-between gap-4 py-4 text-start
                               text-sm font-medium text-hoor-navy-700 transition hover:text-hoor-gold-600"
                        :aria-expanded="open === '{{ $key }}'">
                    {{ __("store.product.sections.{$key}") }}

                    <span class="shrink-0 text-lg transition-transform duration-200"
                          :class="open === '{{ $key }}' && 'rotate-45'"
                          aria-hidden="true">+</span>
                </button>
            </h2>

            <div x-show="open === '{{ $key }}'"
                 @if ($key !== 'description') x-cloak @endif
                 x-transition:enter="transition ease-hoor duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="pb-6 text-sm leading-relaxed text-hoor-muted">

                @if ($key === 'description')
                        <p class="whitespace-pre-line">{{ $product->description }}</p>

                @elseif ($key === 'material')
                        <dl class="space-y-3">
                            @if ($product->fabric)
                                <div>
                                    <dt class="font-medium text-hoor-navy-700">{{ __('catalog.labels.fabric') }}</dt>
                                    <dd class="mt-0.5">{{ $product->fabric }}</dd>
                                </div>
                            @endif

                            @if ($product->care)
                                <div>
                                    <dt class="font-medium text-hoor-navy-700">{{ __('catalog.labels.care') }}</dt>
                                    <dd class="mt-0.5">{{ $product->care }}</dd>
                                </div>
                            @endif
                        </dl>

                @elseif ($key === 'shipping')
                        <ul class="space-y-2.5">
                            @foreach (['cod', 'delivery', 'returns'] as $note)
                                <li class="flex gap-2.5">
                                    <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-hoor-gold-500"
                                          aria-hidden="true"></span>
                                    {{ __("store.product.shipping.{$note}") }}
                                </li>
                            @endforeach
                        </ul>

                @elseif ($key === 'size_guide')
                        {{-- Scrolls inside its own container so the page body
                             never scrolls sideways on a phone. --}}
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-80 text-sm">
                                <thead>
                                    <tr class="border-b border-hoor-cream-300 text-xs uppercase
                                               tracking-wider text-hoor-muted">
                                        @foreach (['size', 'waist', 'hip', 'length'] as $column)
                                            <th scope="col" class="py-2 text-start font-semibold">
                                                {{ __("store.product.size_table.{$column}") }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-hoor-cream-300">
                                    @foreach ($sizeChart as $row)
                                        <tr>
                                            <th scope="row" class="py-2.5 text-start font-medium text-hoor-navy-700"
                                                dir="ltr">{{ $row['size'] }}</th>
                                            <td class="py-2.5" dir="ltr">{{ $row['waist'] }}</td>
                                            <td class="py-2.5" dir="ltr">{{ $row['hip'] }}</td>
                                            <td class="py-2.5" dir="ltr">{{ $row['length'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <p class="mt-3 text-xs">{{ __('store.product.size_guide_hint') }}</p>
                @endif
            </div>
        </section>
    @endforeach
</div>
