{{--
    A grid of product cards, shared by every rail on the homepage and by the
    shop listing. Renders nothing when the collection is empty so a section can
    hide itself rather than showing an empty shelf.
--}}
@props([
    'products',
    'eager'   => 0,   // How many leading cards should load their image eagerly.
    'columns' => 4,
])

@php
    $grid = [
        3 => 'sm:grid-cols-2 lg:grid-cols-3',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
    ][$columns] ?? 'sm:grid-cols-2 lg:grid-cols-4';
@endphp

@if ($products->isNotEmpty())
    {{-- `reveal-group` lets the site-wide observer stagger the cards; each
         card opts in with `reveal`. See store.partials.reveal-script. --}}
    <div {{ $attributes->merge(['class' => 'reveal-group grid grid-cols-2 gap-x-4 gap-y-8 sm:gap-x-6 '.$grid]) }}>
        @foreach ($products as $index => $product)
            <x-store.product-card :product="$product" :eager="$index < $eager" class="reveal" />
        @endforeach
    </div>
@endif
