<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reading orders for the back office.
 *
 * Holds the eager-load shapes as well as the filters: an order list renders a
 * customer name and an item count per row, both of which live on relations, so
 * a listing that forgets them turns into an N+1.
 */
class OrderRepository
{
    /**
     * Relations the admin listing renders per row.
     *
     * @var list<string>
     */
    private const INDEX_RELATIONS = ['address'];

    /**
     * Everything the detail screen shows.
     *
     * @var list<string>
     */
    private const DETAIL_RELATIONS = [
        'address',
        'items',
        'user',
        'statusHistory.user',
    ];

    /**
     * Paginated, filtered listing.
     *
     * @param  array{status?: string|null, search?: string|null, from?: string|null, to?: string|null}  $filters
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->applyFilters(
            Order::query()
                ->with(self::INDEX_RELATIONS)
                ->withCount('items'),
            $filters,
        )
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  Builder<Order>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Order>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        // Status is whitelisted through the enum, so nothing arbitrary reaches
        // the query.
        $status = OrderStatus::tryFrom((string) ($filters['status'] ?? ''));

        $query->when($status !== null, fn (Builder $q) => $q->where('status', $status));

        $query->when(
            filled($filters['search'] ?? null),
            function (Builder $q) use ($filters): void {
                $term = trim((string) $filters['search']);

                // Staff search by whatever the customer quotes on the phone:
                // the order number, their name, or their phone.
                $q->where(function (Builder $q) use ($term): void {
                    $q->where('number', 'like', "%{$term}%")
                        ->orWhereHas('address', fn (Builder $address) => $address
                            ->where('full_name', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%")
                            ->orWhere('phone_alt', 'like', "%{$term}%"))
                        ->orWhereHas('items', fn (Builder $items) => $items->where('sku', 'like', "%{$term}%"));
                });
            },
        );

        $query->when(
            filled($filters['from'] ?? null),
            fn (Builder $q) => $q->whereDate('created_at', '>=', $filters['from']),
        );

        $query->when(
            filled($filters['to'] ?? null),
            fn (Builder $q) => $q->whereDate('created_at', '<=', $filters['to']),
        );

        return $query;
    }

    /**
     * Load an order with everything the detail screen needs.
     */
    public function loadForDetail(Order $order): Order
    {
        return $order->load(self::DETAIL_RELATIONS);
    }

    /**
     * A customer's own orders, newest first.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    public function forCustomer(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()
            ->with(self::INDEX_RELATIONS)
            ->withCount('items')
            ->where('user_id', $userId)
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Look an order up the way a customer would: number plus phone.
     */
    public function findForTracking(string $number, string $phone): ?Order
    {
        return Order::query()
            ->with(self::DETAIL_RELATIONS)
            ->forTracking($number, $phone)
            ->first();
    }
}
