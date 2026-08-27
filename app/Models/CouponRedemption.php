<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record that a code was actually used, and by whom.
 *
 * Written only when an order is placed — quoting a code at checkout is not
 * using it. Keyed by phone as well as account, because most HOOR customers
 * check out as guests and an account-only record would let a
 * one-per-customer code be reused by simply not signing in.
 *
 * @property int $id
 * @property int $coupon_id
 * @property int|null $order_id
 * @property int|null $user_id
 * @property string|null $phone
 * @property int $discount
 */
class CouponRedemption extends Model
{
    use HasFactory;

    protected $fillable = ['coupon_id', 'order_id', 'user_id', 'phone', 'discount'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['discount' => Money::class];
    }

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

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

    /**
     * Redemptions belonging to one customer, however she is identified.
     *
     * Either key matching counts as the same person: she may have ordered as a
     * guest once and signed in the next time, and a welcome code should not be
     * usable twice for that reason.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCustomer(Builder $query, ?string $phone, ?int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($phone, $userId): void {
            // Neither key given means nobody, not everybody — a query with no
            // constraint would count every redemption as this customer's.
            $q->whereRaw('1 = 0');

            if ($phone !== null && $phone !== '') {
                $q->orWhere('phone', $phone);
            }

            if ($userId !== null) {
                $q->orWhere('user_id', $userId);
            }
        });
    }
}
