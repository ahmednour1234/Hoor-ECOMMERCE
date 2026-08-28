{{--
    "Use my current location" — drops a pin for the courier.

    Deliberately not a map. A map library on the checkout page is weight on the
    most important page in the shop, and the courier does not need the customer
    to place a marker precisely: he needs a coordinate to open in his own maps
    app alongside the written address.

    The address fields stay required and unchanged. This supplements them; it
    never replaces them, because a courier in Egypt reads the street name and
    the landmark too.
--}}
@props(['latitude' => null, 'longitude' => null])

<div x-data="locationPicker({
        lat: @js($latitude),
        lng: @js($longitude),
     })"
     {{ $attributes->merge(['class' => 'sm:col-span-2']) }}>

    <div class="flex flex-wrap items-center gap-3 rounded-md border border-hoor-cream-300
                bg-hoor-cream-50 p-4">

        <button type="button"
                x-on:click="locate()"
                x-bind:disabled="busy"
                class="flex items-center gap-2 rounded-md border border-hoor-cream-300 bg-white
                       px-4 py-2 text-sm font-medium text-hoor-navy-700 transition
                       hover:border-hoor-navy-300 hover:shadow-card
                       disabled:cursor-not-allowed disabled:opacity-60
                       focus-visible:outline focus-visible:outline-2
                       focus-visible:outline-offset-2 focus-visible:outline-hoor-denim-500">

            {{-- A crosshair, not a pin: the pin is the result, this is the act
                 of finding. --}}
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6"
                 viewBox="0 0 24 24" aria-hidden="true"
                 x-bind:class="busy && 'animate-spin'">
                <circle cx="12" cy="12" r="7" />
                <circle cx="12" cy="12" r="2" fill="currentColor" stroke="none" />
                <path stroke-linecap="round" d="M12 2v3M12 19v3M2 12h3M19 12h3" />
            </svg>

            {{-- The label is real markup, not only an Alpine expression.
                 Blade's @js() escapes non-Latin text into unicode escape
                 sequences: the browser renders them, but the words never
                 appear in the HTML, leaving the button wordless with
                 JavaScript off. Alpine only swaps it while working. --}}
            <span x-text="busy ? locatingLabel : (hasPin ? againLabel : useLabel)">{{ __('checkout.location.use') }}</span>
        </button>

        {{-- Confirmation, with a link so she can check the pin is right before
             trusting it. --}}
        <template x-if="hasPin && ! error">
            <span class="flex items-center gap-2 text-sm text-emerald-700">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>

                <span>{{ __('checkout.location.saved') }}</span>

                <a x-bind:href="`https://maps.google.com/?q=${lat},${lng}`"
                   target="_blank" rel="noopener noreferrer"
                   class="underline decoration-dotted underline-offset-2 hover:text-emerald-800">
                    {{ __('checkout.location.check') }}
                </a>
            </span>
        </template>

        <template x-if="error">
            <span class="text-sm text-red-600" x-text="error"></span>
        </template>

        <template x-if="! hasPin && ! error && ! busy">
            <span class="text-xs text-hoor-muted">{{ __('checkout.location.hint') }}</span>
        </template>
    </div>

    {{-- The values the form actually posts. Hidden rather than shown: a
         customer has no use for her own coordinates, and a text field would
         invite editing them into nonsense. --}}
    <input type="hidden" name="latitude" x-bind:value="lat">
    <input type="hidden" name="longitude" x-bind:value="lng">
</div>

@push('scripts')
    <script>
            /**
             * Ask the browser where we are.
             *
             * Geolocation needs a secure context — HTTPS, or localhost — so on
             * a plain-HTTP staging site the browser refuses and the customer is
             * told why rather than left with a button that silently does
             * nothing.
             */
            function locationPicker({ lat, lng }) {
                return {
                    lat: lat ?? '',
                    lng: lng ?? '',
                    busy: false,
                    error: '',

                    useLabel: @js(__('checkout.location.use')),
                    againLabel: @js(__('checkout.location.again')),
                    locatingLabel: @js(__('checkout.location.locating')),

                    get hasPin() {
                        return this.lat !== '' && this.lng !== '';
                    },

                    locate() {
                        this.error = '';

                        if (! navigator.geolocation) {
                            this.error = @js(__('checkout.location.unsupported'));
                            return;
                        }

                        if (! window.isSecureContext) {
                            this.error = @js(__('checkout.location.insecure'));
                            return;
                        }

                        this.busy = true;

                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                // Seven decimals is about a centimetre, well
                                // beyond what a phone's GPS offers, and matches
                                // the column's precision.
                                this.lat = position.coords.latitude.toFixed(7);
                                this.lng = position.coords.longitude.toFixed(7);
                                this.busy = false;
                            },
                            (failure) => {
                                this.busy = false;

                                // Denied is the common case and deserves its
                                // own message: she needs to know it is her
                                // browser refusing, not the shop failing.
                                this.error = failure.code === failure.PERMISSION_DENIED
                                    ? @js(__('checkout.location.denied'))
                                    : @js(__('checkout.location.failed'));
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 10000,
                                maximumAge: 0,
                            },
                        );
                    },
                };
            }
    </script>
@endpush
