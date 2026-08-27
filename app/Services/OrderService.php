<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Order\UpdateOrderStatusAction;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\User;

/**
 * Order operations the back office performs.
 *
 * Status changes all funnel through UpdateOrderStatusAction, so there is one
 * place that validates a transition, moves stock and appends history. This
 * class gives those operations names the business uses, and adds the reporting
 * the admin screens need.
 */
class OrderService
{
    public function __construct(private readonly UpdateOrderStatusAction $updateStatus)
    {
    }

    /**
     * Move an order to a new status.
     *
     * @throws InvalidOrderTransitionException
     */
    public function transition(Order $order, OrderStatus $to, ?User $actor = null, ?string $note = null): Order
    {
        return $this->updateStatus->execute($order, $to, $actor, $note);
    }

    /**
     * Confirm a pending order after the customer has been reached by phone.
     *
     * @throws InvalidOrderTransitionException
     */
    public function confirm(Order $order, ?User $actor = null, ?string $note = null): Order
    {
        return $this->transition($order, OrderStatus::Confirmed, $actor, $note);
    }

    /**
     * Cancel an order, releasing its stock.
     *
     * @throws InvalidOrderTransitionException
     */
    public function cancel(Order $order, ?User $actor = null, ?string $note = null): Order
    {
        return $this->transition($order, OrderStatus::Cancelled, $actor, $note);
    }

    /**
     * Mark an order delivered and its cash collected.
     *
     * @throws InvalidOrderTransitionException
     */
    public function markDelivered(Order $order, ?User $actor = null, ?string $note = null): Order
    {
        return $this->transition($order, OrderStatus::Delivered, $actor, $note);
    }

    /**
     * The statuses this order may move to next, for the admin's controls.
     *
     * Driven by the enum rather than hardcoded in a view, so the rules cannot
     * drift between what is offered and what is permitted.
     *
     * @return array<string, string>
     */
    public function availableTransitions(Order $order): array
    {
        return array_reduce(
            $order->status->nextStates(),
            static fn (array $carry, OrderStatus $status): array => $carry + [$status->value => $status->label()],
            [],
        );
    }

    /**
     * How many orders sit in each status, for the tab counts.
     *
     * One grouped query rather than eleven counts.
     *
     * @return array<string, int>
     */
    public function countsByStatus(): array
    {
        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        // Every status appears, so a tab reading zero is distinguishable from a
        // tab whose count failed to load.
        return array_reduce(
            OrderStatus::cases(),
            static fn (array $carry, OrderStatus $status): array => $carry + [
                $status->value => (int) ($counts[$status->value] ?? 0),
            ],
            ['all' => (int) $counts->sum()],
        );
    }

    /**
     * Headline figures for the dashboard.
     *
     * Revenue counts only orders that were actually delivered and paid for —
     * cash on delivery means a placed order is not yet money.
     *
     * @return array{today: int, pending: int, delivered: int, revenue: int}
     */
    public function statistics(): array
    {
        return [
            'today'     => Order::query()->whereDate('created_at', today())->count(),
            'pending'   => Order::query()->status(OrderStatus::Pending)->count(),
            'delivered' => Order::query()->status(OrderStatus::Delivered)->count(),
            'revenue'   => (int) Order::query()
                ->status(OrderStatus::Delivered)
                ->whereMonth('delivered_at', now()->month)
                ->whereYear('delivered_at', now()->year)
                ->sum('total'),
        ];
    }
}
