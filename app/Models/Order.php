<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\Money;
use App\Enums\OrderStatus;
use App\Enums\ReturnStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A placed order.
 *
 * Every money column is frozen at the moment of sale: the catalog may change
 * afterwards, but what the customer owes must not.
 *
 * @property OrderStatus $status
 * @property int $subtotal  Piastres.
 * @property int $total
 */
class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'number', 'user_id',
        'status', 'payment_method',
        'subtotal', 'discount', 'shipping', 'total',
        'coupon_id', 'coupon_code',
        'notes',
        'confirmed_at', 'delivered_at', 'cancelled_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status'         => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'subtotal'       => Money::class,
            'discount'       => Money::class,
            'shipping'       => Money::class,
            'total'          => Money::class,
            'confirmed_at'   => 'datetime',
            'delivered_at'   => 'datetime',
            'cancelled_at'   => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    // ---------------------------------------------------------------- Relations

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasOne<OrderAddress, $this> */
    public function address(): HasOne
    {
        return $this->hasOne(OrderAddress::class);
    }

    /**
     * Status history, oldest first.
     *
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    // ------------------------------------------------------------------ Scopes

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeStatus(Builder $query, OrderStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Orders still consuming stock.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeHoldingStock(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            OrderStatus::Cancelled->value,
            OrderStatus::Returned->value,
        ]);
    }

    /**
     * Look an order up the way a customer would, by number and phone.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForTracking(Builder $query, string $number, string $phone): Builder
    {
        return $query
            ->where('number', strtoupper(trim($number)))
            ->whereHas('address', fn (Builder $address) => $address
                ->where('phone', trim($phone))
                ->orWhere('phone_alt', trim($phone)));
    }

    // ----------------------------------------------------------------- Helpers

    public function totalQuantity(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function isCancellable(): bool
    {
        return $this->status->isCustomerCancellable();
    }

    /**
     * Whether the customer may still ask to send this back.
     *
     * Only a delivered order can be returned — there is nothing in her hands
     * before that — and only within the returns window, so a request cannot be
     * raised against a purchase from last year.
     */
    public function isReturnable(): bool
    {
        if ($this->status !== OrderStatus::Delivered || $this->delivered_at === null) {
            return false;
        }

        return $this->delivered_at->greaterThanOrEqualTo(
            now()->subDays((int) config('hoor.returns.window_days', 14)),
        );
    }

    /**
     * Quantities already covered by a return request, per order item.
     *
     * Rejected requests do not count: a refused return leaves the units with
     * the customer, and she may raise it again.
     *
     * @return array<int, int>  order item id => quantity
     */
    public function returnedQuantities(): array
    {
        return ReturnRequestItem::query()
            ->join('return_requests', 'return_requests.id', '=', 'return_request_items.return_request_id')
            ->where('return_requests.order_id', $this->id)
            ->whereNot('return_requests.status', ReturnStatus::Rejected)
            ->groupBy('return_request_items.order_item_id')
            ->selectRaw('return_request_items.order_item_id, sum(return_request_items.quantity) as aggregate')
            ->pluck('aggregate', 'order_item_id')
            ->map(fn ($quantity): int => (int) $quantity)
            ->all();
    }

    /** @return HasMany<ReturnRequest, $this> */
    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class)->latest('created_at');
    }

    public function customerName(): string
    {
        return $this->address?->full_name ?? $this->user?->name ?? '';
    }

    /**
     * Sanity check that the stored figures still add up.
     *
     * Used in tests and by the admin as a data-integrity signal; a mismatch
     * means something wrote a total without going through CheckoutService.
     */
    public function totalsReconcile(): bool
    {
        $itemsTotal = (int) $this->items->sum('line_total');

        return $itemsTotal === $this->subtotal
            && $this->subtotal - $this->discount + $this->shipping === $this->total;
    }

    public function formattedTotal(): string
    {
        return Money::format($this->total);
    }
}
