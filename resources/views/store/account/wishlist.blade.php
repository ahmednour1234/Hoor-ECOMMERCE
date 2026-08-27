<x-layouts.account :title="__('account.wishlist.title')">

    @if ($products->isEmpty())
        <div class="card p-10 text-center">
            <p class="text-hoor-muted">{{ __('account.wishlist.empty') }}</p>

            <x-ui.button variant="primary" class="mt-6" :href="route('store.shop')">
                {{ __('account.wishlist.browse') }}
            </x-ui.button>
        </div>
    @else
        {{-- Every product here is saved by definition, so each heart starts
             filled without asking the server per card. --}}
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($products as $product)
                <x-store.product-card :product="$product" :saved="true" />
            @endforeach
        </div>

        <div class="mt-8">{{ $products->links() }}</div>
    @endif
</x-layouts.account>
