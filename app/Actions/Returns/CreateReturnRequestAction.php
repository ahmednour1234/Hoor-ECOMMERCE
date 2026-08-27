<?php

declare(strict_types=1);

namespace App\Actions\Returns;

use App\Enums\OrderStatus;
use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Enums\ReturnType;
use App\Exceptions\ReturnNotAllowedException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\ExchangeAvailability;
use Illuminate\Support\Facades\DB;

/**
 * Raises a return or exchange request against a delivered order.
 *
 * Every eligibility rule lives here rather than in the form request, because
 * the form is not the only way in — and because the interesting rules are
 * arithmetic against the order and the catalog that a request object cannot do:
 *
 *   - a customer may return part of a line, and across several requests, but
 *     never more units than she received;
 *   - an exchange must name a replacement that exists, is sellable, and belongs
 *     to the same product.
 *
 * No stock moves at this point. A request is a question; the answer comes when
 * staff decide, and the goods move only when the parcel arrives.
 */
class CreateReturnRequestAction
{
    public function __construct(
        private readonly GenerateReturnNumber $numbers,
        private readonly ExchangeAvailability $exchanges,
    ) {
    }

    /**
     * @param  array<int, int>  $quantities  order item id => quantity requested
     * @param  array<int, int|null>  $replacements  order item id => replacement variant id
     *
     * @throws ReturnNotAllowedException
     */
    public function execute(
        Order $order,
        array $quantities,
        ReturnType $type,
        ReturnReason $reason,
        ?User $customer = null,
        ?string $note = null,
        array $replacements = [],
    ): ReturnRequest {
        $this->assertOrderIsReturnable($order);
        $this->assertNotFlooded($order);

        $lines = $this->validateQuantities($order, $quantities);
        $swaps = $this->validateReplacements($order, $lines, $type, $replacements);

        return DB::transaction(function () use ($order, $lines, $swaps, $type, $reason, $customer, $note): ReturnRequest {
            $request = ReturnRequest::create([
                'number'        => $this->numbers->generate(),
                'order_id'      => $order->id,
                'user_id'       => $customer?->id ?? $order->user_id,
                'type'          => $type,
                'status'        => ReturnStatus::Requested,
                'reason'        => $reason,
                'customer_note' => $note,
            ]);

            foreach ($lines as $orderItemId => $quantity) {
                $request->items()->create(array_merge(
                    [
                        'order_item_id' => $orderItemId,
                        'quantity'      => $quantity,
                    ],
                    // Snapshotted now, so a variant later renamed or retired
                    // does not change what this exchange said.
                    isset($swaps[$orderItemId])
                        ? $this->exchanges->snapshotFor($swaps[$orderItemId])
                        : [],
                ));
            }

            return $request->load('items.orderItem');
        });
    }

    /**
     * @throws ReturnNotAllowedException
     */
    private function assertOrderIsReturnable(Order $order): void
    {
        if ($order->status !== OrderStatus::Delivered || $order->delivered_at === null) {
            throw ReturnNotAllowedException::notDelivered();
        }

        if (! $order->isReturnable()) {
            throw ReturnNotAllowedException::windowClosed(
                (int) config('hoor.returns.window_days', 14),
            );
        }
    }

    /**
     * Cap how many requests one order may carry.
     *
     * Without a limit, a single order can be used to flood the returns queue.
     *
     * @throws ReturnNotAllowedException
     */
    private function assertNotFlooded(Order $order): void
    {
        $max = (int) config('hoor.returns.max_open_per_order', 3);

        if ($order->returnRequests()->open()->count() >= $max) {
            throw ReturnNotAllowedException::tooManyOpen($max);
        }
    }

    /**
     * Check every requested line against what the order actually holds.
     *
     * @param  array<int, int>  $quantities
     * @return array<int, int>  the lines to write, zero and absent entries dropped
     *
     * @throws ReturnNotAllowedException
     */
    private function validateQuantities(Order $order, array $quantities): array
    {
        $items = $order->items()->get()->keyBy('id');
        $alreadyReturned = $order->returnedQuantities();

        $lines = [];

        foreach ($quantities as $orderItemId => $quantity) {
            $quantity = (int) $quantity;

            // Unticked lines arrive as zero; they are simply not part of this
            // request.
            if ($quantity <= 0) {
                continue;
            }

            $item = $items->get((int) $orderItemId);

            if ($item === null) {
                throw ReturnNotAllowedException::itemNotOnOrder();
            }

            // What is left after any earlier request already claimed some.
            $remaining = $item->quantity - (int) ($alreadyReturned[$item->id] ?? 0);

            if ($quantity > $remaining) {
                throw ReturnNotAllowedException::quantityExceeded($item->product_name);
            }

            $lines[$item->id] = $quantity;
        }

        if ($lines === []) {
            throw ReturnNotAllowedException::noItems();
        }

        return $lines;
    }

    /**
     * Resolve and check the replacement variant for each exchanged line.
     *
     * Availability is confirmed here *and* again at approval, because stock
     * moves in between — the last size 38 goes to whoever reaches it first, and
     * an answer given today is not evidence tomorrow.
     *
     * @param  array<int, int>  $lines
     * @param  array<int, int|null>  $replacements
     * @return array<int, ProductVariant>
     *
     * @throws ReturnNotAllowedException
     */
    private function validateReplacements(Order $order, array $lines, ReturnType $type, array $replacements): array
    {
        if ($type !== ReturnType::Exchange) {
            return [];
        }

        $items = $order->items()->get()->keyBy('id');
        $swaps = [];

        foreach ($lines as $orderItemId => $quantity) {
            /** @var OrderItem $item */
            $item = $items->get($orderItemId);

            $variantId = $replacements[$orderItemId] ?? null;

            // An exchange that does not say what she wants instead is not a
            // request anyone can act on.
            if (blank($variantId)) {
                throw ReturnNotAllowedException::replacementRequired($item->product_name);
            }

            $replacement = ProductVariant::query()->with(['size', 'color'])->find((int) $variantId);

            if ($replacement === null) {
                throw ReturnNotAllowedException::replacementNotOnProduct();
            }

            $rejection = $this->exchanges->reject($item, $replacement, $quantity);

            if ($rejection !== null) {
                throw ReturnNotAllowedException::forExchangeReason($rejection, $replacement->sku);
            }

            $swaps[$orderItemId] = $replacement;
        }

        return $swaps;
    }
}
