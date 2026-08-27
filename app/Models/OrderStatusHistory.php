<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry in an order's audit trail.
 *
 * Append-only: entries are written, never edited or removed, so the record of
 * how an order progressed stays trustworthy.
 */
class OrderStatusHistory extends Model
{
    /** @use HasFactory<\Database\Factories\OrderStatusHistoryFactory> */
    use HasFactory;

    protected $table = 'order_status_history';

    /** @var list<string> */
    protected $fillable = ['order_id', 'from_status', 'to_status', 'user_id', 'note'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'from_status' => OrderStatus::class,
            'to_status'   => OrderStatus::class,
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Who made the change; null means the system did.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actorName(): string
    {
        return $this->user?->name ?? __('orders.history.system');
    }
}
