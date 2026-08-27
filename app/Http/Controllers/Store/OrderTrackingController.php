<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\TrackOrderRequest;
use App\Models\Order;
use App\Services\OrderTrackingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public order tracking.
 *
 * No account required: the order number and the phone it was placed with are
 * the credential. That pair is what a guest actually holds, and requiring
 * registration to see where a parcel is would punish the majority of customers
 * for the convenience of the minority who signed up.
 *
 * Orders are addressed by number throughout — never by id — so the URL cannot
 * be walked from one customer's order to the next.
 */
class OrderTrackingController extends Controller
{
    /**
     * Where a proven order number is remembered for the session.
     */
    private const SESSION_KEY = 'tracked_orders';

    public function __construct(private readonly OrderTrackingService $tracking)
    {
    }

    /**
     * The lookup form.
     */
    public function index(): View
    {
        return view('store.tracking.index');
    }

    /**
     * Look the order up.
     *
     * Throttled, because the pair is guessable at scale: order numbers are
     * structured, so without a limit an attacker holding one phone number
     * could try numbers until something matched.
     */
    public function lookup(TrackOrderRequest $request): RedirectResponse
    {
        $key = $this->tracking->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, OrderTrackingService::MAX_ATTEMPTS)) {
            return back()
                ->withInput()
                ->withErrors(['number' => __('tracking.errors.throttled', [
                    'seconds' => RateLimiter::availableIn($key),
                ])]);
        }

        $order = $this->tracking->find($request->number(), $request->phone());

        if ($order === null) {
            RateLimiter::hit($key, OrderTrackingService::DECAY_SECONDS);

            // One message for "no such order" and "wrong phone" alike:
            // distinguishing them would confirm which order numbers exist.
            return back()->withInput()->withErrors(['number' => __('tracking.errors.not_found')]);
        }

        RateLimiter::clear($key);

        // Remember that this visitor proved the pair, so the detail page can be
        // refreshed and linked from the confirmation email without re-entering it.
        $request->session()->push(self::SESSION_KEY, $order->number);

        return redirect()->route('store.tracking.show', $order);
    }

    /**
     * The order's progress.
     */
    public function show(Request $request, Order $order): View
    {
        if (! $this->mayView($request, $order)) {
            // Not a 403: confirming the order exists is itself a disclosure.
            throw new NotFoundHttpException();
        }

        return view('store.tracking.show', [
            'order' => $order->load(['items', 'address', 'statusHistory']),
        ]);
    }

    /**
     * Whether this visitor has earned sight of this order.
     */
    private function mayView(Request $request, Order $order): bool
    {
        $proven = in_array(
            $order->number,
            (array) $request->session()->get(self::SESSION_KEY, []),
            strict: true,
        );

        return $this->tracking->mayView(
            $order,
            $request->user(),
            $proven ? $order->number : null,
        );
    }
}
