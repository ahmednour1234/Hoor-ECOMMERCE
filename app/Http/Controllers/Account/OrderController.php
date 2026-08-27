<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Services\ReturnRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A customer's own order history.
 *
 * Orders bind by number, and ownership is checked on every read: route model
 * binding will happily resolve any order whose number is known, so the guard
 * is here rather than in the URL shape.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly ReturnRequestService $returns,
    ) {
    }

    public function index(Request $request): View
    {
        return view('store.account.orders.index', [
            'orders' => $this->orders->forCustomer($request->user()->id),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->assertOwned($request, $order);

        return view('store.account.orders.show', [
            'order'       => $this->orders->loadForDetail($order),
            'canReturn'   => $order->isReturnable() && $this->returns->hasReturnableLines($order),
            'returns'     => $order->returnRequests()->with('items.orderItem')->get(),
        ]);
    }

    /**
     * Refuse anything that is not this customer's.
     *
     * A 404 rather than a 403: telling someone that an order exists but is not
     * theirs is itself a disclosure.
     */
    private function assertOwned(Request $request, Order $order): void
    {
        if ($order->user_id !== $request->user()->id) {
            throw new NotFoundHttpException();
        }
    }
}
