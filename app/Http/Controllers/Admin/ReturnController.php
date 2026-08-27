<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ReturnStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\ReturnNotAllowedException;
use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Services\ReturnRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The returns queue.
 *
 * Like the orders screen, one filtered listing with status tabs rather than a
 * page per status.
 */
class ReturnController extends Controller
{
    public function __construct(private readonly ReturnRequestService $returns)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ReturnRequest::class);

        $status = ReturnStatus::tryFrom((string) $request->query('status'));

        return view('admin.returns.index', [
            'requests' => $this->returns->queue($status),
            'counts'   => $this->returns->countsByStatus(),
            'statuses' => ReturnStatus::cases(),
            'active'   => $status,
        ]);
    }

    public function show(ReturnRequest $return): View
    {
        $this->authorize('view', $return);

        return view('admin.returns.show', [
            'request' => $return->load([
                'order.address', 'order.items', 'user', 'decidedBy', 'receivedBy',
                'items.orderItem', 'items.replacementVariant.size', 'items.replacementVariant.color',
            ]),
            'transitions' => $this->returns->availableTransitions($return),
        ]);
    }

    /**
     * Move the request along: approve, reject, receive or complete.
     *
     * One endpoint rather than four: the decision is a single field, and
     * splitting it would mean four routes enforcing the same policy. Which
     * decisions are legal from here is the enum's business, re-checked in the
     * action.
     */
    public function decide(Request $request, ReturnRequest $return): RedirectResponse
    {
        $this->authorize('decide', $return);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject', 'receive', 'complete'])],
            'note'     => ['nullable', 'string', 'max:1000'],

            // Per line, for a receipt: what actually turned up in the box.
            // Absent means "all of it", which is the common case.
            'received'   => ['nullable', 'array'],
            'received.*' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $note = trim((string) ($validated['note'] ?? '')) ?: null;

        try {
            $return = match ($validated['decision']) {
                'approve'  => $this->returns->approve($return, $request->user(), $note),
                'reject'   => $this->returns->reject($return, $request->user(), $note),
                'receive'  => $this->returns->receive($return, $request->user(), $this->receivedQuantities($request), $note),
                'complete' => $this->returns->complete($return, $request->user(), $note),
            };
        } catch (ReturnNotAllowedException|InvalidOrderTransitionException $e) {
            return back()->withErrors(['decision' => $e->getMessage()]);
        }

        return back()->with('status', __('returns.messages.decided', [
            'number' => $return->number,
            'status' => $return->status->label(),
        ]));
    }

    /**
     * Received quantities, keyed by return item id and cast at the boundary.
     *
     * @return array<int, int>
     */
    private function receivedQuantities(Request $request): array
    {
        $received = [];

        foreach ((array) $request->input('received', []) as $itemId => $quantity) {
            if (! blank($quantity)) {
                $received[(int) $itemId] = (int) $quantity;
            }
        }

        return $received;
    }
}
