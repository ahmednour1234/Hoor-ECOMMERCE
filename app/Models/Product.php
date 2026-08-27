<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\Money;
use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property string $name
 * @property int $base_price   Piastres.
 * @property int|null $sale_price
 * @property ProductStatus $status
 */
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    /** @var list<string> */
    protected array $translatable = [
        'name', 'description', 'short_description',
        'fabric', 'care', 'meta_title', 'meta_description',
    ];

    /** @var list<string> */
    protected $fillable = [
        'category_id',
        'name_ar', 'name_en', 'slug',
        'description_ar', 'description_en',
        'short_description_ar', 'short_description_en',
        'base_price', 'sale_price',
        'status', 'is_featured', 'is_new',
        'fabric_ar', 'fabric_en',
        'care_ar', 'care_en',
        'meta_title_ar', 'meta_title_en',
        'meta_description_ar', 'meta_description_en',
        'published_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'base_price'   => Money::class,
            'sale_price'   => Money::class,
            'status'       => ProductStatus::class,
            'is_featured'  => 'boolean',
            'is_new'       => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $product): void {
            if (blank($product->slug)) {
                $product->slug = Str::slug($product->name_en);
            }

            // Stamp the moment a product first goes live so "new in" listings
            // have a stable ordering key.
            if ($product->status === ProductStatus::Published && $product->published_at === null) {
                $product->published_at = now();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ---------------------------------------------------------------- Relations

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        // Variants resolve their price through the parent product, so the
        // inverse relation is primed here. Without it, reading prices off a
        // loaded variant collection would re-query this product once per row.
        return $this->hasMany(ProductVariant::class)
            ->chaperone('product');
    }

    /**
     * Lines on placed orders that reference this product.
     *
     * Used for sales reporting and the best-selling sort; the lines keep their
     * own snapshot, so this is a reporting link rather than a dependency.
     *
     * @return HasMany<\App\Models\OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(\App\Models\OrderItem::class);
    }

    /** @return HasMany<ProductImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * The single image used on cards and listings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<ProductImage, $this>
     */
    public function primaryImage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProductImage::class)
            ->where('is_primary', true);
    }

    // ------------------------------------------------------------------ Scopes

    /**
     * Products a storefront visitor is allowed to see.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Published);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeNewArrivals(Builder $query): Builder
    {
        return $query->where('is_new', true)->orderByDesc('published_at');
    }

    /**
     * Only products with at least one sellable variant.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereHas('variants', function (Builder $query): void {
            $query->where('is_active', true)->where('stock_quantity', '>', 0);
        });
    }

    // ------------------------------------------------------------------ Pricing

    /**
     * The price a shopper actually pays for this product, in piastres.
     *
     * A sale price only counts when it genuinely undercuts the base price,
     * which stops a mis-keyed "sale" from raising the price.
     */
    public function effectivePrice(): int
    {
        return $this->isOnSale() ? (int) $this->sale_price : (int) $this->base_price;
    }

    public function isOnSale(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->base_price;
    }

    /**
     * Whole-percentage discount, for the "-20%" badge.
     */
    public function discountPercentage(): int
    {
        if (! $this->isOnSale() || $this->base_price <= 0) {
            return 0;
        }

        return (int) round((($this->base_price - $this->sale_price) / $this->base_price) * 100);
    }

    /**
     * Cheapest and dearest sellable variant price, for "from EGP x" labels.
     *
     * @return array{min: int, max: int}
     */
    public function priceRange(): array
    {
        // Hand each variant its already-loaded parent so effectivePrice() does
        // not re-query this product once per variant.
        $prices = $this->variants
            ->where('is_active', true)
            ->map(fn (ProductVariant $variant): int => $variant->setRelation('product', $this)->effectivePrice());

        if ($prices->isEmpty()) {
            $price = $this->effectivePrice();

            return ['min' => $price, 'max' => $price];
        }

        return ['min' => (int) $prices->min(), 'max' => (int) $prices->max()];
    }

    // -------------------------------------------------------------- Inventory

    /**
     * Total sellable stock across active variants.
     *
     * Derived rather than stored: a product-level stock column would inevitably
     * drift from the variant rows that orders actually decrement.
     */
    public function totalStock(): int
    {
        return (int) $this->variants
            ->where('is_active', true)
            ->sum('stock_quantity');
    }

    public function stockStatus(): StockStatus
    {
        $variants = $this->variants->where('is_active', true);

        if ($variants->isEmpty()) {
            return StockStatus::OutOfStock;
        }

        // A product counts as low when every remaining variant is low or gone.
        $anyHealthy = $variants->contains(
            fn (ProductVariant $variant): bool => $variant->stockStatus() === StockStatus::InStock
        );

        if ($anyHealthy) {
            return StockStatus::InStock;
        }

        return $this->totalStock() > 0 ? StockStatus::LowStock : StockStatus::OutOfStock;
    }

    public function isInStock(): bool
    {
        return $this->totalStock() > 0;
    }
}
