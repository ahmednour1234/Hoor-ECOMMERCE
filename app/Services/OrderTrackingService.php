<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;

/**
 * Finding an order without an account.
 *
 * The credential is the order number *plus* the phone it was placed with.
 * Neither alone is enough: an order number appears on a printed invoice, and a
 * phone number is not secret — but together they are something only the
 * customer and the shop hold.
 *
 * Nothing here accepts a database id. Orders route by their number precisely so
 * that a URL cannot be walked from /orders/41 to /orders/42.
 */
class OrderTrackingService
{
    /**
     * How many attempts one visitor may make before being made to wait.
     *
     * Without this, the pair is guessable: order numbers are structured, so an
     * attacker who knows a phone number could try numbers until one matched.
     */
    public const MAX_ATTEMPTS = 6;

    public const DECAY_SECONDS = 300;

    public function __construct(private readonly OrderRepository $orders)
    {
    }

    /**
     * Look up an order by the pair.
     *
     * Returns null rather than throwing, so the caller can give one message
     * for "no such order" and "wrong phone" alike — telling them apart would
     * confirm which order numbers exist.
     */
    public function find(string $number, string $phone): ?Order
    {
        if (trim($number) === '' || trim($phone) === '') {
            return null;
        }

        return $this->orders->findForTracking($number, $phone);
    }

    /**
     * The throttle key for a visitor.
     *
     * Keyed on IP and the submitted number, so hammering one order does not
     * lock out an unrelated customer behind the same office connection.
     */
    public function throttleKey(Request $request): string
    {
        return 'track:'.$request->ip().'|'.strtoupper(trim((string) $request->input('number')));
    }

    /**
     * Whether this visitor is entitled to see this order.
     *
     * Three ways in, in the order they are checked: staff, the customer whose
     * account it is, or anyone holding the number-and-phone pair.
     */
    public function mayView(Order $order, ?\App\Models\User $user, ?string $trackedNumber = null): bool
    {
        if ($user?->canAccessAdmin()) {
            return true;
        }

        if ($user !== null && $order->user_id === $user->id) {
            return true;
        }

        // Proved the pair earlier in this session.
        return $trackedNumber !== null && $trackedNumber === $order->number;
    }
}
