<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use App\Http\Resources\CartResource;
use App\Models\Product;
use App\Http\Requests\Cart\ApplyCouponRequest;
use App\Services\CartService;
use App\Services\CouponService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Cart pages and actions.
 *
 * Thin by design: every rule about what may be added, at what quantity and at
 * what price lives in AddToCartRequest and CartService. The controller only
 * decides where the customer goes next.
 */
class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CouponService $coupons,
    ) {
    }

    public function index(): View
    {
        $cart = $this->cart->get();

        return view('store.cart.index', [
            'cart' => $cart,

            // Resolved on every read, never stored: the same call checkout
            // makes, so the basket cannot quote a discount checkout refuses.
            'coupon' => $this->coupons->resolve($this->cart->couponCode(), $cart),
        ]);
    }

    /**
     * Apply a code to the basket.
     *
     * The code is remembered whether or not it is worth anything today — a
     * coupon that only applies once she adds another piece should stay in the
     * box telling her so, not vanish.
     */
    public function applyCoupon(ApplyCouponRequest $request): RedirectResponse|JsonResponse
    {
        $this->cart->applyCoupon($request->code());

        $cart = $this->cart->get();
        $coupon = $this->coupons->resolve($this->cart->couponCode(), $cart);

        $message = $coupon['valid']
            ? __('coupons.messages.applied', ['code' => $coupon['code']])
            : __('coupons.errors.'.($coupon['reason'] ?? 'not_found'));

        if ($request->expectsJson()) {
            return response()->json([
                'coupon'  => $coupon,
                'message' => $message,
                'summary' => $this->summaryFor($cart, $coupon),
            ], $coupon['valid'] ? 200 : 422);
        }

        return $coupon['valid']
            ? back()->with('status', $message)
            : back()->withErrors(['coupon_code' => $message]);
    }

    /**
     * Take the code off again.
     */
    public function removeCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $this->cart->forgetCoupon();

        if ($request->expectsJson()) {
            $cart = $this->cart->get();

            return response()->json([
                'coupon'  => null,
                'message' => __('coupons.messages.removed'),
                'summary' => $this->summaryFor($cart, null),
            ]);
        }

        return back()->with('status', __('coupons.messages.removed'));
    }

    /**
     * The figures the basket shows, formatted for display.
     *
     * @param  array<string, mixed>|null  $coupon
     * @return array<string, string|int>
     */
    private function summaryFor(\App\Support\Cart\Cart $cart, ?array $coupon): array
    {
        $subtotal = $cart->subtotal();
        $discount = (int) ($coupon['discount'] ?? 0);

        return [
            'subtotal'          => \App\Casts\Money::format($subtotal),
            'discount'          => \App\Casts\Money::format($discount),
            'discount_piastres' => $discount,
            // Shipping is added at checkout, once a governorate is chosen.
            'total'             => \App\Casts\Money::format(max(0, $subtotal - $discount)),
        ];
    }

    /**
     * Add a variant to the cart.
     *
     * AddToCartRequest has already confirmed the product is published, the
     * variant is active and belongs to it, and the quantity is available.
     */
    public function store(AddToCartRequest $request, Product $product): RedirectResponse|JsonResponse
    {
        $variant = $request->variant();

        $result = $this->cart->add($variant, $request->quantity());

        if ($result['full']) {
            return $this->respond($request, __('cart.errors.cart_full'), success: false);
        }

        // The customer already holds every remaining unit, so the click changed
        // nothing. Saying "added" here would be a lie they can see through.
        if ($result['added'] === 0) {
            return $this->respond($request, __('cart.errors.already_holding_all', [
                'count' => $result['held'],
            ]), success: false);
        }

        // Some of what was asked for was added, but not all of it.
        if ($result['capped']) {
            return $this->respond($request, __('cart.notices.added_capped', [
                'name'    => $product->name,
                'variant' => $variant->label(),
                'count'   => $result['held'],
            ]));
        }

        return $this->respond($request, __('cart.messages.added', [
            'name'    => $product->name,
            'variant' => $variant->label(),
        ]));
    }

    /**
     * Change the quantity on an existing line.
     */
    public function update(UpdateCartRequest $request): RedirectResponse|JsonResponse
    {
        $variant = $request->variant();
        $requested = $request->quantity();

        $held = $this->cart->update($variant, $requested);

        if ($held === 0) {
            return $this->respond($request, __('cart.messages.removed', [
                'name' => $variant->product->name,
            ]));
        }

        // The service clamps to stock, so the customer may end up with less
        // than they asked for — say so rather than silently changing it.
        $message = $held < $requested
            ? __('cart.notices.reduced', [
                'name'    => $variant->product->name,
                'variant' => $variant->label(),
                'count'   => $held,
            ])
            : __('cart.messages.updated');

        return $this->respond($request, $message);
    }

    public function destroy(Request $request, int $variant): RedirectResponse|JsonResponse
    {
        $this->cart->remove($variant);

        return $this->respond($request, __('cart.messages.removed_generic'));
    }

    public function clear(Request $request): RedirectResponse|JsonResponse
    {
        $this->cart->clear();

        return $this->respond($request, __('cart.messages.cleared'));
    }

    /**
     * Reply in the format the caller asked for.
     */
    private function respond(Request $request, string $message, bool $success = true): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            // Return the recalculated cart, so the page redraws from the
            // server's figures rather than doing arithmetic of its own.
            return response()->json([
                'message' => $message,
                'success' => $success,
                'cart'    => CartResource::toArray($this->cart->get()),
            ], $success ? 200 : 422);
        }

        $redirect = back();

        return $success
            ? $redirect->with('cart_status', $message)
            : $redirect->withErrors(['cart' => $message]);
    }
}
