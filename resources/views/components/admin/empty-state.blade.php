@props([
    'title'   => null,
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'card p-12 text-center']) }}>
    <p class="font-display text-lg text-hoor-navy-700">{{ $title }}</p>

    @if ($message)
        <p class="mx-auto mt-2 max-w-sm text-sm text-hoor-muted">{{ $message }}</p>
    @endif

    @isset($action)
        <div class="mt-6 flex justify-center">{{ $action }}</div>
    @endisset
</div>
