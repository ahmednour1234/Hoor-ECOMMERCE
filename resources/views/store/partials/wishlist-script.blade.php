{{--
    Wishlist persistence, shared by every wishlist button on the page.

    Included once by the store layout rather than pushed from the button
    component: that component renders many times per page, and an @once block
    nested inside it leaves an output buffer open — which PHPUnit flags as risky
    and which would leak on real responses too.

    Signed in, the toggle posts to the server and the wishlist follows the
    customer between devices. Signed out, there is nowhere to save it, so the
    button sends her to log in — and the product she was looking at is
    remembered locally so the heart is already filled when she returns.
--}}
<script>
    /**
     * Wishlist state.
     *
     * localStorage can throw in private modes and is wrapped accordingly, so a
     * blocked write degrades to an in-memory toggle rather than breaking the
     * page.
     */
    function wishlistButton(productId, initiallySaved = false) {
        const KEY = 'hoor.wishlist';
        const authenticated = @json(auth()->check());
        const endpoint = @json(auth()->check() ? url(app()->getLocale().'/account/wishlist') : null);
        const loginUrl = @json(route('login'));

        const read = () => {
            try { return JSON.parse(localStorage.getItem(KEY)) || []; }
            catch { return []; }
        };

        const write = (ids) => {
            try { localStorage.setItem(KEY, JSON.stringify(ids)); }
            catch { /* storage unavailable — keep the in-memory state */ }
        };

        return {
            active: initiallySaved,
            busy: false,

            init() {
                // The server is the authority when signed in; the local list is
                // only a guest's memory of what she liked.
                if (! authenticated) {
                    this.active = read().includes(productId);
                }
            },

            async toggle() {
                if (this.busy) return;

                if (! authenticated) {
                    // Remember the intent, then ask her to sign in — a wishlist
                    // has to belong to someone to be worth keeping.
                    const ids = read();
                    if (! ids.includes(productId)) write([...ids, productId]);

                    window.location.href = loginUrl;
                    return;
                }

                this.busy = true;
                const previous = this.active;

                // Flip immediately: the network round trip should not make the
                // button feel unresponsive.
                this.active = ! this.active;

                try {
                    const response = await fetch(`${endpoint}/${productId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                    });

                    if (! response.ok) throw new Error(response.statusText);

                    const data = await response.json();
                    this.active = data.saved;

                    window.dispatchEvent(new CustomEvent('wishlist:changed', {
                        detail: { count: data.count, saved: data.saved, message: data.message },
                    }));
                } catch {
                    // Put the heart back where it was rather than leaving it
                    // showing a state the server never accepted.
                    this.active = previous;
                } finally {
                    this.busy = false;
                }
            },
        };
    }
</script>
