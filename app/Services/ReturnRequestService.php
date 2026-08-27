<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Returns\CreateReturnRequestAction;
use App\Actions\Returns\DecideReturnRequestAction;
use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Enums\ReturnType;
use App\Exceptions\ReturnNotAllowedException;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\ExchangeAvailability;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Returns and exchanges, as the rest of the application sees them.
 *
 * The two actions own the rules; this class names the operations and provides
 * the reads the account and admin screens need.
 */
class ReturnRequestService
{
    public function __construct(
        private readonly CreateReturnRequestAction $create,
        private readonly DecideReturnRequestAction $decide,
        private readonly ExchangeAvailability $exchanges,
    ) {
    }

    /**
     * @param  array<int, int>  $quantities
     * @param  array<int, int|null>  $replacements  order item id => replacement variant id
     *
     * @throws ReturnNotAllowedException
     */
    public function request(
        Order $order,
        array $quantities,
        ReturnType $type,
        ReturnReason $reason,
        ?User $customer = null,
        ?string $note = null,
        array $replacements = [],
    ): ReturnRequest {
        return $this->create->execute($order, $quantities, $type, $reason, $customer, $note, $replacements);
    }

    /**
     * @throws ReturnNotAllowedException
     */
    public function approve(ReturnRequest $request, User $actor, ?string $note = null): ReturnRequest
    {
        return $this->decide->approve($request, $actor, $note);
    }

    /**
     * @throws ReturnNotAllowedException
     */
    public function reject(ReturnRequest $request, User $actor, ?string $note = null): ReturnRequest
    {
        return $this->decide->reject($request, $actor, $note);
    }

    /**
     * Record the parcel arriving — where inventory actually moves.
     *
     * @param  array<int, int>  $receivedQuantities  return item id => quantity, empty means all of it
     *
     * @throws ReturnNotAllowedException
     */
    public function receive(
        ReturnRequest $request,
        User $actor,
        array $receivedQuantities = [],
        ?string $note = null,
    ): ReturnRequest {
        return $this->decide->receive($request, $actor, $receivedQuantities, $note);
    }

    /**
     * @throws ReturnNotAllowedException
     */
    public function complete(ReturnRequest $request, User $actor, ?string $note = null): ReturnRequest
    {
        return $this->decide->complete($request, $actor, $note);
    }

    /**
     * The statuses this request may move to next, for the admin's controls.
     *
     * Driven by the enum, so what is offered cannot drift from what is
     * permitted.
     *
     * @return array<string, string>
     */
    public function availableTransitions(ReturnRequest $request): array
    {
        return array_reduce(
            $request->status->nextStates(),
            static fn (array $carry, ReturnStatus $status): array => $carry + [$status->value => $status->label()],
            [],
        );
    }

    /**
     * The variants a customer may swap a line for.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductVariant>
     */
    public function replacementOptions(\App\Models\OrderItem $item, int $quantity = 1): \Illuminate\Database\Eloquent\Collection
    {
        return $this->exchanges->optionsFor($item, $quantity);
    }

    /**
     * Withdraw a request the customer raised by mistake.
     *
     * Deleted rather than kept as a status: an undecided request nobody acted
     * on is not a business record, and leaving it would keep its units counted
     * against the order's returnable quantity.
     *
     * @throws ReturnNotAllowedException
     */
    public function withdraw(ReturnRequest $request): void
    {
        if (! $request->isCancellable()) {
            throw ReturnNotAllowedException::alreadyDecided();
        }

        $request->delete();
    }

    /**
     * What a customer may still send back from an order, line by line.
     *
     * Returns every line with the quantity still available, so the form can
     * show a piece already returned as unavailable rather than silently
     * rejecting it on submit.
     *
     * @return list<array{item: \App\Models\OrderItem, remaining: int, replacements: iterable}>
     */
    public function returnableLines(Order $order): array
    {
        $claimed = $order->returnedQuantities();

        // Every sellable variant for every product on the order, in one query
        // rather than one per line.
        $swappable = $this->exchanges->optionsForProducts(
            $order->items->pluck('product_id')->filter()->unique()->all(),
        );

        return $order->items
            ->map(function ($item) use ($claimed, $swappable): array {
                $remaining = max(0, $item->quantity - (int) ($claimed[$item->id] ?? 0));

                return [
                    'item'      => $item,
                    'remaining' => $remaining,
                    // What she could swap this for, so the exchange form can
                    // offer real choices instead of a free-text box.
                    'replacements' => $remaining > 0
                        ? ($swappable[$item->product_id] ?? collect())
                        : collect(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Whether there is anything left to send back.
     */
    public function hasReturnableLines(Order $order): bool
    {
        return collect($this->returnableLines($order))->contains(fn (array $line): bool => $line['remaining'] > 0);
    }

    /**
     * @return LengthAwarePaginator<int, ReturnRequest>
     */
    public function forCustomer(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return $user->returnRequests()
            ->with(['order', 'items.orderItem', 'items.replacementVariant'])
            ->paginate($perPage);
    }

    /**
     * The admin queue, newest first, optionally narrowed to one status.
     *
     * @return LengthAwarePaginator<int, ReturnRequest>
     */
    public function queue(?ReturnStatus $status = null, int $perPage = 20): LengthAwarePaginator
    {
        return ReturnRequest::query()
            ->with(['order.address', 'user', 'items.orderItem', 'items.replacementVariant'])
            ->when($status, fn ($query) => $query->status($status))
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * How many requests sit in each status, for the queue tabs.
     *
     * @return array<string, int>
     */
    public function countsByStatus(): array
    {
        $counts = ReturnRequest::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return array_reduce(
            ReturnStatus::cases(),
            static fn (array $carry, ReturnStatus $status): array => $carry + [
                $status->value => (int) ($counts[$status->value] ?? 0),
            ],
            ['all' => (int) $counts->sum()],
        );
    }
}
