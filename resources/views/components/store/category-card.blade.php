{{-- Category tile: image with a navy scrim so the label stays legible. --}}
@props([
    'category',
    'eager' => false,
])

@php
    /*
     * The shop filters by category slug, so the tile links straight into a
     * filtered listing.
     *
     * There is no dedicated category page: `store.categories.show` was never
     * defined, and the old Route::has() guard quietly fell back to the
     * homepage — which made every tile a link to nowhere.
     */
    $url = route('store.shop', ['category' => $category->slug]);

    $count = $category->products_count ?? null;
@endphp

<a href="{{ $url }}"
   {{ $attributes->merge([
       'class' => 'group relative block aspect-[3/4] overflow-hidden rounded-md bg-hoor-navy-500',
   ]) }}>

    @php
        // The uploaded banner wins; otherwise a photo from a product in this
        // branch, so the tile always shows real imagery.
        $cover = $category->image
            ? $category->imageUrl()
            : ($category->cover_path
                ? \Illuminate\Support\Facades\Storage::disk(config('hoor.media.disk'))->url($category->cover_path)
                : null);
    @endphp

    @if ($cover)
        <img src="{{ $cover }}"
             alt=""
             loading="{{ $eager ? 'eager' : 'lazy' }}"
             decoding="async"
             class="h-full w-full object-cover transition duration-700 ease-hoor
                    group-hover:scale-105">
    @else
        {{-- Nothing to show: a brand-toned panel rather than a broken tile. --}}
        <span class="absolute inset-0 bg-gradient-to-br from-hoor-navy-500 to-hoor-denim-600"></span>
    @endif

    <span class="absolute inset-0 bg-gradient-to-t from-hoor-navy-900/85 via-hoor-navy-900/30 to-hoor-navy-900/5"></span>

    <span class="absolute inset-x-0 bottom-0 p-5">
        <span class="block font-display text-xl text-hoor-cream-50">{{ $category->name }}</span>

        @if ($count)
            <span class="mt-1 block text-xs text-hoor-cream-50/70">
                {{ trans_choice('store.categories.count', $count, ['count' => $count]) }}
            </span>
        @endif

        <span class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium tracking-wide
                     text-hoor-gold-500 opacity-0 transition duration-300
                     group-hover:opacity-100 group-focus-visible:opacity-100">
            {{ __('common.actions.shop_now') }}
            <span class="rtl:rotate-180" aria-hidden="true">&rarr;</span>
        </span>
    </span>
</a>
