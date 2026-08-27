<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\Money;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An Egyptian governorate, and the delivery fee for reaching it.
 *
 * @property string $name
 * @property int $shipping_fee  Piastres.
 */
class Governorate extends Model
{
    /** @use HasFactory<\Database\Factories\GovernorateFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    protected array $translatable = ['name'];

    /** @var list<string> */
    protected $fillable = [
        'name_ar', 'name_en', 'code',
        'shipping_fee',
        'delivery_days_min', 'delivery_days_max',
        'is_active', 'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'shipping_fee'      => Money::class,
            'delivery_days_min' => 'integer',
            'delivery_days_max' => 'integer',
            'is_active'         => 'boolean',
            'sort_order'        => 'integer',
        ];
    }

    /** @return HasMany<Area, $this> */
    public function areas(): HasMany
    {
        return $this->hasMany(Area::class)->orderBy('sort_order');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name_en');
    }

    /**
     * Whether orders can currently be delivered here.
     *
     * A governorate with no active areas is still deliverable — areas are
     * optional detail, not a requirement.
     */
    public function isDeliverable(): bool
    {
        return $this->is_active;
    }

    /**
     * Human-readable delivery window, e.g. "2–5".
     */
    public function deliveryWindow(): string
    {
        return $this->delivery_days_min === $this->delivery_days_max
            ? (string) $this->delivery_days_min
            : $this->delivery_days_min.'–'.$this->delivery_days_max;
    }
}
