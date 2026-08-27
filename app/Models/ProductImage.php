<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $alt
 */
class ProductImage extends Model
{
    /** @use HasFactory<\Database\Factories\ProductImageFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    protected array $translatable = ['alt'];

    /** @var list<string> */
    protected $fillable = [
        'product_id', 'product_variant_id',
        'path',
        'alt_ar', 'alt_en',
        'sort_order', 'is_primary',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Keep the primary flag exclusive per product.
     *
     * Enforced here rather than left to callers so the invariant holds no
     * matter which code path saves the image.
     */
    protected static function booted(): void
    {
        static::saved(function (self $image): void {
            if (! $image->is_primary) {
                return;
            }

            static::query()
                ->where('product_id', $image->product_id)
                ->whereKeyNot($image->getKey())
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        });
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_primary')->orderBy('sort_order');
    }

    public function url(): string
    {
        return Storage::disk(config('hoor.media.disk'))->url($this->path);
    }
}
