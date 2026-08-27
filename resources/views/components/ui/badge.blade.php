@props([
    'variant' => 'neutral',  // navy | denim | gold | neutral | success | warning | danger
])

<span {{ $attributes->merge(['class' => 'badge-'.$variant]) }}>
    {{ $slot }}
</span>
