{{--
    An auth card's title, subtitle and ornament divider.

    Shared so login and register cannot drift apart — they are the same card
    with different words.
--}}
@props(['title', 'subtitle' => null])

<div class="text-center">
    <h1 class="font-display text-3xl italic text-hoor-navy-700">{{ $title }}</h1>

    @if ($subtitle)
        <p class="mt-2 text-sm text-hoor-navy-600/70">{{ $subtitle }}</p>
    @endif

    {{-- Rule, ornament, rule. Decorative, so hidden from assistive tech. --}}
    <div class="mt-5 flex items-center justify-center gap-3" aria-hidden="true">
        <span class="h-px w-14 bg-gradient-to-r from-transparent to-hoor-gold-500/60"></span>

        <svg class="h-3.5 w-3.5 text-hoor-gold-500" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.2">
            <path d="M12 3l2.4 6.6L21 12l-6.6 2.4L12 21l-2.4-6.6L3 12l6.6-2.4L12 3z" />
        </svg>

        <span class="h-px w-14 bg-gradient-to-l from-transparent to-hoor-gold-500/60"></span>
    </div>
</div>
