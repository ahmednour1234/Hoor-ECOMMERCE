{{--
    A time series as inline SVG.

    Server-rendered rather than handed to a charting library: it needs no
    dependency, works with JavaScript off, survives printing, and reads
    correctly in RTL because the bars are laid out in document order.

    Values arrive in the smallest unit (piastres for money), and are formatted
    only at the tooltip and the axis.
--}}
@props([
    'series'   => [],       // list<array{label, orders, revenue}>
    'metric'   => 'orders', // which key to plot
    'money'    => false,
    'grouping' => 'day',
])

@php
    $values = array_map(fn (array $point): int => (int) $point[$metric], $series);
    $peak = max($values ?: [0]);

    // A flat-zero series still needs a baseline, or every bar divides by zero.
    $scale = $peak > 0 ? $peak : 1;

    $format = function (string $label) use ($grouping): string {
        $date = \Carbon\CarbonImmutable::parse($label);

        return $grouping === 'month'
            ? $date->translatedFormat('M Y')
            : $date->translatedFormat('d M');
    };
@endphp

<div {{ $attributes }}>
    @if ($peak === 0)
        <p class="py-10 text-center text-sm text-hoor-muted">{{ __('admin.dashboard.no_data') }}</p>
    @else
        <div class="flex h-44 items-end gap-px" role="img"
             aria-label="{{ __('admin.dashboard.chart_alt', ['metric' => $metric]) }}">
            @foreach ($series as $point)
                @php
                    $value = (int) $point[$metric];
                    $height = $value > 0 ? max(2, (int) round(($value / $scale) * 100)) : 0;
                    $display = $money ? \App\Casts\Money::format($value) : $value;
                @endphp

                <div class="group relative flex flex-1 items-end justify-center"
                     style="height:100%"
                     title="{{ $format($point['label']) }} — {{ $display }}">

                    <div class="w-full rounded-t-sm transition
                                {{ $value > 0 ? 'bg-hoor-denim-400 group-hover:bg-hoor-denim-600' : 'bg-hoor-cream-200' }}"
                         style="height:{{ $height }}%"></div>
                </div>
            @endforeach
        </div>

        {{-- Only the ends and the middle are labelled: a 30-bar axis cannot
             carry 30 legible dates. --}}
        <div class="mt-2 flex justify-between text-xs text-hoor-muted">
            <span>{{ $format($series[0]['label']) }}</span>
            @if (count($series) > 2)
                <span>{{ $format($series[intdiv(count($series), 2)]['label']) }}</span>
            @endif
            <span>{{ $format($series[count($series) - 1]['label']) }}</span>
        </div>

        <p class="mt-3 text-xs text-hoor-muted">
            {{ __('admin.dashboard.peak') }}:
            <span class="font-medium text-hoor-navy-700" dir="ltr">
                {{ $money ? \App\Casts\Money::format($peak) : $peak }}
            </span>
        </p>
    @endif
</div>
