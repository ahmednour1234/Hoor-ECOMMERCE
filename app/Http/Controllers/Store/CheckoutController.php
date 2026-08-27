<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Exceptions\CartChangedException;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ShippingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Checkout, cash on delivery.
 *
 * Thin: every rule about stock, pricing, shipping and coupons lives in
 * CheckoutService and the action beneath it. The controller decides where the
 * customer goes and what they are told.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutService $checkout,
        private readonly ShippingService $shipping,
    ) {
    }

    public function index(): View|RedirectResponse
    {
        $cart = $this->cart->get();

        // Nothing to check out, or something in the basket has become
        // unavailable — either way the cart page is where it gets resolved.
        if ($cart->isEmpty() || ! $cart->isCheckoutReady()) {
            return redirect()->route('store.cart.index');
        }

        // A code entered in the basket carries through, so she does not have
        // to remember and retype it here.
        $couponCode = $this->cart->couponCode();

        return view('store.checkout.index', [
            'cart'         => $cart,
            'governorates' => $this->shipping->deliverableGovernorates(),
            'summary'      => $this->checkout->summarise($cart, couponCode: $couponCode),
            'couponCode'   => $couponCode,
        ]);
    }

    /**
     * Recalculate the summary as the customer chooses a destination.
     *
     * Server-side so the figures shown are the same ones that will be charged;
     * the browser displays them rather than computing them.
     */
    public function quote(Request $request): JsonResponse
    {
        $cart = $this->cart->get();

        $governorate = filled($request->input('governorate_id'))
            ? \App\Models\Governorate::query()->active()->find($request->integer('governorate_id'))
            : null;

        $area = $governorate !== null && filled($request->input('area_id'))
            ? \App\Models\Area::query()
                ->active()
                ->where('governorate_id', $governorate->id)
                ->find($request->integer('area_id'))
            : null;

        /*
         * The phone is passed so a per-customer limit is enforced on the quote
         * as well as on the order — quoting a discount the order would then
         * refuse is worse than refusing it now.
         */
        $summary = $this->checkout->summarise(
            $cart,
            $governorate,
            $area,
            $request->input('coupon_code'),
            $request->input('phone'),
            $request->user()?->id,
        );

        return response()->json([
            'subtotal'  => \App\Casts\Money::format($summary['subtotal']),
            'discount'  => \App\Casts\Money::format($summary['discount']),
            'shipping'  => $governorate !== null ? \App\Casts\Money::format($summary['shipping']) : null,
            'total'     => \App\Casts\Money::format($summary['total']),
            'has_discount' => $summary['discount'] > 0,
            'delivery_days' => $summary['delivery_days'],
            'areas' => $governorate !== null
                ? $this->shipping->areasFor($governorate)->map(fn ($area): array => [
                    'id'   => $area->id,
                    'name' => $area->name,
                ])->all()
                : [],
        ]);
    }

    public function store(PlaceOrderRequest $request): RedirectResponse
    {
        try {
            $order = $this->checkout->place(
                details: $request->validated(),
                userId: $request->user()?->id,
            );
        } catch (CartChangedException | InsufficientStockException $e) {
            // Either the basket outran its stock, or it was reconciled and no
            // longer matches what the customer confirmed. Both send them back
            // to the cart, which shows exactly what changed.
            return redirect()
                ->route('store.cart.index')
                ->withErrors(['cart' => $e->messages() ?: [__('checkout.errors.cart_empty')]]);
        }

        // Remember the order for the success page, so it can be shown once
        // without exposing a guest's order behind a guessable URL.
        session()->flash('order_placed', $order->number);

        return redirect()->route('store.checkout.success', $order);
    }

    /**
     * Order confirmation.
     *
     * Reachable immediately after placing the order, or later by anyone who
     * knows both the number and the phone it was placed with — which is how a
     * guest returns to it without an account.
     */
    public function success(Request $request, Order $order): View
    {
        $justPlaced = session('order_placed') === $order->number;

        if (! $justPlaced && ! $this->mayView($request, $order)) {
            throw new NotFoundHttpException();
        }

        return view('store.checkout.success', [
            'order' => $order->load(['items', 'address']),
        ]);
    }

    /**
     * Whether this visitor is entitled to see the order.
     */
    private function mayView(Request $request, Order $order): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        return $order->user_id === $user->id || $user->canAccessAdmin();
    }
}
