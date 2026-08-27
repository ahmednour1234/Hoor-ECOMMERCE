{{--
    The dashboard's date window.

    Presets are plain links, so each window is a shareable URL and the back
    button works. The custom range is a real form that submits without
    JavaScript; Alpine only reveals it, so nothing is lost with JS off.
--}}
@props(['period'])

@php
    $labels = [
        \App\Support\DatePeriodFilter::TODAY => __('admin.dashboard.period.today'),
        \App\Support\DatePeriodFilter::WEEK  => __('admin.dashboard.period.week'),
        \App\Support\DatePeriodFilter::MONTH => __('admin.dashboard.period.month'),
    ];
@endphp

<div x-data="{ custom: @js($period->isCustom()) }"
     {{ $attributes->merge(['class' => 'card p-4']) }}>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-1">
            @foreach ($labels as $key => $label)
                <a href="{{ route('admin.dashboard', ['period' => $key]) }}"
                   @if ($period->key === $key) aria-current="page" @endif
                   class="rounded-sm px-3 py-1.5 text-sm font-medium transition
                          {{ $period->key === $key
                              ? 'bg-hoor-navy-700 text-hoor-cream-50'
                              : 'text-hoor-muted hover:bg-hoor-cream-100 hover:text-hoor-navy-700' }}">
                    {{ $label }}
                </a>
            @endforeach

            <button type="button" @click="custom = ! custom"
                    class="rounded-sm px-3 py-1.5 text-sm font-medium transition
                           {{ $period->isCustom()
                               ? 'bg-hoor-navy-700 text-hoor-cream-50'
                               : 'text-hoor-muted hover:bg-hoor-cream-100 hover:text-hoor-navy-700' }}">
                {{ __('admin.dashboard.period.custom') }}
            </button>
        </div>

        <p class="text-xs text-hoor-muted" dir="ltr">
            {{ $period->start->translatedFormat('d M Y') }}
            &ndash;
            {{ $period->end->translatedFormat('d M Y') }}
        </p>
    </div>

    <form method="GET" action="{{ route('admin.dashboard') }}"
          x-show="custom" x-cloak
          class="mt-4 flex flex-wrap items-end gap-3 border-t border-hoor-cream-300 pt-4">

        <input type="hidden" name="period" value="custom">

        <div>
            <label for="from" class="form-label">{{ __('admin.dashboard.period.from') }}</label>
            <input id="from" type="date" name="from" class="form-input"
                   value="{{ $period->start->toDateString() }}"
                   max="{{ now()->toDateString() }}">
        </div>

        <div>
            <label for="to" class="form-label">{{ __('admin.dashboard.period.to') }}</label>
            <input id="to" type="date" name="to" class="form-input"
                   value="{{ $period->end->toDateString() }}"
                   max="{{ now()->toDateString() }}">
        </div>

        <x-ui.button type="submit" variant="secondary" size="sm">
            {{ __('admin.dashboard.period.apply') }}
        </x-ui.button>
    </form>
</div>
