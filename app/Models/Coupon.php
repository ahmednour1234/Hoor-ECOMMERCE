<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\Money;
use App\Enums\CouponType;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A discount code.
 *
 * The model owns the rules that depend only on the coupon and the basket —
 * whether it is live, whether the basket is large enough, what it is worth.
 * Rules that need to know who is asking (has this customer used it before)
 * live in CouponService, which can see the redemption history.
 *
 * @property int $id
 * @property string $code
 * @property CouponType $type
 * @property int $value
 * @property int|null $max_discount
 * @property int|null $min_order
 * @property int|null $usage_limit
 * @property int|null $per_customer_limit
 * @property int $used_count
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 */
class Coupon extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'code', 'name_ar', 'name_en',
        'type', 'value', 'max_discount', 'min_order',
        'starts_at', 'expires_at',
        'usage_limit', 'per_customer_limit',
        'is_active',
    ];

    /** @var list<string> */
    protected array $translatable = ['name'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type'               => CouponType::class,
            'value'              => 'integer',
            // Money in piastres, like every other amount in the application.
            'max_discount'       => Money::class,
            'min_order'          => Money::class,
            'starts_at'          => 'datetime',
            'expires_at'         => 'datetime',
            'usage_limit'        => 'integer',
            'per_customer_limit' => 'integer',
            'used_count'         => 'integer',
            'is_active'          => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    // ---------------------------------------------------------------- Relations

    /** @return HasMany<CouponRedemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    // ------------------------------------------------------------------ Scopes

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Coupons that are within their date window right now.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query
            ->active()
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    /**
     * Look a code up, however the customer typed it.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCode(Builder $query, string $code): Builder
    {
        return $query->where('code', self::normaliseCode($code));
    }

    // ------------------------------------------------------------------- Rules

    /**
     * Codes are stored and compared upper-case, so casing never matters.
     */
    public static function normaliseCode(string $code): string
    {
        return mb_substr(strtoupper(trim($code)), 0, 64);
    }

    public function hasStarted(): bool
    {
        return $this->starts_at === null || $this->starts_at->isPast();
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Whether the coupon has been used as many times as it is allowed.
     */
    public function isExhausted(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    /**
     * Whether the coupon is usable at all, before considering any basket.
     */
    public function isLive(): bool
    {
        return $this->is_active
            && $this->hasStarted()
            && ! $this->hasExpired()
            && ! $this->isExhausted();
    }

    /**
     * Whether a basket of this size reaches the minimum spend.
     */
    public function coversMinimum(int $subtotal): bool
    {
        return $this->min_order === null || $subtotal >= $this->min_order;
    }

    /**
     * What this coupon takes off a subtotal, in piastres.
     *
     * Clamped to the subtotal by the enum, so a 500 EGP code on a 200 EGP
     * basket discounts 200 and not a piastre more — a coupon can never make a
     * total negative.
     */
    public function discountFor(int $subtotal): int
    {
        return $this->type->discountFor($subtotal, $this->value, $this->max_discount);
    }

    // ----------------------------------------------------------------- Display

    /**
     * The coupon's terms as one readable line, for the admin listing.
     */
    public function summary(): string
    {
        $parts = [$this->type->formatValue($this->value)];

        if ($this->type->supportsMaxDiscount() && $this->max_discount !== null) {
            $parts[] = __('coupons.summary.up_to', ['amount' => Money::format($this->max_discount)]);
        }

        if ($this->min_order !== null) {
            $parts[] = __('coupons.summary.over', ['amount' => Money::format($this->min_order)]);
        }

        return implode(' · ', $parts);
    }

    /**
     * How many uses are left, or null when unlimited.
     */
    public function remainingUses(): ?int
    {
        return $this->usage_limit === null
            ? null
            : max(0, $this->usage_limit - $this->used_count);
    }

    /**
     * Why this coupon is not currently usable, for the admin listing.
     */
    public function statusKey(): string
    {
        return match (true) {
            ! $this->is_active     => 'inactive',
            $this->hasExpired()    => 'expired',
            ! $this->hasStarted()  => 'scheduled',
            $this->isExhausted()   => 'exhausted',
            default                => 'live',
        };
    }
}
