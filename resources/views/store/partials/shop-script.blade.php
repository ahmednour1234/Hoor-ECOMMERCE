{{--
    Shop filtering without a full page load.

    Filter links stay real anchors carrying real URLs — so they remain
    shareable, crawlable, and work with JavaScript off. This intercepts the
    click, fetches the same URL, swaps in the parts of the page that changed,
    and pushes the URL into history so the address bar and the back button
    stay correct.
--}}
<script>
    function shopPage() {
        return {
            loading: false,

            init() {
                // The back and forward buttons must reload the matching view.
                window.addEventListener('popstate', () => this.load(window.location.href, false));
            },

            /**
             * Follow a filter or sort link without navigating.
             */
            navigate(event) {
                const link = event.currentTarget;

                // Let modified clicks (new tab, download) behave normally.
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
                    return;
                }

                event.preventDefault();
                this.load(link.href);
            },

            /**
             * Submit a filter form (price range, sort) without navigating.
             */
            submit(event) {
                event.preventDefault();

                const form = event.currentTarget;
                const query = new URLSearchParams(new FormData(form)).toString();

                this.load(`${form.action}${query ? '?' + query : ''}`);
            },

            /**
             * Fetch a shop URL and swap in the regions that changed.
             *
             * Only the results and the filter panels are replaced, so scroll
             * position, the open state of the drawer and anything else on the
             * page survive.
             */
            async load(url, push = true) {
                if (this.loading) return;

                this.loading = true;

                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });

                    if (! response.ok) {
                        window.location.href = url;
                        return;
                    }

                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');

                    for (const id of ['shop-results', 'shop-filters-desktop', 'shop-filters-drawer', 'shop-toolbar']) {
                        const next = doc.getElementById(id);
                        const current = document.getElementById(id);

                        if (next && current) {
                            current.innerHTML = next.innerHTML;
                        }
                    }

                    document.title = doc.title;

                    if (push) {
                        history.pushState({}, '', url);
                    }

                    // Bring the results into view when filtering from far down
                    // the page, but never yank the viewport on a back press.
                    if (push) {
                        const results = document.getElementById('shop-results');

                        if (results && results.getBoundingClientRect().top < 0) {
                            results.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }
                } catch {
                    // Any failure falls back to a normal navigation, so the
                    // customer is never stranded.
                    window.location.href = url;
                } finally {
                    this.loading = false;
                }
            },
        };
    }
</script>
