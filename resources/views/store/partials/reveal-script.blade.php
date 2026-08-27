{{--
    Scroll reveal, site-wide.

    One observer for the whole page rather than a component per section: the
    behaviour is identical everywhere, and a single script means a new section
    gets the animation by adding a class rather than by wiring anything.

    Anything carrying `.reveal` rises into place as it enters the viewport.
    Add `.reveal-group` to a container and its direct children stagger
    themselves, so a grid of cards does not need a delay written per card.

    Included once by the store layout, for the same reason the wishlist script
    is: an @once inside a component that renders many times leaves an output
    buffer open.
--}}
{{-- The hidden state comes from CSS, so a visitor with scripting disabled
     would see an empty page. This restores it before the script matters. --}}
<noscript>
    <style>.reveal { opacity: 1 !important; transform: none !important; }</style>
</noscript>

<script>
    (() => {
        'use strict';

        const REVEALED = 'is-revealed';

        /**
         * Show everything immediately when the visitor has asked for less
         * motion, or when the browser cannot observe intersections.
         *
         * The hidden state is applied by CSS, so failing to reveal would leave
         * the page blank — this is the branch that must never be wrong.
         */
        const showAll = () => {
            document.querySelectorAll('.reveal').forEach((el) => el.classList.add(REVEALED));
        };

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

        if (reducedMotion.matches || ! ('IntersectionObserver' in window)) {
            showAll();
            return;
        }

        /**
         * Stagger a group's children by their position.
         *
         * Set as a custom property rather than inline transition-delay, so the
         * stylesheet keeps control of the easing and duration.
         */
        const stagger = (container) => {
            Array.from(container.children).forEach((child, index) => {
                if (child.classList.contains('reveal')) {
                    child.style.setProperty('--reveal-delay', `${Math.min(index, 8) * 70}ms`);
                }
            });
        };

        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach((entry) => {
                if (! entry.isIntersecting) return;

                entry.target.classList.add(REVEALED);

                // Once shown, stay shown: re-animating on every pass is noise.
                obs.unobserve(entry.target);
            });
        }, {
            // Fire a little before the element is fully in view, so the motion
            // finishes about when the reader arrives at it.
            rootMargin: '0px 0px -8% 0px',
            threshold: 0.1,
        });

        const start = () => {
            document.querySelectorAll('.reveal-group').forEach(stagger);
            document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));
        };

        document.readyState === 'loading'
            ? document.addEventListener('DOMContentLoaded', start, { once: true })
            : start();

        // A visitor who turns reduced motion on mid-visit should not be left
        // with half a page still waiting to appear.
        reducedMotion.addEventListener?.('change', (event) => {
            if (event.matches) {
                observer.disconnect();
                showAll();
            }
        });
    })();
</script>
