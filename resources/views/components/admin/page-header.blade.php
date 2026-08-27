@props([
    'title'    => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-end justify-between gap-4']) }}>
    <div>
        <h2 class="font-display text-2xl text-hoor-navy-700">{{ $title ?? $slot }}</h2>
        @if ($subtitle)
            <p class="mt-1 text-sm text-hoor-muted">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
