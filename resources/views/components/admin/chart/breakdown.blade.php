{{--
    A ranked breakdown as proportional bars.

    Chosen over a pie chart deliberately: comparing angles is harder than
    comparing lengths, and this form carries the actual numbers alongside,
    which is what someone reading a dashboard is really after.

    Rows are `['label' => string, 'value' => int, 'variant' => ?string]`.
--}}
@props([
    'rows'  => [],
    'money' => false,
    'empty' => null,
])

@php
    $total = array_sum(array_map(fn (array $row): int => (int) $row['value'], $rows));
    $peak = max(array_map(fn (array $row): int => (int) $row['value'], $rows) ?: [0]);

    $tone = [
        'success' => 'bg-emerald-500',
        'danger'  => 'bg-red-500',
        'warning' => 'bg-amber-500',
        'denim'   => 'bg-hoor-denim-500',
        'gold'    => 'bg-hoor-gold-500',
    ];
@endphp

<div {{ $attributes }}>
    @if ($peak === 0)
        <p class="py-8 text-center text-sm text-hoor-muted">
            {{ $empty ?? __('admin.dashboard.no_data') }}
        </p>
    @else
        <ul class="space-y-3">
            @foreach ($rows as $row)
                @php
                    $value = (int) $row['value'];
                    $share = $total > 0 ? round(($value / $total) * 100) : 0;
                    $width = $peak > 0 ? max(1, round(($value / $peak) * 100)) : 0;
                    $bar = $tone[$row['variant'] ?? ''] ?? 'bg-hoor-navy-500';
                @endphp

                <li>
                    <div class="flex items-baseline justify-between gap-3 text-sm">
                        <span class="truncate text-hoor-navy-700">{{ $row['label'] }}</span>

                        <span class="shrink-0 tabular-nums text-hoor-muted" dir="ltr">
                            <span class="font-medium text-hoor-navy-700">
                                {{ $money ? \App\Casts\Money::format($value) : $value }}
                            </span>
                            <span class="text-xs">({{ $share }}%)</span>
                        </span>
                    </div>

                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-hoor-cream-200">
                        <div class="h-full rounded-full {{ $bar }}" style="width:{{ $width }}%"></div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
