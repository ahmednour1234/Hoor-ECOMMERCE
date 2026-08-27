{{--
    Cart page behaviour.

    Each line owns its quantity and listens for the cart:updated event, so one
    request redraws every affected figure. Nothing here computes a price: the
    formatted strings come from the server's response.
--}}
<script>
    /**
     * A single cart line.
     */
    function cartLine({ id, quantity, max }) {
        return {
            id,
            quantity,
            max,
            busy: false,
            removed: false,
            totalFormatted: null,
            unitFormatted: null,

            init() {
                // The initial values are already server-rendered; these hold
                // whatever the most recent response supplied.
                this.totalFormatted = this.$el.querySelector('[x-text="totalFormatted"]')?.textContent.trim();
                this.unitFormatted = this.$el.querySelector('[x-text="unitFormatted"]')?.textContent.trim();

                // Adopt this line's figures whenever the cart changes, so a
                // change to one line refreshes the rest of the page too.
                window.addEventListener('cart:updated', (event) => {
                    const line = event.detail.lines.find(l => l.variant_id === this.id);

                    if (! line) {
                        this.removed = true;
                        return;
                    }

                    this.quantity = line.quantity;
                    this.max = line.max_quantity;
                    this.totalFormatted = line.total_formatted;
                    this.unitFormatted = line.unit_formatted;
                });
            },

            step(delta) {
                this.setQuantity(this.quantity + delta);
            },

            async setQuantity(next) {
                const target = Math.max(0, Math.min(Number(next) || 0, this.max));

                if (this.busy) return;

                this.busy = true;

                if (target === 0) {
                    await Alpine.store('cart').remove(this.id);
                } else {
                    await Alpine.store('cart').update(this.id, target);
                }

                this.busy = false;
            },

            async remove() {
                if (this.busy) return;

                this.busy = true;
                await Alpine.store('cart').remove(this.id);
                this.busy = false;
            },
        };
    }

    /**
     * The order summary and the page's empty state.
     */
    function cartSummary({ empty, ready, hasSavings }) {
        return {
            empty,
            ready,
            subtotal: null,
            savings: null,
            hasSavings,

            init() {
                // Seed from what the server already rendered, otherwise the
                // binding would blank these until the first update arrives.
                this.subtotal = this.$el.querySelector('[x-text="subtotal"]')?.textContent.trim() ?? '';
                this.savings = this.$el.querySelector('[x-text="savings"]')?.textContent.trim() ?? '';

                window.addEventListener('cart:updated', (event) => {
                    const cart = event.detail;

                    this.empty = cart.empty;
                    this.ready = cart.ready;
                    this.subtotal = cart.totals.subtotal_formatted;
                    this.savings = cart.totals.savings_formatted;
                    this.hasSavings = cart.totals.has_savings;
                });
            },
        };
    }
</script>
