{{--
    Language switcher.

    Links point at the same page in the target locale, so switching language
    never costs the visitor their place in the journey.
--}}
@props(['tone' => 'navy'])

@php
    $toneClasses = $tone === 'light'
        ? 'text-hoor-cream-50/80 hover:text-hoor-cream-50'
        : 'text-hoor-navy-600 hover:text-hoor-navy-800';
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-1 text-sm']) }}>
    @foreach (\App\Support\Locale::alternates() as $code => $meta)
        <a href="{{ route('locale.switch', ['locale' => $code]) }}"
           class="{{ $toneClasses }} rounded-sm px-2 py-1 font-medium transition"
           hreflang="{{ $code }}"
           lang="{{ $code }}"
           rel="alternate">
            {{ $meta['native'] }}
        </a>
    @endforeach
</div>
