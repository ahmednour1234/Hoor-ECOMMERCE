{{-- Section heading with an optional eyebrow and a trailing link. --}}
@props([
    'eyebrow' => null,
    'title'   => null,
    'lead'    => null,
    'href'    => null,
    'linkText'=> null,
    'align'   => 'start',   // start | center
])

{{--
    `items-end` is right for the default row layout, where the trailing link
    should sit on the heading's baseline. It is wrong for the centred column,
    where it pushes the whole block to the trailing edge — so the two layouts
    set their own cross-axis alignment rather than sharing one.
--}}
<div {{ $attributes->merge([
    'class' => 'mb-8 flex flex-wrap gap-4 '
        .($align === 'center'
            ? 'flex-col items-center text-center'
            : 'items-end justify-between'),
]) }}>
    <div class="{{ $align === 'center' ? 'mx-auto max-w-2xl' : '' }}">
        @if ($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif

        <h2 class="mt-2 section-title">{{ $title ?? $slot }}</h2>

        @if ($lead)
            <p class="mt-3 max-w-xl text-sm leading-relaxed text-hoor-muted
                      {{ $align === 'center' ? 'mx-auto' : '' }}">{{ $lead }}</p>
        @endif
    </div>

    @if ($href && $linkText)
        <a href="{{ $href }}"
           class="group inline-flex items-center gap-1.5 text-sm font-medium text-hoor-navy-600
                  transition hover:text-hoor-gold-600">
            {{ $linkText }}
            {{-- Arrow flips with the writing direction. --}}
            <span class="transition-transform group-hover:translate-x-1 rtl:rotate-180
                         rtl:group-hover:-translate-x-1" aria-hidden="true">&rarr;</span>
        </a>
    @endif
</div>
