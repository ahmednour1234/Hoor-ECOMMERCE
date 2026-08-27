{{--
    Line icons for the About page.

    Inline SVG rather than an icon font or sprite: there are nine of them, they
    are used once each, and a request for a font file would cost more than the
    markup does. `currentColor` throughout, so each usage sets the colour with a
    text class — navy on cream, gold on the navy band.
--}}
@props(['name'])

@php
    $paths = [
        // A gem: premium quality.
        'gem' => '<path d="M6 3h12l3 6-9 12L3 9l3-6z"/><path d="M3 9h18M9 3l3 18M15 3l-3 18"/>',

        // A leaf: modest by design, sustainability.
        'leaf' => '<path d="M11 20A7 7 0 019.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/><path d="M2 21c0-3 1.85-5.36 5.08-6"/>',

        // A heart: made for you.
        'heart' => '<path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>',

        // A clock: timeless.
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',

        // A shield: coverage, durability, integrity.
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',

        // A sparkle: flattering fits, empowerment.
        'sparkle' => '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15z"/>',

        // A feather: everyday comfort.
        'feather' => '<path d="M20.24 12.24a6 6 0 00-8.49-8.49L5 10.5V19h8.5l6.74-6.76z"/><path d="M16 8L2 22M17.5 15H9"/>',

        // A needle and thread: craftsmanship.
        'needle' => '<path d="M3 21L21 3"/><path d="M15 3h6v6"/><path d="M9 15a3 3 0 100 6 3 3 0 000-6z"/>',

        // A rosette: carefully inspected.
        'badge' => '<circle cx="12" cy="9" r="6"/><path d="M8.2 14.3L7 22l5-2.5L17 22l-1.2-7.7"/>',

        // A crescent: faith and modesty.
        'moon' => '<path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/>',

        // Figures: community.
        'people' => '<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0112 0"/><path d="M16 5.2a3 3 0 010 5.6M18 20a6 6 0 00-3-5.2"/>',

        // ------------------------------------------------------ Contact page

        'phone' => '<path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1 1 .4 1.9.7 2.8a2 2 0 01-.4 2.1L8.1 9.9a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.5c.9.4 1.8.6 2.8.7a2 2 0 011.7 2z"/>',

        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 6 10-6"/>',

        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="3.5"/><circle cx="17" cy="7" r=".6" fill="currentColor"/>',

        // A delivery van.
        'truck' => '<path d="M3 16V6a1 1 0 011-1h10v11H3z"/><path d="M14 8h3.5l2.5 3v5H14V8z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',

        // Arrows returning: easy returns.
        'refresh' => '<path d="M3 12a9 9 0 0115.5-6.2M21 12a9 9 0 01-15.5 6.2"/><path d="M18.5 3v3h-3M5.5 21v-3h3"/>',

        // A wallet: cash on delivery.
        'wallet' => '<rect x="2.5" y="6" width="19" height="13" rx="2"/><path d="M2.5 10h19"/><circle cx="17" cy="14.5" r="1.2" fill="currentColor"/>',

        // A speech bubble: support.
        'chat' => '<path d="M21 12a8 8 0 01-8 8H8l-5 3 1.2-4.4A8 8 0 1121 12z"/>',

        // A map pin, outlined.
        'pin' => '<path d="M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
    ];

    $path = $paths[$name] ?? $paths['sparkle'];
@endphp

<svg {{ $attributes->merge(['class' => 'h-6 w-6']) }}
     viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true">
    {!! $path !!}
</svg>
