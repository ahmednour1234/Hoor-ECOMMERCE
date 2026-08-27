<x-layouts.store>
    @section('title', __('tracking.title'))

    <div class="mx-auto max-w-lg px-4 py-16 sm:px-6">
        <header class="text-center">
            <h1 class="font-display text-3xl text-hoor-navy-700">{{ __('tracking.title') }}</h1>
            <p class="mt-3 text-sm text-hoor-muted">{{ __('tracking.subtitle') }}</p>
        </header>

        <form method="POST" action="{{ route('store.tracking.lookup') }}" class="card mt-8 space-y-5 p-6">
            @csrf

            {{-- The order number is not a secret and the phone is not either,
                 but together they are something only she and the shop hold. --}}
            <x-ui.input name="number"
                        :label="__('tracking.fields.number')"
                        :hint="__('tracking.fields.number_hint')"
                        :value="old('number')"
                        dir="ltr"
                        autocomplete="off"
                        required />

            <x-ui.input name="phone"
                        type="tel"
                        :label="__('tracking.fields.phone')"
                        :hint="__('tracking.fields.phone_hint')"
                        :value="old('phone')"
                        dir="ltr"
                        inputmode="numeric"
                        autocomplete="tel"
                        required />

            <x-ui.button type="submit" variant="primary" class="w-full">
                {{ __('tracking.submit') }}
            </x-ui.button>
        </form>

        @auth
            <p class="mt-6 text-center text-sm text-hoor-muted">
                <a href="{{ route('store.account.orders.index') }}"
                   class="text-hoor-denim-600 hover:text-hoor-denim-700">
                    {{ __('account.orders.title') }}
                </a>
            </p>
        @endauth
    </div>
</x-layouts.store>
