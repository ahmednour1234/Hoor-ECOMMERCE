<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Reconciles a product's variants with the rows submitted by the admin form.
 *
 * The form posts the complete intended set, so this diffs it against what is
 * stored: rows carrying an id are updated, rows without one are created, and
 * anything no longer present is removed.
 *
 * Deletion is deliberately conservative — a variant that has ever been ordered
 * must not vanish, or historical order lines would lose the row they describe.
 * Such variants are deactivated instead.
 */
class SyncProductVariantsAction
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{created: int, updated: int, deleted: int, deactivated: int}
     */
    public function execute(Product $product, array $rows): array
    {
        $existing = $product->variants()->get()->keyBy('id');
        $submittedIds = collect($rows)
            ->pluck('id')
            ->filter()
            ->map(fn ($id): int => (int) $id);

        $result = ['created' => 0, 'updated' => 0, 'deleted' => 0, 'deactivated' => 0];

        foreach ($rows as $row) {
            $attributes = $this->attributesFrom($row);

            $id = isset($row['id']) ? (int) $row['id'] : null;
            $variant = $id !== null ? $existing->get($id) : null;

            if ($variant !== null) {
                $variant->update($attributes);
                $result['updated']++;

                continue;
            }

            $product->variants()->create($attributes);
            $result['created']++;
        }

        $removed = $this->removeMissing($existing, $submittedIds);

        return array_merge($result, $removed);
    }

    /**
     * @param  Collection<int, ProductVariant>  $existing
     * @param  Collection<int, int>  $submittedIds
     * @return array{deleted: int, deactivated: int}
     */
    private function removeMissing(Collection $existing, Collection $submittedIds): array
    {
        $deleted = 0;
        $deactivated = 0;

        foreach ($existing as $variant) {
            if ($submittedIds->contains($variant->id)) {
                continue;
            }

            if ($this->isReferencedByOrders($variant)) {
                // Keep the row so order history stays intact, but take it out
                // of circulation.
                $variant->update(['is_active' => false, 'stock_quantity' => 0]);
                $deactivated++;

                continue;
            }

            $variant->delete();
            $deleted++;
        }

        return ['deleted' => $deleted, 'deactivated' => $deactivated];
    }

    /**
     * Orders arrive in a later phase; until the table exists nothing can
     * reference a variant, so this is the correct answer rather than a stub.
     */
    private function isReferencedByOrders(ProductVariant $variant): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('order_items')) {
            return false;
        }

        return \Illuminate\Support\Facades\DB::table('order_items')
            ->where('product_variant_id', $variant->id)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function attributesFrom(array $row): array
    {
        return [
            'color_id'            => $row['color_id'] ?: null,
            'size_id'             => $row['size_id'] ?: null,
            'sku'                 => trim((string) $row['sku']),
            'stock_quantity'      => (int) ($row['stock_quantity'] ?? 0),
            'low_stock_threshold' => (int) ($row['low_stock_threshold'] ?? 3),
            // Blank overrides mean "inherit the product price", which is not
            // the same as a zero price.
            'price'               => $this->nullableMoney($row['price'] ?? null),
            'sale_price'          => $this->nullableMoney($row['sale_price'] ?? null),
            'is_active'           => (bool) ($row['is_active'] ?? false),
        ];
    }

    private function nullableMoney(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : \App\Casts\Money::fromMajor($value);
    }
}
