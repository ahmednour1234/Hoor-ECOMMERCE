<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An address in a customer's saved book.
 *
 * Live and editable, unlike OrderAddress, which is the frozen snapshot of
 * where a particular parcel went.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $label
 * @property string $full_name
 * @property string $phone
 * @property string|null $phone_alt
 * @property int $governorate_id
 * @property int|null $area_id
 * @property string $address
 * @property string|null $landmark
 * @property bool $is_default
 */
class CustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'label',
        'full_name', 'phone', 'phone_alt',
        'governorate_id', 'area_id',
        'address', 'landmark',
        'is_default',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Governorate, $this> */
    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    /** @return BelongsTo<Area, $this> */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeDefaultFirst(Builder $query): Builder
    {
        return $query->orderByDesc('is_default')->orderBy('label');
    }

    /**
     * The address as one readable line, for a summary row.
     */
    public function summary(): string
    {
        return collect([
            $this->address,
            $this->area?->name,
            $this->governorate?->name,
        ])->filter()->join('، ');
    }

    /**
     * Prefill values for the checkout form.
     *
     * @return array<string, mixed>
     */
    public function toCheckoutDefaults(): array
    {
        return [
            'full_name'      => $this->full_name,
            'phone'          => $this->phone,
            'phone_alt'      => $this->phone_alt,
            'governorate_id' => $this->governorate_id,
            'area_id'        => $this->area_id,
            'address'        => $this->address,
            'landmark'       => $this->landmark,
        ];
    }
}
