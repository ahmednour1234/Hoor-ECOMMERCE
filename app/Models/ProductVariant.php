<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\Money;
use App\Enums\StockStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single sellable combination of a product, colour and size.
 *
 * This is the unit inventory is tracked against: orders reserve and release
 * stock here, never on the parent product.
 *
 * @property int $stock_quantity
 * @property int|null $price       Piastres; null inherits the product price.
 * @property int|null $sale_price
 */
class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'product_id', 'color_id', 'size_id',
        'sku',
        'stock_quantity', 'low_stock_threshold',
        'price', 'sale_price',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'stock_quantity'      => 'integer',
            'low_stock_threshold' => 'integer',
            'price'               => Money::class,
            'sale_price'          => Money::class,
            'is_active'           => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $variant): void {
            if (blank($variant->sku)) {
                $variant->sku = $variant->generateSku();
            }

            $variant->sku = strtoupper($variant->sku);
        });
    }

    // ---------------------------------------------------------------- Relations

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Color, $this> */
    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    /** @return BelongsTo<Size, $this> */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    /** @return HasMany<ProductImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
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
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Variants that have fallen to or below their reorder threshold.
     *
     * Compares two columns, so each variant is judged against its own
     * threshold rather than one global number.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSellable(Builder $query): Builder
    {
        return $query->active()->inStock();
    }

    // ------------------------------------------------------------------ Pricing

    /**
     * Resolve the price actually charged for this variant, in piastres.
     *
     * Resolution order, most specific first:
     *   1. variant sale price   (when it undercuts the effective base)
     *   2. variant price
     *   3. product sale price   (when it undercuts the product base)
     *   4. product base price
     *
     * Centralising this means no caller re-derives pricing and gets it subtly
     * wrong — important because Phase 3 recomputes every order total server-side.
     */
    public function effectivePrice(): int
    {
        $base = $this->price ?? $this->product->base_price;
        $sale = $this->price !== null
            ? $this->sale_price
            : ($this->sale_price ?? $this->product->sale_price);

        return ($sale !== null && $sale < $base) ? (int) $sale : (int) $base;
    }

    /**
     * The pre-discount price, used to render the struck-through amount.
     */
    public function basePrice(): int
    {
        return (int) ($this->price ?? $this->product->base_price);
    }

    public function isOnSale(): bool
    {
        return $this->effectivePrice() < $this->basePrice();
    }

    // -------------------------------------------------------------- Inventory

    public function stockStatus(): StockStatus
    {
        return StockStatus::forQuantity($this->stock_quantity, $this->low_stock_threshold);
    }

    public function isSellable(): bool
    {
        return $this->is_active && $this->stock_quantity > 0;
    }

    /**
     * Whether the requested quantity can be met from stock on hand.
     */
    public function canFulfil(int $quantity): bool
    {
        return $this->is_active && $quantity > 0 && $this->stock_quantity >= $quantity;
    }

    // ----------------------------------------------------------------- Helpers

    /**
     * Human-readable variant label, e.g. "Indigo / M".
     */
    public function label(): string
    {
        return collect([$this->color?->name, $this->size?->name])
            ->filter()
            ->implode(' / ');
    }

    /**
     * Guard for the partially-null case the unique index cannot cover.
     *
     * SQL treats NULLs as distinct, so (product, colour, NULL) can be inserted
     * twice despite the unique constraint. Callers creating variants for
     * single-axis products should check this first.
     */
    public static function hasCombination(int $productId, ?int $colorId, ?int $sizeId, ?int $ignoreId = null): bool
    {
        return static::query()
            ->where('product_id', $productId)
            ->where(fn (Builder $q) => $colorId === null ? $q->whereNull('color_id') : $q->where('color_id', $colorId))
            ->where(fn (Builder $q) => $sizeId === null ? $q->whereNull('size_id') : $q->where('size_id', $sizeId))
            ->when($ignoreId !== null, fn (Builder $q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * Build a readable, collision-resistant SKU: HOOR-<product>-<colour>-<size>.
     */
    private function generateSku(): string
    {
        $parts = array_filter([
            'HOOR',
            str_pad((string) $this->product_id, 4, '0', STR_PAD_LEFT),
            $this->color?->slug ? strtoupper(substr($this->color->slug, 0, 3)) : null,
            $this->size?->code,
        ]);

        return strtoupper(implode('-', $parts));
    }
}
