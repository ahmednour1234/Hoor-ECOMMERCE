<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\Money;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Where an order is being delivered.
 *
 * Destination names are snapshotted for the same reason as the order items: a
 * governorate renamed or an area deleted must not change what a past delivery
 * address said.
 *
 * @property string $governorate_name
 * @property string|null $area_name
 */
class OrderAddress extends Model
{
    /** @use HasFactory<\Database\Factories\OrderAddressFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    protected array $translatable = ['governorate_name', 'area_name'];

    /** @var list<string> */
    protected $fillable = [
        'order_id',
        'full_name', 'phone', 'phone_alt', 'email',
        'governorate_id', 'governorate_name_ar', 'governorate_name_en',
        'area_id', 'area_name_ar', 'area_name_en',
        'address', 'landmark',
        'shipping_fee',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['shipping_fee' => Money::class];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The address as a courier would read it, in the active language.
     */
    public function formatted(): string
    {
        return collect([
            $this->address,
            $this->landmark,
            $this->area_name,
            $this->governorate_name,
        ])->filter()->implode('، ');
    }

    /**
     * Both contact numbers, for the courier.
     *
     * @return list<string>
     */
    public function phones(): array
    {
        return array_values(array_filter([$this->phone, $this->phone_alt]));
    }
}
