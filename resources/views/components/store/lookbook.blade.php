{{--
    Lookbook / Instagram gallery.

    A masonry-style mosaic of brand photography. Tiles are decorative, so their
    alt text is empty and the section carries one accessible name instead of
    repeating the brand on every image.
--}}
@php
    $disk = \Illuminate\Support\Facades\Storage::disk(config('hoor.media.disk'));

    /*
     * Mixed spans keep the mosaic from reading as a plain grid.
     *
     * `focus` decides where the square crop is taken from. The sources are 4:5
     * portraits, so a 1:1 tile discards a fifth of the height — centred, that
     * is 10% off the top, which is exactly where the head is. The portraits
     * are therefore anchored high; the flat-lay is already square and keeps
     * the centre.
     */
    $tiles = [
        ['file' => 'products/hoor-1.png',  'span' => 'sm:col-span-2 sm:row-span-2', 'focus' => 'object-[50%_18%]'],
        ['file' => 'products/hoor-7.png',  'span' => '',                            'focus' => 'object-[50%_18%]'],
        ['file' => 'products/hoor-3.png',  'span' => '',                            'focus' => 'object-[50%_15%]'],
        ['file' => 'products/hoor-10.png', 'span' => '',                            'focus' => 'object-center'],
        ['file' => 'products/hoor-6.png',  'span' => '',                            'focus' => 'object-[50%_15%]'],
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
               class="group relative aspect-square overflow-hidden rounded-md bg-hoor-cream-100
                      {{ $tile['span'] }}">

                <img src="{{ $disk->url($tile['file']) }}"
                     alt=""
                     loading="lazy"
                     decoding="async"
                     class="h-full w-full object-cover transition duration-700 ease-hoor
                            group-hover:scale-105 {{ $tile['focus'] }}">

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
