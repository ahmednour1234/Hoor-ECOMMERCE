<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Order\CreateOrderAction;
use App\Exceptions\CartChangedException;
use App\Exceptions\InsufficientStockException;
use App\Models\Area;
use App\Models\Governorate;
use App\Mail\OrderPlaced;
use App\Models\Order;
use App\Support\Cart\Cart;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Places orders.
 *
 * Every figure that ends up on an order is computed here, from the database:
 *
 *   - line prices come from the variant rows, never from the request
 *   - the subtotal is the sum of those lines
 *   - shipping comes from ShippingService, never from the request
 *   - the discount comes from a validated coupon, never from the request
 *
 * Nothing a customer submits influences money. The form carries an address and
 * a destination id; that is all.
 *
 * Stock is checked twice on purpose. Once here, so an unfulfillable basket is
 * refused before an order exists — and again inside CreateOrderAction under a
 * row lock, which is the check that actually prevents overselling. This one is
 * a courtesy that produces a better message; that one is the guarantee.
 */
class CheckoutService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly ShippingService $shipping,
        private readonly CouponService $coupons,
        private readonly CreateOrderAction $createOrder,
    ) {
    }

    /**
     * Compute what a basket costs to deliver to a destination.
     *
     * Read-only: safe to call while the customer is still choosing, and it is
     * the same code path that produces the figures actually written to the
     * order, so the summary can never disagree with the total charged.
     *
     * A coupon's per-customer limit needs to know who is asking, so the phone
     * and account are passed through when they are known. Both are optional:
     * the cart quotes a discount before either exists.
     *
     * @return array{subtotal: int, discount: int, shipping: int, total: int, coupon_id: int|null, coupon_code: string|null, delivery_days: string}
     */
    public function summarise(
        Cart $cart,
        ?Governorate $governorate = null,
        ?Area $area = null,
        ?string $couponCode = null,
        ?string $phone = null,
        ?int $userId = null,
    ): array {
        $subtotal = $cart->subtotal();

        $shipping = $governorate !== null
            ? $this->shipping->feeFor($governorate, $area)
            : 0;

        // Coupons are validated server-side against the cart; an invalid or
        // expired code yields no discount rather than an error, so a stale code
        // never blocks a customer from ordering.
        $coupon = $this->coupons->resolve($couponCode, $cart, $phone, $userId);

        $discount = min($coupon['discount'], $subtotal);

        return [
            'subtotal'      => $subtotal,
            'discount'      => $discount,
            'shipping'      => $shipping,
            // Shipping is charged on top of the discounted goods, and the total
            // can never fall below the shipping alone.
            'total'         => max(0, $subtotal - $discount) + $shipping,
            'coupon_id'     => $coupon['id'],
            'coupon_code'   => $coupon['code'],
            'delivery_days' => $governorate?->deliveryWindow() ?? '',
        ];
    }

    /**
     * Place the order.
     *
     * The cart is cleared only after the transaction has committed — if
     * anything fails, the customer still has their basket and can retry.
     *
     * @param  array<string, mixed>  $details  Validated customer details.
     *
     * @throws InsufficientStockException
     */
    public function place(array $details, ?int $userId = null): Order
    {
        // Hydration reconciles the basket against current stock and reports
        // what it changed. If it changed anything, the customer is about to be
        // charged for something other than what they confirmed, so stop and let
        // them see the difference rather than silently ordering less.
        $cart = $this->cart->get();

        $this->assertCartIsOrderable($cart);
        $this->assertNothingChangedSinceReview($cart);

        // HTML form values arrive as strings; cast at the boundary so the
        // service reads the same whether it is called from a controller or
        // directly.
        $governorate = $this->resolveGovernorate((int) $details['governorate_id']);

        $area = $this->resolveArea(
            $governorate,
            filled($details['area_id'] ?? null) ? (int) $details['area_id'] : null,
        );

        $totals = $this->summarise(
            $cart,
            $governorate,
            $area,
            $details['coupon_code'] ?? null,
            $details['phone'] ?? null,
            $userId,
        );

        $order = $this->createOrder->execute(
            cart: $cart,
            details: $details,
            governorate: $governorate,
            area: $area,
            totals: $totals,
            userId: $userId,
        );

        // A code is spent when an order is placed, not when it is quoted.
        $this->coupons->redeem($order->load('address'));

        // Only now, with the order committed and stock consumed.
        $this->cart->clear();

        $this->sendConfirmation($order);

        return $order;
    }

    /**
     * Email the customer her confirmation.
     *
     * Cash on delivery leaves nothing in writing, so for a guest this is the
     * only record of the order and the only place its number appears — which
     * is half of what the tracking page asks for.
     *
     * Failure is swallowed deliberately. The order exists, stock is consumed
     * and the customer has already seen the success page; throwing here would
     * turn a mail-server hiccup into an apparent checkout failure for an order
     * that in fact went through. It is logged instead, so the shop can see it.
     */
    private function sendConfirmation(Order $order): void
    {
        $email = $order->address?->email;

        if (blank($email)) {
            return;
        }

        try {
            Mail::to($email)
                ->locale(app()->getLocale())
                ->send(new OrderPlaced($order));
        } catch (\Throwable $e) {
            Log::warning('Order confirmation email failed', [
                'order' => $order->number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Refuse a basket that changed while the customer was filling the form.
     *
     * A notice means hydration trimmed or dropped a line. Proceeding would
     * charge for a different order than the one on screen, so this stops and
     * sends the customer back to the cart, where the change is explained.
     *
     * @throws CartChangedException
     */
    private function assertNothingChangedSinceReview(Cart $cart): void
    {
        if ($cart->hasNotices()) {
            throw new CartChangedException($cart->notices);
        }
    }

    /**
     * Refuse a basket that cannot become an order.
     *
     * @throws InsufficientStockException
     */
    private function assertCartIsOrderable(Cart $cart): void
    {
        if ($cart->isEmpty()) {
            throw InsufficientStockException::forLines([]);
        }

        $unavailable = $cart->unavailableLines();

        if ($unavailable->isEmpty()) {
            return;
        }

        throw InsufficientStockException::forLines(
            $unavailable->map(fn ($line): array => [
                'name'      => $line->product()->name,
                'variant'   => $line->variant->label(),
                'requested' => $line->quantity,
                'available' => $line->variant->stock_quantity,
            ])->all(),
        );
    }

    /**
     * Resolve the destination, refusing anything not currently deliverable.
     */
    private function resolveGovernorate(int $id): Governorate
    {
        $governorate = Governorate::query()->active()->find($id);

        abort_if($governorate === null, 422, __('shipping.checkout.unavailable'));

        return $governorate;
    }

    /**
     * Resolve the area, and refuse one belonging to a different governorate.
     */
    private function resolveArea(Governorate $governorate, ?int $areaId): ?Area
    {
        if ($areaId === null) {
            return null;
        }

        $area = Area::query()
            ->active()
            ->where('governorate_id', $governorate->id)
            ->find($areaId);

        abort_if($area === null, 422, __('shipping.checkout.unavailable'));

        return $area;
    }
}
