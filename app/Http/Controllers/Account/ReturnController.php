<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Enums\ReturnReason;
use App\Enums\ReturnType;
use App\Exceptions\ReturnNotAllowedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreReturnRequest;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\ReturnRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns and exchanges, from the customer's side.
 */
class ReturnController extends Controller
{
    public function __construct(private readonly ReturnRequestService $returns)
    {
    }

    public function index(Request $request): View
    {
        return view('store.account.returns.index', [
            'requests' => $this->returns->forCustomer($request->user()),
        ]);
    }

    /**
     * The form for sending something back from a particular order.
     */
    public function create(Request $request, Order $order): View
    {
        $this->assertOwned($request, $order);

        if (! $order->isReturnable()) {
            abort(404);
        }

        return view('store.account.returns.create', [
            'order'   => $order->load('items'),
            'lines'   => $this->returns->returnableLines($order),
            'types'   => ReturnType::options(),
            'reasons' => ReturnReason::options(),
        ]);
    }

    public function store(StoreReturnRequest $request, Order $order): RedirectResponse
    {
        $this->assertOwned($request, $order);

        try {
            $return = $this->returns->request(
                order: $order,
                quantities: $request->quantities(),
                type: $request->type(),
                reason: $request->reason(),
                customer: $request->user(),
                note: $request->note(),
                replacements: $request->replacements(),
            );
        } catch (ReturnNotAllowedException $e) {
            // The action's rules are the authority; its message names which one
            // was broken, so the customer knows what to change.
            return back()->withInput()->withErrors(['quantities' => $e->getMessage()]);
        }

        return redirect()
            ->route('store.account.returns.show', $return)
            ->with('status', __('returns.messages.submitted', ['number' => $return->number]));
    }

    public function show(Request $request, ReturnRequest $return): View
    {
        $this->authorize('view', $return);

        return view('store.account.returns.show', [
            'request' => $return->load(['order', 'items.orderItem', 'items.replacementVariant']),
        ]);
    }

    /**
     * Withdraw a request nobody has decided yet.
     */
    public function destroy(ReturnRequest $return): RedirectResponse
    {
        $this->authorize('withdraw', $return);

        try {
            $this->returns->withdraw($return);
        } catch (ReturnNotAllowedException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()
            ->route('store.account.returns.index')
            ->with('status', __('returns.messages.withdrawn'));
    }

    private function assertOwned(Request $request, Order $order): void
    {
        if ($order->user_id !== $request->user()->id) {
            throw new NotFoundHttpException();
        }
    }
}
