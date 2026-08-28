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

            // Appending the next page rather than replacing the grid. Distinct
            // from `loading`, which dims the results during a filter change —
            // the two must not block each other's spinner.
            appending: false,

            // Only taken over from the numbered pager once we know the browser
            // can actually drive it. Without IntersectionObserver the real
            // links stay on screen rather than leaving no way forward.
            infinite: false,

            nextUrl: null,
            loadedCount: 0,
            totalCount: 0,

            observer: null,

            init() {
                // The back and forward buttons must reload the matching view.
                window.addEventListener('popstate', () => this.load(window.location.href, false));

                this.infinite = 'IntersectionObserver' in window;

                this.readPager();
                this.$nextTick(() => this.watchSentinel());
            },

            /**
             * Take the paging state from the markup the server just rendered.
             *
             * The server is the only authority on what page comes next and how
             * many results there are, so it is re-read after every swap rather
             * than tracked in JavaScript and allowed to drift.
             */
            readPager() {
                const pager = document.getElementById('shop-pager');

                this.nextUrl     = pager?.dataset.next || null;
                this.loadedCount = Number(pager?.dataset.loaded ?? 0);
                this.totalCount  = Number(pager?.dataset.total ?? 0);
            },

            /**
             * Watch the sentinel at the foot of the results.
             *
             * Re-attached after every swap: filtering replaces the pager's
             * innerHTML, which throws away the element the previous observer
             * was watching.
             */
            watchSentinel() {
                if (! this.infinite) return;

                this.observer?.disconnect();

                const sentinel = this.$refs.sentinel;

                if (! sentinel) return;

                this.observer = new IntersectionObserver(
                    entries => entries[0].isIntersecting && this.appendNext(),
                    // Start fetching before the customer reaches the bottom, so
                    // the next row is usually there by the time they arrive.
                    { rootMargin: '600px 0px' },
                );

                this.observer.observe(sentinel);
            },

            /**
             * Fetch the next page and add it below what is already shown.
             */
            async appendNext() {
                // `loading` is checked too: a filter change in flight is about
                // to replace this grid, so appending to it would be wasted.
                if (this.appending || this.loading || ! this.nextUrl) return;

                this.appending = true;

                const url = this.nextUrl;

                // Cleared up front so a second crossing of the sentinel cannot
                // request the same page twice.
                this.nextUrl = null;

                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });

                    if (! response.ok) throw new Error(response.status);

                    const doc = new DOMParser()
                        .parseFromString(await response.text(), 'text/html');

                    const incoming = doc.getElementById('shop-grid');
                    const grid     = document.getElementById('shop-grid');

                    if (! incoming || ! grid) throw new Error('missing grid');

                    const added = incoming.children.length;

                    grid.append(...incoming.children);

                    // The address bar follows the results, so a reload or a
                    // shared link lands where the customer actually is.
                    history.replaceState({}, '', url);

                    const pager = document.getElementById('shop-pager');
                    const next  = doc.getElementById('shop-pager');

                    if (pager && next) {
                        pager.dataset.next   = next.dataset.next || '';
                        pager.dataset.loaded = String(this.loadedCount + added);
                    }

                    this.loadedCount += added;
                    this.nextUrl = next?.dataset.next || null;
                } catch {
                    // Put the button back rather than stranding the customer at
                    // a dead end; the numbered pager is hidden behind it.
                    this.nextUrl = url;
                } finally {
                    this.appending = false;
                }
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

                    // The swap replaced the pager and the sentinel with fresh
                    // elements, so both the state and the observer are stale.
                    this.readPager();
                    this.$nextTick(() => this.watchSentinel());

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
