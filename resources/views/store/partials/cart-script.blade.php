{{--
    Client-side cart behaviour.

    One Alpine store shared by the header badge, the product page and the cart
    page. Every mutation posts to the same routes the non-JS forms use and
    redraws from the server's response, so totals are never computed in the
    browser and the pages stay correct with JavaScript disabled.
--}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('cart', {
            count: @js(app(\App\Services\CartService::class)->count()),
            busy: false,

            /**
             * Send a cart mutation and adopt the server's recalculated figures.
             *
             * The server is the only thing that decides quantities and totals;
             * this just relays the request and broadcasts what came back.
             */
            async send(url, { method = 'POST', body = {} } = {}) {
                if (this.busy) return null;

                this.busy = true;

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        // Laravel reads the intended verb from _method, which
                        // keeps PATCH and DELETE working through a POST.
                        body: JSON.stringify({ ...body, _method: method }),
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (payload.cart) {
                        this.count = payload.cart.count;
                        window.dispatchEvent(new CustomEvent('cart:updated', { detail: payload.cart }));
                    }

                    if (payload.message) {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: payload.message, variant: response.ok ? 'success' : 'error' },
                        }));
                    }

                    // Validation errors arrive as a Laravel error bag.
                    if (! response.ok && payload.errors) {
                        const first = Object.values(payload.errors)[0]?.[0];

                        if (first) {
                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: { message: first, variant: 'error' },
                            }));
                        }
                    }

                    return response.ok ? payload : null;
                } catch {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: @js(__('cart.errors.network')), variant: 'error' },
                    }));

                    return null;
                } finally {
                    this.busy = false;
                }
            },

            add(url, variantId, quantity) {
                return this.send(url, { body: { variant_id: variantId, quantity } });
            },

            update(variantId, quantity) {
                return this.send(@js(route('store.cart.update')), {
                    method: 'PATCH',
                    body: { variant_id: variantId, quantity },
                });
            },

            remove(variantId) {
                return this.send(
                    @js(route('store.cart.destroy', ['variant' => '__ID__'])).replace('__ID__', variantId),
                    { method: 'DELETE' },
                );
            },

            clear() {
                return this.send(@js(route('store.cart.clear')), { method: 'DELETE' });
            },
        });
    });
</script>
