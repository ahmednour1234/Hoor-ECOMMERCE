@props([
    'hover'   => false,
    'padding' => true,
])

<div {{ $attributes->merge(['class' => $hover ? 'card-hover' : 'card']) }}>
    @isset($header)
        <div class="card-header">{{ $header }}</div>
    @endisset

    <div @class(['card-body' => $padding])>
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="card-footer">{{ $footer }}</div>
    @endisset
</div>
