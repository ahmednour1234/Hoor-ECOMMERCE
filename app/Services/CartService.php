<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\ProductVariant;
use App\Support\Cart\Cart;
use App\Support\Cart\CartLine;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * The cart.
 *
 * Guests are first-class: the cart lives in the session, so nothing requires an
 * account and there are no orphan rows to garbage-collect.
 *
 * The session holds one thing per line — a variant id and a quantity. It never
 * holds a price, a name or an image. Everything a customer sees is loaded from
 * the database on each read, so:
 *
 *   - a submitted price can never influence what is charged, because there is
 *     nowhere for one to be stored;
 *   - an admin price change is reflected on the customer's next page view
 *     rather than frozen at the moment they added the item;
 *   - stock is re-checked on every read, and a line that has outrun its stock
 *     is trimmed with an explanation rather than failing silently at checkout.
 *
 * Quantities are still validated at order time under a row lock: this class
 * reports what is available now, which is not a reservation.
 */
class CartService
{
    private const SESSION_KEY = 'hoor.cart';

    /**
     * Where the applied code lives.
     *
     * Only the code, never the discount it was worth. The server recomputes
     * the value on every read for the same reason it recomputes prices: a
     * stored amount is a number the customer could carry past a change she is
     * no longer entitled to.
     */
    private const COUPON_KEY = 'hoor.cart.coupon';

    /** Guard against a session growing without bound. */
    private const MAX_LINES = 50;

    private const MAX_QUANTITY_PER_LINE = 99;

    public function __construct(private readonly Session $session)
    {
    }

    // ------------------------------------------------------------- Commands

    /**
     * Add a quantity of a variant, merging with any existing line.
     *
     * The caller has already validated the variant through AddToCartRequest;
     * the quantity is still clamped here so a merge cannot push a line past the
     * stock on hand.
     *
     * The result reports what actually happened, not just the resulting total:
     * a customer who is already holding every remaining unit must be told the
     * click did nothing, rather than shown a success message for an addition
     * that never occurred.
     *
     * @return array{held: int, added: int, capped: bool, full: bool}
     */
    public function add(ProductVariant $variant, int $quantity = 1): array
    {
        $items = $this->rawItems();

        if (! isset($items[$variant->id]) && count($items) >= self::MAX_LINES) {
            return ['held' => 0, 'added' => 0, 'capped' => false, 'full' => true];
        }

        $before = $items[$variant->id] ?? 0;
        $requested = $before + max(1, $quantity);

        $items[$variant->id] = $this->clamp($requested, $variant);

        $this->persist($items);

        $held = $items[$variant->id];

        return [
            'held'   => $held,
            'added'  => $held - $before,
            // True when the customer asked for more than the stock allows.
            'capped' => $held < $requested,
            'full'   => false,
        ];
    }

    /**
     * Set a line to an exact quantity.
     *
     * A quantity of zero removes the line, which is what the "0" case in a
     * quantity input should mean.
     */
    public function update(ProductVariant $variant, int $quantity): int
    {
        if ($quantity <= 0) {
            $this->remove($variant->id);

            return 0;
        }

        $items = $this->rawItems();

        // Only touch lines that are actually in the cart, so a stale form
        // cannot silently re-add something the customer removed.
        if (! isset($items[$variant->id])) {
            return 0;
        }

        $items[$variant->id] = $this->clamp($quantity, $variant);

        $this->persist($items);

        return $items[$variant->id];
    }

    public function remove(int $variantId): void
    {
        $items = $this->rawItems();

        unset($items[$variantId]);

        $this->persist($items);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
        $this->session->forget(self::COUPON_KEY);
    }

    // ---------------------------------------------------------------- Coupon

    /**
     * Remember a code the customer entered.
     *
     * Storing it is not accepting it: whether the code is worth anything is
     * decided by CouponService every time the cart is read, so a code that
     * expires while she shops simply stops discounting.
     */
    public function applyCoupon(string $code): void
    {
        $this->session->put(self::COUPON_KEY, \App\Models\Coupon::normaliseCode($code));
    }

    public function forgetCoupon(): void
    {
        $this->session->forget(self::COUPON_KEY);
    }

    public function couponCode(): ?string
    {
        $code = $this->session->get(self::COUPON_KEY);

        return is_string($code) && $code !== '' ? $code : null;
    }

    // ---------------------------------------------------------------- Reads

    /**
     * The hydrated cart.
     *
     * Lines whose variant has vanished or been deactivated are dropped, and
     * lines that now exceed their stock are trimmed. Both produce a notice, so
     * the customer is told what changed rather than discovering it at checkout.
     */
    public function get(): Cart
    {
        $items = $this->rawItems();

        if ($items === []) {
            return Cart::empty();
        }

        $variants = $this->loadVariants(array_keys($items));

        $lines = collect();
        $notices = [];
        $corrected = [];

        foreach ($items as $variantId => $quantity) {
            $variant = $variants->get($variantId);

            // Gone, deactivated, or its product was unpublished: the line
            // cannot be bought at all, so it leaves the cart.
            if ($variant === null || ! $this->isPurchasable($variant)) {
                $notices[] = __('cart.notices.removed', [
                    'name' => $variant?->product?->name ?? __('cart.notices.an_item'),
                ]);

                continue;
            }

            $available = $variant->stock_quantity;

            if ($available <= 0) {
                $notices[] = __('cart.notices.sold_out', [
                    'name'    => $variant->product->name,
                    'variant' => $variant->label(),
                ]);

                continue;
            }

            // Stock fell below what the customer is holding: trim rather than
            // let them reach checkout with an impossible order.
            if ($available < $quantity) {
                $notices[] = __('cart.notices.reduced', [
                    'name'    => $variant->product->name,
                    'variant' => $variant->label(),
                    'count'   => $available,
                ]);

                $lines->push(new CartLine($variant, $available, $available));
                $corrected[$variantId] = $available;

                continue;
            }

            $lines->push(new CartLine($variant, $quantity));
            $corrected[$variantId] = $quantity;
        }

        // Write back only when hydration actually changed something, so a plain
        // read does not churn the session on every request.
        if ($corrected !== $items) {
            $this->persist($corrected);
        }

        return new Cart($lines, $notices);
    }

    /**
     * Total item count, for the header badge.
     *
     * Reads the raw session rather than hydrating, because the badge renders on
     * every page and does not need prices or stock.
     */
    public function count(): int
    {
        return (int) array_sum($this->rawItems());
    }

    public function isEmpty(): bool
    {
        return $this->rawItems() === [];
    }

    /**
     * Quantity currently held for a variant.
     */
    public function quantityFor(int $variantId): int
    {
        return (int) ($this->rawItems()[$variantId] ?? 0);
    }

    // -------------------------------------------------------------- Internals

    /**
     * Raw session contents: variant id => quantity, and nothing else.
     *
     * Defensive on read because session data can be stale across deployments or
     * corrupted by hand.
     *
     * @return array<int, int>
     */
    private function rawItems(): array
    {
        $items = $this->session->get(self::SESSION_KEY, []);

        if (! is_array($items)) {
            return [];
        }

        $clean = [];

        foreach ($items as $variantId => $quantity) {
            $id = filter_var($variantId, FILTER_VALIDATE_INT);
            $qty = filter_var($quantity, FILTER_VALIDATE_INT);

            if ($id === false || $qty === false || $id <= 0 || $qty <= 0) {
                continue;
            }

            $clean[$id] = min($qty, self::MAX_QUANTITY_PER_LINE);
        }

        return $clean;
    }

    /**
     * @param  array<int, int>  $items
     */
    private function persist(array $items): void
    {
        if ($items === []) {
            $this->session->forget(self::SESSION_KEY);

            return;
        }

        $this->session->put(self::SESSION_KEY, $items);
    }

    /**
     * Load every variant a cart references, with what the lines will render.
     *
     * One query for the whole cart rather than one per line.
     *
     * @param  list<int>  $ids
     * @return Collection<int, ProductVariant>
     */
    private function loadVariants(array $ids): Collection
    {
        return ProductVariant::query()
            ->with(['product.primaryImage', 'product.category', 'color', 'size'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    /**
     * Whether a variant may still be sold.
     */
    private function isPurchasable(ProductVariant $variant): bool
    {
        return $variant->is_active
            && $variant->product !== null
            && $variant->product->status === ProductStatus::Published;
    }

    /**
     * Hold a quantity within both the stock on hand and the per-line ceiling.
     */
    private function clamp(int $quantity, ProductVariant $variant): int
    {
        return max(1, min(
            $quantity,
            $variant->stock_quantity,
            self::MAX_QUANTITY_PER_LINE,
        ));
    }
}
