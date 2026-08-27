{{--
    One way to reach the shop.

    Rendered as a link when there is somewhere to go, and as a plain panel
    otherwise — a card that looks clickable but is not is worse than one that
    does not pretend. The two branches share their inner markup through a
    slot-less partial so the duplication is only the wrapper.
--}}
@props([
    'icon',
    'title',
    'value',
    'href' => null,
    'note' => null,
    'external' => false,
])

@php
    $classes = 'card flex h-full items-start gap-3 p-4 transition'
        .($href ? ' hover:border-hoor-gold-500 hover:shadow-card-hover' : '');
@endphp

<li>
    @if ($href)
        <a href="{{ $href }}"
           @if ($external) target="_blank" rel="noopener noreferrer" @endif
           class="{{ $classes }}">
            @include('components.store.partials.contact-card-body', compact('icon', 'title', 'value', 'note'))
        </a>
    @else
        <div class="{{ $classes }}">
            @include('components.store.partials.contact-card-body', compact('icon', 'title', 'value', 'note'))
        </div>
    @endif
</li>
