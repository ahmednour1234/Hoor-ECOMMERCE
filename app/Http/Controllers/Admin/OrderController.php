<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Order management.
 *
 * One listing filtered by status rather than eleven near-identical pages: the
 * tabs produce the same URLs a separate page would (?status=shipped), each with
 * its own count, from a single code path that cannot drift out of sync.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $filters = $request->only(['status', 'search', 'from', 'to']);

        return view('admin.orders.index', [
            'orders'   => $this->orders->paginate($filters),
            'counts'   => $this->service->countsByStatus(),
            'statuses' => OrderStatus::cases(),
            'filters'  => $filters,
            'active'   => OrderStatus::tryFrom((string) ($filters['status'] ?? '')),
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('admin.orders.show', [
            'order'       => $this->orders->loadForDetail($order),
            'transitions' => $this->service->availableTransitions($order),
        ]);
    }

    /**
     * Move the order to a new status.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        try {
            $this->service->transition(
                order: $order,
                to: $request->status(),
                actor: $request->user(),
                note: $request->note(),
            );
        } catch (InvalidOrderTransitionException $e) {
            // Most often: reinstating a cancelled order whose stock has since
            // been sold to someone else.
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', __('orders.messages.status_updated', [
            'number' => $order->number,
            'status' => $order->fresh()->status->label(),
        ]));
    }
}
