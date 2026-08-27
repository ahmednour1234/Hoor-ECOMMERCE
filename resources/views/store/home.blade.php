{{--
    HOOR storefront homepage.

    Every section is a component and every product comes from the repository —
    there is no hardcoded product markup anywhere on this page. Rails hide
    themselves when their query returns nothing, so a young catalog never shows
    an empty shelf.
--}}
<x-layouts.store>
    @section('title', __('store.meta.home_title'))
    @section('description', __('store.meta.home_description'))

    @php
        $shopUrl = \Illuminate\Support\Facades\Route::has('store.shop')
            ? route('store.shop')
            : route('store.home');
    @endphp

    {{--
        Each section is shown only if the admin has it switched on. The flags
        come from settings (see SettingsRegistry::homepage), so the shape of the
        page is the shop's decision rather than this file's.
    --}}

    {{-- 1. Hero slider (the announcement bar and navbar live in the layout header) --}}
    @if ($show('hero'))
        <x-store.hero-slider :slides="$slides" />
    @endif

    {{-- 2. Brand benefits --}}
    @if ($show('benefits'))
        <x-store.benefits />
    @endif

    {{-- 3. Shop by category --}}
    @if ($show('categories') && $categories->isNotEmpty())
        <section class="hoor-container py-16 lg:py-20">
            <x-store.section-title
                class="reveal reveal-soft"
                :eyebrow="__('store.categories.eyebrow')"
                :title="__('store.categories.title')"
                :lead="__('store.categories.lead')" />

            {{-- A rail rather than a grid: a wrapping grid strands a partial
                 second row whenever the category count is not a multiple of
                 four, which reads as unfinished. --}}
            <x-store.category-slider :categories="$categories" />
        </section>
    @endif

    {{-- 4. New arrivals --}}
    @if ($show('new_arrivals') && $sections['new_arrivals']->isNotEmpty())
        <section id="new-arrivals" class="scroll-mt-24 bg-hoor-cream-100">
            <div class="hoor-container py-16 lg:py-20">
                <x-store.section-title
                    class="reveal reveal-soft"
                    :eyebrow="__('store.new_arrivals.eyebrow')"
                    :title="__('store.new_arrivals.title')"
                    :lead="__('store.new_arrivals.lead')"
                    :href="$shopUrl"
                    :link-text="__('store.new_arrivals.view_all')" />

                <x-store.product-grid :products="$sections['new_arrivals']" :eager="2" />
            </div>
        </section>
    @endif

    {{-- 5. Featured denim collection banner, or an admin-managed promo --}}
    @if ($show('promo_banner'))
        @if ($promoBanner)
            <x-store.promo-banner :banner="$promoBanner" />
        @else
            <x-store.collection-banner :products="$sections['on_sale']" />
        @endif
    @endif

    {{-- 6. Featured products --}}
    @if ($show('featured') && $sections['featured']->isNotEmpty())
        <section class="hoor-container py-16 lg:py-20">
            <x-store.section-title
                :eyebrow="__('store.featured.eyebrow')"
                {{-- The admin may retitle the rail; otherwise the default. --}}
                :title="$featuredTitle ?: __('store.featured.title')"
                :lead="__('store.featured.lead')"
                :href="$shopUrl"
                :link-text="__('store.featured.view_all')" />

            <x-store.product-grid :products="$sections['featured']" />
        </section>
    @endif

    {{-- 7. Quality promise banner --}}
    <x-store.quality-banner />

    {{-- 8. Why choose HOOR --}}
    @if ($show('why_hoor'))
        <x-store.why-hoor />
    @endif

    {{-- 9. Lookbook / Instagram gallery --}}
    @if ($show('lookbook'))
        <x-store.lookbook />
    @endif

    {{-- 10. Newsletter --}}
    @if ($show('newsletter'))
        <x-store.newsletter />
    @endif
</x-layouts.store>
