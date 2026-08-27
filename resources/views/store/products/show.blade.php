<x-layouts.store>
    {{-- Both values must be strings: @section() with a null second argument is
         read as a block opener, so Blade starts a buffer waiting for an
         @endsection that never arrives. A product saved without SEO copy is the
         ordinary case, not an edge case. --}}
    @section('title', ($product->meta_title ?: $product->name).' — '.__('common.brand'))
    @section('description', (string) ($product->meta_description
        ?: $product->short_description
        ?: __('store.meta.home_description')))

    <div class="hoor-container py-8 lg:py-12">

        {{-- Breadcrumb --}}
        <nav class="mb-6 flex flex-wrap items-center gap-2 text-xs text-hoor-muted"
             aria-label="{{ __('nav.menu') }}">
            <a href="{{ route('store.home') }}" class="transition hover:text-hoor-navy-600">
                {{ __('nav.home') }}
            </a>
            <span aria-hidden="true" class="rtl:rotate-180">&rsaquo;</span>

            <a href="{{ route('store.shop') }}" class="transition hover:text-hoor-navy-600">
                {{ __('nav.shop') }}
            </a>

            @if ($product->category)
                <span aria-hidden="true" class="rtl:rotate-180">&rsaquo;</span>
                <a href="{{ route('store.shop', ['category' => $product->category->slug]) }}"
                   class="transition hover:text-hoor-navy-600">
                    {{ $product->category->name }}
                </a>
            @endif

            <span aria-hidden="true" class="rtl:rotate-180">&rsaquo;</span>
            <span class="text-hoor-navy-600" aria-current="page">{{ $product->name }}</span>
        </nav>

        <div class="grid gap-8 lg:grid-cols-2 lg:gap-14">

            {{-- Gallery --}}
            <div class="lg:sticky lg:top-24 lg:self-start">
                <x-store.product-gallery :product="$product" />
            </div>

            {{-- Buying panel --}}
            <div>
                @if ($product->category)
                    <p class="text-xs tracking-wide text-hoor-muted">{{ $product->category->name }}</p>
                @endif

                <h1 class="mt-1 font-display text-3xl leading-tight text-hoor-navy-700 sm:text-4xl">
                    {{ $product->name }}
                </h1>

                @if ($product->short_description)
                    <p class="mt-3 text-sm leading-relaxed text-hoor-muted">
                        {{ $product->short_description }}
                    </p>
                @endif

                <div class="mt-6">
                    <x-store.variant-selector
                        :product="$product"
                        :colors="$colors"
                        :sizes="$sizes"
                        :matrix="$matrix"
                        :selected="$selected" />
                </div>

                {{-- Reassurance, repeating the promises that drive conversion. --}}
                <ul class="mt-6 space-y-2 text-xs text-hoor-muted">
                    @foreach (['cod', 'shipping', 'exchange'] as $promise)
                        <li class="flex items-center gap-2">
                            <span class="h-1 w-1 shrink-0 rounded-full bg-hoor-gold-500" aria-hidden="true"></span>
                            {{ __("store.promise.{$promise}.body") }}
                        </li>
                    @endforeach
                </ul>

                <x-store.product-details :product="$product" />
            </div>
        </div>

        {{-- Related products --}}
        @if ($related->isNotEmpty())
            <section class="mt-16 lg:mt-24">
                <x-store.section-title
                    :title="__('store.product.related')"
                    :href="route('store.shop')"
                    :link-text="__('store.featured.view_all')" />

                <x-store.product-grid :products="$related" />
            </section>
        @endif
    </div>

    @include('store.partials.variant-selector-script')
</x-layouts.store>
