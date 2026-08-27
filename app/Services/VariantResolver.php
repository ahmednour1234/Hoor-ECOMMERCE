<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Resolves and validates a customer's size/colour choice against real variant
 * rows.
 *
 * The storefront disables combinations that do not exist, but that is a
 * convenience for the shopper — never a guarantee. Anything reaching the server
 * is re-checked here: that the variant exists, belongs to the product being
 * bought, is active, and holds the requested quantity.
 */
class VariantResolver
{
    /**
     * Find the variant matching a colour and size for this product.
     *
     * Returns null when the combination has no row, which is the case the
     * caller must treat as "not purchasable" rather than as an error.
     */
    public function resolve(Product $product, ?int $colorId, ?int $sizeId): ?ProductVariant
    {
        return $product->variants()
            ->where('is_active', true)
            ->where(fn ($query) => $colorId === null
                ? $query->whereNull('color_id')
                : $query->where('color_id', $colorId))
            ->where(fn ($query) => $sizeId === null
                ? $query->whereNull('size_id')
                : $query->where('size_id', $sizeId))
            ->first();
    }

    /**
     * Verify that a variant id genuinely belongs to this product.
     *
     * Scoping the lookup to the product is what stops a tampered request from
     * buying a variant of some other, cheaper product.
     */
    public function forProduct(Product $product, int $variantId): ?ProductVariant
    {
        return $product->variants()
            ->where('is_active', true)
            ->whereKey($variantId)
            ->first();
    }

    /**
     * The selectable matrix for a product, as plain data for the browser.
     *
     * Every entry is a real, active variant row. The storefront renders only
     * these, so a combination with no row cannot be selected in the first place.
     *
     * @return list<array<string, mixed>>
     */
    public function matrix(Product $product): array
    {
        return $product->variants
            ->where('is_active', true)
            ->map(fn (ProductVariant $variant): array => [
                'id'          => $variant->id,
                'color_id'    => $variant->color_id,
                'color_name'  => $variant->color?->name,
                'size_id'     => $variant->size_id,
                'size_name'   => $variant->size?->name,
                'sku'         => $variant->sku,
                'stock'       => $variant->stock_quantity,
                'price'       => $variant->effectivePrice(),
                'base_price'  => $variant->basePrice(),
                'on_sale'     => $variant->isOnSale(),
                'status'      => $variant->stockStatus()->value,
                'in_stock'    => $variant->stock_quantity > 0,
            ])
            ->values()
            ->all();
    }

    /**
     * Colours offered for this product, in catalog order.
     *
     * @return Collection<int, \App\Models\Color>
     */
    public function colors(Product $product): Collection
    {
        return $product->variants
            ->where('is_active', true)
            ->pluck('color')
            ->filter()
            ->unique('id')
            ->sortBy('sort_order')
            ->values();
    }

    /**
     * Sizes offered for this product, in wearable order.
     *
     * @return Collection<int, \App\Models\Size>
     */
    public function sizes(Product $product): Collection
    {
        return $product->variants
            ->where('is_active', true)
            ->pluck('size')
            ->filter()
            ->unique('id')
            ->sortBy('sort_order')
            ->values();
    }

    /**
     * The variant a page should preselect.
     *
     * Prefers something the customer can actually buy, so the page does not
     * open on a sold-out combination when stock exists elsewhere.
     */
    public function defaultVariant(Product $product): ?ProductVariant
    {
        $active = $product->variants->where('is_active', true);

        return $active->firstWhere(fn (ProductVariant $variant): bool => $variant->stock_quantity > 0)
            ?? $active->first();
    }
}
