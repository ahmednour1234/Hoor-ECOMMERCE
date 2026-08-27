<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Products a customer has saved.
 *
 * Signed-in only. A guest wishlist would have to live in the session and then
 * merge on login, which is a real feature with real edge cases — worth doing
 * deliberately rather than as a side effect of this phase.
 */
class WishlistService
{
    /**
     * The same eager-load shape the product card renders from.
     *
     * A wishlist is a grid of cards, so it needs exactly what the shop grid
     * needs; forgetting the swatches here is how the N+1 crept back in before.
     *
     * @var list<string>
     */
    private const CARD_RELATIONS = ['category', 'primaryImage', 'variants.color'];

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return $user->wishlistProducts()
            ->with(self::CARD_RELATIONS)
            ->orderByDesc('wishlists.created_at')
            ->paginate($perPage);
    }

    /**
     * Save a product, or do nothing if it is already saved.
     *
     * Idempotent by design: the heart icon is a toggle, and a double click
     * should not be an error.
     */
    public function add(User $user, Product $product): bool
    {
        if ($this->has($user, $product)) {
            return false;
        }

        $user->wishlist()->create(['product_id' => $product->id]);

        return true;
    }

    public function remove(User $user, Product $product): bool
    {
        return $user->wishlist()->where('product_id', $product->id)->delete() > 0;
    }

    /**
     * Flip the state, reporting what it became.
     *
     * @return array{saved: bool, count: int}
     */
    public function toggle(User $user, Product $product): array
    {
        $saved = $this->has($user, $product)
            ? ! $this->remove($user, $product)
            : $this->add($user, $product);

        return ['saved' => $saved, 'count' => $this->count($user)];
    }

    public function has(User $user, Product $product): bool
    {
        return $user->wishlist()->where('product_id', $product->id)->exists();
    }

    public function count(User $user): int
    {
        return $user->wishlist()->count();
    }

    /**
     * Which of these products the customer has saved.
     *
     * One query for a whole grid, so a listing can mark its hearts without
     * asking per card.
     *
     * @param  list<int>  $productIds
     * @return list<int>
     */
    public function savedAmong(User $user, array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        return $user->wishlist()
            ->whereIn('product_id', $productIds)
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
