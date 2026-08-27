<?php

declare(strict_types=1);

namespace App\Actions\Returns;

use App\Actions\Order\UpdateOrderStatusAction;
use App\Enums\OrderStatus;
use App\Enums\ReturnStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\ReturnNotAllowedException;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\User;
use App\Services\ExchangeAvailability;
use Illuminate\Support\Facades\DB;

/**
 * Moves a return request through its lifecycle.
 *
 * The single rule that shapes this class: **stock moves on receipt, not on
 * approval.** Approving is a promise to accept a parcel; the parcel arriving is
 * a fact about inventory. Restocking on approval would inflate the shelf with
 * garments that never came back — and in a fashion store, where a size sells
 * out in days, that oversells the replacement to someone else.
 *
 * So `receive()` is where the two inventory movements happen, together in one
 * transaction:
 *
 *   - the returned units go back on the shelf, via UpdateOrderStatusAction
 *     when the whole order came back, so restocking stays in one place;
 *   - an exchange's replacement units come off it, re-checked for availability
 *     because stock has moved since the request was raised.
 */
class DecideReturnRequestAction
{
    public function __construct(
        private readonly UpdateOrderStatusAction $updateOrderStatus,
        private readonly ExchangeAvailability $exchanges,
    ) {
    }

    /**
     * Accept the request in principle.
     *
     * Availability is re-checked here even though nothing moves yet: telling a
     * customer her exchange is approved when the size has since sold out is a
     * promise the shop cannot keep.
     *
     * @throws ReturnNotAllowedException
     */
    public function approve(ReturnRequest $request, User $actor, ?string $note = null): ReturnRequest
    {
        $this->assertCanMoveTo($request, ReturnStatus::Approved);
        $this->assertReplacementsStillAvailable($request);

        $request->update([
            'status'     => ReturnStatus::Approved,
            'admin_note' => $note,
            'decided_by' => $actor->id,
            'decided_at' => now(),
        ]);

        return $request->refresh();
    }

    /**
     * Refuse it.
     *
     * Reachable from `requested` and from `approved` alike: what arrives may
     * not be what was described, and staff must be able to say so.
     *
     * @throws ReturnNotAllowedException
     */
    public function reject(ReturnRequest $request, User $actor, ?string $note = null): ReturnRequest
    {
        $this->assertCanMoveTo($request, ReturnStatus::Rejected);

        $request->update([
            'status'     => ReturnStatus::Rejected,
            'admin_note' => $note,
            'decided_by' => $actor->id,
            'decided_at' => now(),
        ]);

        return $request->refresh();
    }

    /**
     * The parcel is back with us.
     *
     * Where inventory actually moves. Quantities are per line because what
     * arrives is not always what was promised — two pieces requested, one in
     * the box.
     *
     * @param  array<int, int>  $receivedQuantities  return item id => quantity, empty means "all of it"
     *
     * @throws ReturnNotAllowedException|InvalidOrderTransitionException
     */
    public function receive(
        ReturnRequest $request,
        User $actor,
        array $receivedQuantities = [],
        ?string $note = null,
    ): ReturnRequest {
        $this->assertCanMoveTo($request, ReturnStatus::Received);

        $request->loadMissing('items.orderItem');

        $counted = $this->resolveReceivedQuantities($request, $receivedQuantities);

        return DB::transaction(function () use ($request, $actor, $counted, $note): ReturnRequest {
            foreach ($counted as $itemId => $quantity) {
                $request->items->firstWhere('id', $itemId)?->update(['received_quantity' => $quantity]);
            }

            // Replacements go out before the status is written, so a size that
            // sold out in the meantime leaves the request where it was rather
            // than marked received against stock that cannot be sent.
            $this->dispatchReplacements($request, $counted);

            $request->update([
                'status'      => ReturnStatus::Received,
                'admin_note'  => $note ?? $request->admin_note,
                'received_by' => $actor->id,
                'received_at' => now(),
            ]);

            $this->restock($request, $actor, $note, $counted);

            return $request->refresh();
        });
    }

    /**
     * Everything is settled — refunded, or the replacement is on its way.
     *
     * @throws ReturnNotAllowedException
     */
    public function complete(ReturnRequest $request, User $actor, ?string $note = null): ReturnRequest
    {
        $this->assertCanMoveTo($request, ReturnStatus::Completed);

        $request->update([
            'status'     => ReturnStatus::Completed,
            'admin_note' => $note ?? $request->admin_note,
            'decided_by' => $actor->id,
        ]);

        return $request->refresh();
    }

    // ------------------------------------------------------------------ Rules

    /**
     * @throws ReturnNotAllowedException
     */
    private function assertCanMoveTo(ReturnRequest $request, ReturnStatus $target): void
    {
        if (! $request->status->canTransitionTo($target)) {
            throw ReturnNotAllowedException::invalidTransition($request->status, $target);
        }
    }

    /**
     * Confirm every replacement can still be sent.
     *
     * @throws ReturnNotAllowedException
     */
    private function assertReplacementsStillAvailable(ReturnRequest $request): void
    {
        $request->loadMissing('items.orderItem', 'items.replacementVariant');

        foreach ($request->items as $line) {
            $replacement = $line->replacementVariant;

            if ($replacement === null || $line->orderItem === null) {
                continue;
            }

            $rejection = $this->exchanges->reject($line->orderItem, $replacement, $line->quantity);

            if ($rejection !== null) {
                throw ReturnNotAllowedException::forExchangeReason($rejection, $replacement->sku);
            }
        }
    }

    /**
     * Work out how many of each line came back.
     *
     * An empty submission means "everything we asked for", which is the common
     * case and should not require the staff member to retype it.
     *
     * @param  array<int, int>  $submitted
     * @return array<int, int>  return item id => quantity
     *
     * @throws ReturnNotAllowedException
     */
    private function resolveReceivedQuantities(ReturnRequest $request, array $submitted): array
    {
        $counted = [];

        foreach ($request->items as $line) {
            $quantity = array_key_exists($line->id, $submitted)
                ? (int) $submitted[$line->id]
                : $line->quantity;

            if ($quantity < 0) {
                $quantity = 0;
            }

            // More in the box than was ever requested is a counting error, not
            // a windfall — and silently accepting it would inflate stock.
            if ($quantity > $line->quantity) {
                throw ReturnNotAllowedException::receivedTooMany(
                    $line->orderItem?->product_name ?? $line->replacement_sku ?? '',
                );
            }

            $counted[$line->id] = $quantity;
        }

        return $counted;
    }

    // -------------------------------------------------------------- Inventory

    /**
     * Take the replacement units off the shelf.
     *
     * Locked in ascending id order, as everywhere else that touches stock, so
     * concurrent operations cannot deadlock against each other.
     *
     * @param  array<int, int>  $counted
     *
     * @throws ReturnNotAllowedException
     */
    private function dispatchReplacements(ReturnRequest $request, array $counted): void
    {
        $wanted = [];

        foreach ($request->items as $line) {
            $quantity = (int) ($counted[$line->id] ?? 0);

            // Only what actually came back earns a replacement.
            if ($line->replacement_variant_id === null || $quantity < 1) {
                continue;
            }

            $wanted[$line->replacement_variant_id] = ($wanted[$line->replacement_variant_id] ?? 0) + $quantity;
        }

        if ($wanted === []) {
            return;
        }

        ksort($wanted);

        ProductVariant::query()
            ->whereIn('id', array_keys($wanted))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($wanted as $variantId => $quantity) {
            $affected = ProductVariant::query()
                ->whereKey($variantId)
                ->where('stock_quantity', '>=', $quantity)
                ->decrement('stock_quantity', $quantity);

            if ($affected === 0) {
                throw ReturnNotAllowedException::replacementOutOfStock(
                    ProductVariant::query()->find($variantId)?->sku ?? (string) $variantId,
                );
            }
        }
    }

    /**
     * Put the returned units back.
     *
     * Delegated to UpdateOrderStatusAction when the whole order came back, so
     * that restocking lives in exactly one place. A partial return leaves the
     * order Delivered — most of it was, in fact, delivered and kept — and its
     * units are returned directly.
     *
     * An exchange restocks nothing: the piece coming back is being swapped for
     * one going out, and the shelf nets to where it was.
     *
     * @param  array<int, int>  $counted  return item id => quantity received
     *
     * @throws InvalidOrderTransitionException
     */
    private function restock(ReturnRequest $request, User $actor, ?string $note, array $counted): void
    {
        if (! $request->type->restocks()) {
            return;
        }

        $order = $request->order;

        if ($this->coversWholeOrder($request) && $order->status->canTransitionTo(OrderStatus::Returned)) {
            $this->updateOrderStatus->execute(
                $order,
                OrderStatus::Returned,
                $actor,
                $note ?? __('returns.history.received', ['number' => $request->number]),
            );

            return;
        }

        $this->restockPartial($request, $counted);
    }

    /**
     * Return the units from a partial return, which leaves the order alone.
     *
     * @param  array<int, int>  $counted  return item id => quantity received
     */
    private function restockPartial(ReturnRequest $request, array $counted): void
    {
        $quantities = [];

        foreach ($request->items as $line) {
            $variantId = $line->orderItem?->product_variant_id;
            $quantity = (int) ($counted[$line->id] ?? 0);

            if ($variantId === null || $quantity < 1) {
                continue;
            }

            $quantities[$variantId] = ($quantities[$variantId] ?? 0) + $quantity;
        }

        if ($quantities === []) {
            return;
        }

        ksort($quantities);

        ProductVariant::query()
            ->whereIn('id', array_keys($quantities))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($quantities as $variantId => $quantity) {
            ProductVariant::query()->whereKey($variantId)->increment('stock_quantity', $quantity);
        }
    }

    /**
     * Whether every unit on the order has now come back.
     *
     * Counts what was actually received, not what was requested: a request for
     * everything that arrives half-empty has not returned the order.
     */
    private function coversWholeOrder(ReturnRequest $request): bool
    {
        $order = $request->order;

        $ordered = (int) $order->items()->sum('quantity');

        $received = ReturnRequestItem::query()
            ->join('return_requests', 'return_requests.id', '=', 'return_request_items.return_request_id')
            ->where('return_requests.order_id', $order->id)
            ->whereIn('return_requests.status', [ReturnStatus::Received, ReturnStatus::Completed])
            ->sum('return_request_items.received_quantity');

        return $ordered > 0 && (int) $received >= $ordered;
    }
}
