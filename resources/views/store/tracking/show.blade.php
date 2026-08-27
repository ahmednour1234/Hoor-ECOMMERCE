<x-layouts.store>
    @section('title', __('tracking.show.title', ['number' => $order->number]))

    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <header class="mb-8">
            <h1 class="font-display text-2xl text-hoor-navy-700" dir="ltr">
                {{ __('tracking.show.title', ['number' => $order->number]) }}
            </h1>
        </header>

        <x-store.order-summary :order="$order" />

        <div class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-hoor-cream-300 pt-6">
            <p class="text-xs text-hoor-muted">{{ __('tracking.show.help') }}</p>

            <x-ui.button variant="ghost" size="sm" :href="route('store.tracking.index')">
                {{ __('tracking.show.another') }}
            </x-ui.button>
        </div>
    </div>
</x-layouts.store>
