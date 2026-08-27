<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Enums\ReturnType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A request to send something back.
 *
 * Routed by its public number rather than its id, for the same reason orders
 * are: a sequential id in a URL invites walking the list.
 *
 * @property int $id
 * @property string $number
 * @property int $order_id
 * @property int|null $user_id
 * @property ReturnType $type
 * @property ReturnStatus $status
 * @property ReturnReason $reason
 * @property string|null $customer_note
 * @property string|null $admin_note
 * @property int|null $decided_by
 * @property \Illuminate\Support\Carbon|null $decided_at
 */
class ReturnRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'order_id', 'user_id',
        'type', 'status', 'reason',
        'customer_note', 'admin_note',
        'decided_by', 'decided_at',
        'received_by', 'received_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type'       => ReturnType::class,
            'status'     => ReturnStatus::class,
            'reason'     => ReturnReason::class,
            'decided_at'  => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    // ---------------------------------------------------------------- Relations

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @return BelongsTo<User, $this> */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /** @return HasMany<ReturnRequestItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ReturnRequestItem::class);
    }

    // ------------------------------------------------------------------ Scopes

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeStatus(Builder $query, ReturnStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Requests still waiting on a decision.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ReturnStatus::Requested);
    }

    /**
     * Requests that are still going somewhere.
     *
     * Useful for "what is outstanding" without listing the open statuses at
     * every call site.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ReturnStatus::Requested,
            ReturnStatus::Approved,
            ReturnStatus::Received,
        ]);
    }

    // ----------------------------------------------------------------- Helpers

    public function totalQuantity(): int
    {
        return (int) $this->items->sum('quantity');
    }

    /**
     * Whether the customer may still withdraw this.
     */
    public function isCancellable(): bool
    {
        return $this->status->isCancellable();
    }

    /**
     * Whether any line on this request names a replacement.
     */
    public function hasReplacements(): bool
    {
        return $this->items->contains(fn (ReturnRequestItem $item): bool => $item->isExchange());
    }

    /**
     * How many units have actually come back.
     */
    public function receivedQuantity(): int
    {
        return (int) $this->items->sum('received_quantity');
    }

    /**
     * Whether everything asked for has arrived.
     */
    public function isFullyReceived(): bool
    {
        return $this->items->every(fn (ReturnRequestItem $item): bool => $item->outstanding() === 0);
    }
}
