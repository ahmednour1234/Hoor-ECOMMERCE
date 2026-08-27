{{--
    Lookbook / Instagram gallery.

    A masonry-style mosaic of brand photography. Tiles are decorative, so their
    alt text is empty and the section carries one accessible name instead of
    repeating the brand on every image.
--}}
@php
    $disk = \Illuminate\Support\Facades\Storage::disk(config('hoor.media.disk'));

    // Mixed aspect ratios keep the mosaic from reading as a plain grid. The
    // square flat-lays are placed where a 1:1 crop is intended.
    $tiles = [
        ['file' => 'products/hoor-1.png',  'span' => 'sm:col-span-2 sm:row-span-2', 'ratio' => 'aspect-square'],
        ['file' => 'products/hoor-7.png',  'span' => '',                            'ratio' => 'aspect-square'],
        ['file' => 'products/hoor-3.png',  'span' => '',                            'ratio' => 'aspect-square'],
        ['file' => 'products/hoor-10.png', 'span' => '',                            'ratio' => 'aspect-square'],
        ['file' => 'products/hoor-6.png',  'span' => '',                            'ratio' => 'aspect-square'],
    ];

    // From settings rather than config, so the admin owns it.
    $instagram = $contact->socials()['instagram'] ?? null;
@endphp

<section class="hoor-container py-16 lg:py-20" aria-label="{{ __('store.lookbook.title') }}">
    <x-store.section-title
        :eyebrow="__('store.lookbook.eyebrow')"
        :title="__('store.lookbook.title')"
        :lead="__('store.lookbook.lead')"
        :href="$instagram"
        :link-text="__('store.lookbook.follow')" />

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ($tiles as $index => $tile)
            <a href="{{ $instagram }}"
               target="_blank" rel="noopener noreferrer"
               class="group relative overflow-hidden rounded-md bg-hoor-cream-100
                      {{ $tile['span'] }} {{ $tile['ratio'] }}">

                <img src="{{ $disk->url($tile['file']) }}"
                     alt=""
                     loading="lazy"
                     decoding="async"
                     class="h-full w-full object-cover transition duration-700 ease-hoor
                            group-hover:scale-105">

                <span class="absolute inset-0 flex items-center justify-center bg-hoor-navy-900/0
                             transition duration-300 group-hover:bg-hoor-navy-900/35
                             group-focus-visible:bg-hoor-navy-900/35">
                    <span class="translate-y-2 text-xs font-medium tracking-wide text-hoor-cream-50
                                 opacity-0 transition duration-300
                                 group-hover:translate-y-0 group-hover:opacity-100
                                 group-focus-visible:translate-y-0 group-focus-visible:opacity-100">
                        {{ config('hoor.brand.name_en') }}
                    </span>
                </span>
            </a>
        @endforeach
    </div>
</section>
