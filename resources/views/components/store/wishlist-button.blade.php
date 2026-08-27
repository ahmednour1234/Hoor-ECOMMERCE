{{--
    Wishlist toggle.

    Signed in, this posts to the server. Signed out, it remembers the product
    locally and sends her to log in — see store.partials.wishlist-script.

    `saved` lets a listing pass in what it already knows from one query, so a
    grid of cards does not ask the server once per heart.
--}}
@props(['product', 'saved' => false])

<button type="button"
        x-data="wishlistButton({{ $product->id }}, {{ $saved ? 'true' : 'false' }})"
        x-on:click.prevent.stop="toggle()"
        :aria-pressed="active"
        :aria-label="active ? @js(__('store.shop.wishlist.remove')) : @js(__('store.shop.wishlist.add'))"
        {{ $attributes->merge([
            'class' => 'absolute end-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full
                        bg-white/85 text-hoor-navy-600 shadow-card backdrop-blur transition
                        hover:bg-white hover:text-hoor-gold-600',
        ]) }}>
    <svg class="h-4 w-4 transition" :class="active && 'fill-hoor-gold-500 text-hoor-gold-500'"
         fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 20.25s-7.5-4.5-7.5-9.75a4.125 4.125 0 017.5-2.4 4.125 4.125 0 017.5 2.4c0 5.25-7.5 9.75-7.5 9.75z" />
    </svg>
</button>
