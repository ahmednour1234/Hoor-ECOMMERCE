<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrderItem;
use App\Models\ProductVariant;

/**
 * Whether a replacement variant can actually be sent.
 *
 * Asked twice, deliberately: once when the customer raises the exchange, and
 * again when staff approve it. Stock moves in between — the whole point of a
 * fashion store is that the last size 38 goes to whoever gets there first — so
 * an answer given at request time is not evidence at approval time.
 *
 * Kept apart from the actions so both can ask the same question and get the
 * same answer.
 */
class ExchangeAvailability
{
    /**
     * Why a replacement is not acceptable, or null if it is.
     *
     * Returns a reason key rather than a boolean so the caller can tell the
     * customer which rule she tripped: "that size is sold out" and "that is not
     * this product" need different answers.
     */
    public function reject(OrderItem $item, ProductVariant $replacement, int $quantity): ?string
    {
        // An exchange swaps within a product. Sending a different garment is a
        // new order, not an exchange, and would break the money story: the
        // return carries no price adjustment.
        if ($replacement->product_id !== $item->product_id) {
            return 'different_product';
        }

        if (! $replacement->is_active) {
            return 'inactive';
        }

        if ($replacement->stock_quantity < $quantity) {
            return 'out_of_stock';
        }

        return null;
    }

    public function isAvailable(OrderItem $item, ProductVariant $replacement, int $quantity): bool
    {
        return $this->reject($item, $replacement, $quantity) === null;
    }

    /**
     * The variants a customer may swap this line for.
     *
     * Everything sellable on the same product, including what she already has —
     * exchanging a size 38 for another size 38 is a legitimate request when the
     * first arrived damaged.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ProductVariant>
     */
    public function optionsFor(OrderItem $item, int $quantity = 1): \Illuminate\Database\Eloquent\Collection
    {
        return ProductVariant::query()
            ->with(['size', 'color'])
            ->where('product_id', $item->product_id)
            ->where('is_active', true)
            ->where('stock_quantity', '>=', $quantity)
            ->orderBy('size_id')
            ->orderBy('color_id')
            ->get();
    }

    /**
     * Sellable variants for several products at once, keyed by product.
     *
     * The bulk form of optionsFor(): a return form lists every line on the
     * order, and asking per line is one query per garment.
     *
     * @param  list<int>  $productIds
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, ProductVariant>>
     */
    public function optionsForProducts(array $productIds, int $quantity = 1): \Illuminate\Support\Collection
    {
        if ($productIds === []) {
            return collect();
        }

        return ProductVariant::query()
            ->with(['size', 'color'])
            ->whereIn('product_id', $productIds)
            ->where('is_active', true)
            ->where('stock_quantity', '>=', $quantity)
            ->orderBy('size_id')
            ->orderBy('color_id')
            ->get()
            ->groupBy('product_id');
    }

    /**
     * The snapshot columns to write alongside the replacement.
     *
     * Frozen for the same reason order items are: a variant renamed or retired
     * must not change what this exchange said when it was agreed.
     *
     * @return array<string, mixed>
     */
    public function snapshotFor(ProductVariant $replacement): array
    {
        return [
            'replacement_variant_id' => $replacement->id,
            'replacement_sku'        => $replacement->sku,
            'replacement_size_ar'    => $replacement->size?->name_ar,
            'replacement_size_en'    => $replacement->size?->name_en,
            'replacement_color_ar'   => $replacement->color?->name_ar,
            'replacement_color_en'   => $replacement->color?->name_en,
        ];
    }
}
