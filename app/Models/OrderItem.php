<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\Money;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A line on a placed order.
 *
 * Reads entirely from its own snapshot columns — never from the catalog. That
 * is the point: a product renamed, repriced or deleted tomorrow must not change
 * what this order says was bought.
 *
 * @property string $product_name
 * @property string|null $color_name
 * @property string|null $size_name
 */
class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    protected array $translatable = ['product_name', 'color_name', 'size_name'];

    /** @var list<string> */
    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id',
        'product_name_ar', 'product_name_en',
        'sku',
        'color_name_ar', 'color_name_en',
        'size_name_ar', 'size_name_en',
        'image_path',
        'unit_price', 'unit_price_before_discount',
        'quantity', 'line_total',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'unit_price'                 => Money::class,
            'unit_price_before_discount' => Money::class,
            'line_total'                 => Money::class,
            'quantity'                   => 'integer',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The catalog product, if it still exists.
     *
     * @return BelongsTo<Product, $this>
     */
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
     * Human-readable variant, e.g. "Indigo / M", from the snapshot.
     */
    public function variantLabel(): string
    {
        return collect([$this->color_name, $this->size_name])->filter()->implode(' / ');
    }

    public function wasDiscounted(): bool
    {
        return $this->unit_price < $this->unit_price_before_discount;
    }

    public function imageUrl(): ?string
    {
        return blank($this->image_path)
            ? null
            : Storage::disk(config('hoor.media.disk'))->url($this->image_path);
    }

    public function formattedLineTotal(): string
    {
        return Money::format($this->line_total);
    }
}
