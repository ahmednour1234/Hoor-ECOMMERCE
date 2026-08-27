{{--
    A titled section of a back-office page.

    Distinct from <x-admin.card>, which is the dashboard stat tile: this is the
    container for a block of detail — a table, a form, a definition list.
--}}
@props(['title' => null])

<section {{ $attributes->merge(['class' => 'card p-5']) }}>
    @if ($title)
        <h3 class="mb-4 font-display text-lg text-hoor-navy-700">{{ $title }}</h3>
    @endif

    {{ $slot }}
</section>
