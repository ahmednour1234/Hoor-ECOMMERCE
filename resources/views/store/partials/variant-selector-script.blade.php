{{--
    Variant selection behaviour.

    Included by the product page rather than pushed from the selector component:
    a @push issued from inside an anonymous component leaves that component's
    output buffer open, which PHPUnit flags as risky and which leaks on real
    responses too.
--}}
<script>
    /**
     * Variant selection.
     *
     * `matrix` holds only real, active variants. Every lookup goes through it,
     * so the UI can never offer a combination the catalog does not have.
     */
    function variantSelector({ matrix, colorId, sizeId, hasColors, hasSizes }) {
        return {
            matrix,
            colorId,
            sizeId,
            quantity: 1,
            hasColors,
            hasSizes,

            /** The variant matching the current selection, or null. */
            get variant() {
                return this.matrix.find(v =>
                    (!this.hasColors || v.color_id === this.colorId) &&
                    (!this.hasSizes  || v.size_id  === this.sizeId)
                ) ?? null;
            },

            /** Labels come from the matrix, so no DOM lookup is needed. */
            get colorName() {
                return this.matrix.find(v => v.color_id === this.colorId)?.color_name ?? '';
            },

            get sizeName() {
                return this.matrix.find(v => v.size_id === this.sizeId)?.size_name ?? '';
            },

            get maxQuantity() {
                return Math.max(1, Math.min(this.variant?.stock ?? 1, 99));
            },

            get canAddToCart() {
                return !!this.variant && this.variant.in_stock;
            },

            get addToCartLabel() {
                if (!this.variant) return @js(__('store.product.select_first'));
                if (!this.variant.in_stock) return @js(__('store.product.sold_out'));
                return @js(__('store.product.add_to_cart'));
            },

            adding: false,

            /**
             * Add to the bag without leaving the page.
             *
             * Falls back to a normal submit if the shared store is unavailable,
             * so the page still works when the script has not loaded.
             */
            async submit(url) {
                if (! this.canAddToCart || this.adding) return;

                const store = Alpine.store('cart');

                if (! store) {
                    this.$el.submit();
                    return;
                }

                this.adding = true;

                await store.add(url, this.variant.id, this.quantity);

                this.adding = false;
            },

            /* ---------------------------------------------------- selection */

            selectColor(id) {
                this.colorId = id;

                // If the current size is not offered in this colour, drop it
                // rather than leaving an impossible pair selected.
                if (this.hasSizes && !this.sizeExists(this.sizeId)) {
                    this.sizeId = this.firstAvailableSize();
                }

                this.clampQuantity();
            },

            selectSize(id) {
                if (!this.sizeExists(id)) return;

                this.sizeId = id;
                this.clampQuantity();
            },

            /** Whether a size has a real variant in the chosen colour. */
            sizeExists(sizeId) {
                if (sizeId === null || sizeId === undefined) return false;

                return this.matrix.some(v =>
                    v.size_id === sizeId && (!this.hasColors || v.color_id === this.colorId)
                );
            },

            sizeInStock(sizeId) {
                return this.matrix.some(v =>
                    v.size_id === sizeId &&
                    (!this.hasColors || v.color_id === this.colorId) &&
                    v.in_stock
                );
            },

            colorHasAnyVariant(colorId) {
                return this.matrix.some(v => v.color_id === colorId);
            },

            firstAvailableSize() {
                const inColour = this.matrix.filter(v => !this.hasColors || v.color_id === this.colorId);

                return (inColour.find(v => v.in_stock) ?? inColour[0])?.size_id ?? null;
            },

            /* ------------------------------------------------------ styling */

            sizeClasses(sizeId) {
                if (!this.sizeExists(sizeId)) {
                    return 'border-hoor-cream-200 text-hoor-muted/40 line-through';
                }

                if (this.sizeId === sizeId) {
                    return 'border-hoor-navy-500 bg-hoor-navy-500 text-hoor-cream-50';
                }

                if (!this.sizeInStock(sizeId)) {
                    return 'border-hoor-cream-300 text-hoor-muted/60';
                }

                return 'border-hoor-cream-300 text-hoor-navy-600 hover:border-hoor-navy-400';
            },

            sizeTitle(sizeId, name) {
                if (!this.sizeExists(sizeId)) return @js(__('store.product.unavailable'));
                if (!this.sizeInStock(sizeId)) return @js(__('store.product.sold_out'));
                return name;
            },

            /* ----------------------------------------------------- quantity */

            increment() { this.quantity = Math.min(this.quantity + 1, this.maxQuantity); },
            decrement() { this.quantity = Math.max(this.quantity - 1, 1); },

            clampQuantity() {
                const value = Number(this.quantity) || 1;
                this.quantity = Math.min(Math.max(value, 1), this.maxQuantity);
            },

            /* ------------------------------------------------------ display */

            money(piastres) {
                const amount = (piastres / 100).toLocaleString(@js(app()->getLocale()), {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });

                return @js(app()->getLocale() === 'ar')
                    ? `${amount} ${@js(__('common.currency'))}`
                    : `${@js(__('common.currency'))} ${amount}`;
            },
        };
    }
</script>
