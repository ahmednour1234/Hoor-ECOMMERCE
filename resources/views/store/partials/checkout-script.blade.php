{{--
    Checkout behaviour.

    The destination select drives a server-side re-quote: the browser asks the
    server what the order costs and displays the answer. It never computes a
    fee, a discount or a total of its own — those figures must match what the
    order is actually written with.
--}}
<script>
    function checkoutPage({ governorates }) {
        return {
            governorates,
            governorateId: '',
            areaId: '',
            areas: [],
            submitting: false,

            totals: {
                subtotal: @js(\App\Casts\Money::format($summary['subtotal'])),
                discount: null,
                shipping: null,
                total: @js(\App\Casts\Money::format($summary['total'])),
                has_discount: false,
                delivery_days: '',
            },

            init() {
                // Restore the destination after a validation failure, so the
                // customer does not have to choose it again.
                const previous = @js(old('governorate_id'));

                if (previous) {
                    this.governorateId = Number(previous);
                    this.loadAreas();
                    this.areaId = Number(@js(old('area_id'))) || '';
                    this.refreshQuote();
                }
            },

            onGovernorateChange() {
                // A governorate change invalidates the area, so clear it rather
                // than leaving a district from somewhere else selected.
                this.areaId = '';
                this.loadAreas();
                this.refreshQuote();
            },

            loadAreas() {
                this.areas = this.governorates[this.governorateId]?.areas ?? [];
            },

            /**
             * Ask the server what this order now costs.
             */
            async refreshQuote() {
                if (! this.governorateId) return;

                try {
                    const response = await fetch(@js(route('store.checkout.quote')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({
                            governorate_id: this.governorateId,
                            area_id: this.areaId || null,
                        }),
                    });

                    if (! response.ok) return;

                    const data = await response.json();

                    this.totals = {
                        subtotal: data.subtotal,
                        discount: data.discount,
                        shipping: data.shipping,
                        total: data.total,
                        has_discount: data.has_discount,
                        delivery_days: data.delivery_days,
                    };
                } catch {
                    // A failed quote leaves the last known figures on screen;
                    // the order is priced server-side on submit regardless.
                }
            },

            /**
             * Delivery estimate, with the day range substituted in.
             */
            get deliveryNote() {
                if (! this.totals.delivery_days) return '';

                return @js(__('checkout.summary.delivery_in', ['days' => '__DAYS__']))
                    .replace('__DAYS__', this.totals.delivery_days);
            },
        };
    }
</script>
