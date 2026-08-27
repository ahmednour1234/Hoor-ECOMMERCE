{{--
    Compatibility shim.

    Breeze scaffolding renders authenticated pages through <x-app-layout>.
    HOOR serves those pages inside the storefront shell so a logged-in customer
    never leaves the brand experience.
--}}
<x-layouts.store>
    @isset($header)
        <div class="border-b border-hoor-cream-300 bg-white">
            <div class="hoor-container py-6">
                {{ $header }}
            </div>
        </div>
    @endisset

    <div class="hoor-container py-10">
        {{ $slot }}
    </div>
</x-layouts.store>
