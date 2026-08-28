<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Category;
use App\Models\Color;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Support\ProductFilter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Query and persistence surface for products.
 *
 * Centralising the eager-load shapes matters more than the abstraction itself:
 * variants resolve their price through the parent product, so a listing that
 * forgets a relation silently turns into an N+1. Every read here loads what the
 * caller will actually touch.
 */
class ProductRepository
{
    /**
     * The price a shopper actually pays, expressed in SQL.
     *
     * A sale price only counts when it genuinely undercuts the base, mirroring
     * Product::effectivePrice() so filtering, sorting and display can never
     * disagree about what a product costs.
     */
    private const EFFECTIVE_PRICE_SQL =
        'CASE WHEN sale_price IS NOT NULL AND sale_price < base_price THEN sale_price ELSE base_price END';
    /**
     * Relations the admin listing renders for each row.
     *
     * @var list<string>
     */
    private const INDEX_RELATIONS = ['category', 'primaryImage', 'variants'];

    /**
     * Relations the admin edit form needs to render fully.
     *
     * @var list<string>
     */
    private const FORM_RELATIONS = [
        'category',
        'images',
        'variants.color',
        'variants.size',
    ];

    /**
     * Paginated, filtered listing for the admin index.
     *
     * @param  array{search?: string|null, category?: int|string|null, status?: string|null, stock?: string|null, sort?: string|null, direction?: string|null}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForAdmin(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->applyFilters(
            Product::query()->with(self::INDEX_RELATIONS),
            $filters,
        )->paginate($perPage)->withQueryString();
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        $query
            ->when(
                filled($filters['search'] ?? null),
                fn (Builder $q) => $q->where(function (Builder $q) use ($filters): void {
                    $term = $filters['search'];

                    $q->searchTranslation('name', $term)
                        ->orWhere('slug', 'like', "%{$term}%")
                        ->orWhereHas('variants', fn (Builder $v) => $v->where('sku', 'like', "%{$term}%"));
                }),
            )
            ->when(
                filled($filters['category'] ?? null),
                fn (Builder $q) => $q->where('category_id', $filters['category']),
            )
            ->when(
                filled($filters['status'] ?? null),
                fn (Builder $q) => $q->where('status', $filters['status']),
            );

        // Stock filters read through the variant rows, never a cached column.
        $query->when($filters['stock'] ?? null, fn (Builder $q, string $stock) => match ($stock) {
            'in'  => $q->whereHas('variants', fn (Builder $v) => $v->active()->inStock()),
            'low' => $q->whereHas('variants', fn (Builder $v) => $v->active()->inStock()->lowStock()),
            'out' => $q->whereDoesntHave('variants', fn (Builder $v) => $v->active()->inStock()),
            default => $q,
        });

        return $this->applySort($query, $filters);
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    private function applySort(Builder $query, array $filters): Builder
    {
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        // Whitelisted to keep user input out of the ORDER BY clause.
        return match ($filters['sort'] ?? null) {
            'name'   => $query->orderByTranslation('name', $direction),
            'price'  => $query->orderBy('base_price', $direction),
            'status' => $query->orderBy('status', $direction),
            default  => $query->orderBy('created_at', $direction),
        };
    }

    /**
     * Relations a storefront card renders.
     *
     * Variants carry price, availability AND the colour swatches the card
     * draws, so the colour relation is loaded too. Omitting it turns one
     * listing into a query per card.
     *
     * @var list<string>
     */
    private const CARD_RELATIONS = ['category', 'primaryImage', 'variants.color'];

    /**
     * Products a storefront visitor may see: published, and sellable.
     *
     * @return Builder<Product>
     */
    private function storefrontQuery(): Builder
    {
        return Product::query()
            ->published()
            ->with(self::CARD_RELATIONS);
    }

    /**
     * Newest published products, for the "New in" rail.
     *
     * Ordered by published_at so a data import cannot reshuffle the rail.
     *
     * @return Collection<int, Product>
     */
    public function newArrivals(int $limit = 8): Collection
    {
        return $this->storefrontQuery()
            ->where('is_new', true)
            ->orderByDesc('published_at')
            // Products published in the same second, from a seeder or a
            // bulk publish, would otherwise come back in any order.
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Hand-picked products for the featured rail.
     *
     * @return Collection<int, Product>
     */
    public function featured(int $limit = 8): Collection
    {
        return $this->storefrontQuery()
            ->featured()
            ->orderByDesc('published_at')
            // Products published in the same second, from a seeder or a
            // bulk publish, would otherwise come back in any order.
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Products carrying a genuine discount, for the sale banner.
     *
     * @return Collection<int, Product>
     */
    public function onSale(int $limit = 4): Collection
    {
        return $this->storefrontQuery()
            ->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'base_price')
            ->orderByDesc('published_at')
            // Products published in the same second, from a seeder or a
            // bulk publish, would otherwise come back in any order.
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Everything the homepage needs, in one place.
     *
     * Falls back from "new arrivals" to the most recent products so the rail is
     * never empty on a young catalog.
     *
     * @return array{new_arrivals: Collection<int, Product>, featured: Collection<int, Product>, on_sale: Collection<int, Product>}
     */
    public function forHomepage(int $perRail = 4): array
    {
        return [
            'new_arrivals' => $this->topUp($this->newArrivals($perRail), $perRail),
            'featured'     => $this->topUp($this->featured($perRail), $perRail),
            'on_sale'      => $this->onSale(),
        ];
    }

    /**
     * Pad a rail to a full row with other published products.
     *
     * A four-column grid holding three cards reads as broken rather than as a
     * short list, and a young catalog will not always have enough flagged
     * products. Padding preserves the flagged ones at the front and never
     * repeats a product already in the rail.
     *
     * @param  Collection<int, Product>  $products
     * @return Collection<int, Product>
     */
    private function topUp(Collection $products, int $target): Collection
    {
        if ($products->count() >= $target) {
            return $products;
        }

        $filler = $this->storefrontQuery()
            ->whereNotIn('id', $products->modelKeys())
            ->inStock()
            ->orderByDesc('published_at')
            // Products published in the same second, from a seeder or a
            // bulk publish, would otherwise come back in any order.
            ->orderByDesc('id')
            ->limit($target - $products->count())
            ->get();

        return $products->concat($filler);
    }

    /**
     * Relations the shop grid renders per card.
     *
     * `variants.color` matters: the card draws a swatch per colour, and without
     * it every card would re-query, turning one page into dozens of queries.
     *
     * @var list<string>
     */
    private const SHOP_RELATIONS = ['category', 'primaryImage', 'variants.color'];

    /**
     * Paginated shop listing.
     *
     * Every filter is expressed as SQL — nothing is loaded into PHP and then
     * discarded — so the query cost stays flat regardless of catalog size.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForShop(ProductFilter $filter, int $perPage = ProductFilter::PER_PAGE): LengthAwarePaginator
    {
        $query = Product::query()
            ->published()
            ->with(self::SHOP_RELATIONS);

        $this->applyShopFilters($query, $filter);
        $this->applyShopSort($query, $filter);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Count matching products without paginating, for the results header.
     */
    public function countForShop(ProductFilter $filter): int
    {
        $query = Product::query()->published();

        $this->applyShopFilters($query, $filter);

        return $query->count();
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyShopFilters(Builder $query, ProductFilter $filter): void
    {
        // Category matches the category itself or any of its children, so
        // choosing "Jeans" includes everything filed under Wide Leg and friends.
        $query->when($filter->categories !== [], function (Builder $query) use ($filter): void {
            $query->whereHas('category', function (Builder $category) use ($filter): void {
                $category->where(function (Builder $inner) use ($filter): void {
                    $inner->whereIn('slug', $filter->categories)
                        ->orWhereHas('parent', fn (Builder $parent) => $parent->whereIn('slug', $filter->categories));
                });
            });
        });

        // Size and colour are variant attributes, and both constraints apply to
        // the SAME variant row: "M in indigo" means one variant that is both,
        // not a product that happens to offer each somewhere.
        $query->when(
            $filter->sizes !== [] || $filter->colors !== [] || $filter->inStockOnly,
            function (Builder $query) use ($filter): void {
                $query->whereHas('variants', function (Builder $variant) use ($filter): void {
                    $variant->where('is_active', true);

                    $variant->when(
                        $filter->sizes !== [],
                        fn (Builder $q) => $q->whereHas(
                            'size',
                            fn (Builder $s) => $s->whereIn(DB::raw('LOWER(code)'), $filter->sizes),
                        ),
                    );

                    $variant->when(
                        $filter->colors !== [],
                        fn (Builder $q) => $q->whereHas(
                            'color',
                            fn (Builder $c) => $c->whereIn('slug', $filter->colors),
                        ),
                    );

                    $variant->when(
                        $filter->inStockOnly,
                        fn (Builder $q) => $q->where('stock_quantity', '>', 0),
                    );
                });
            },
        );

        $query->when($filter->newArrivals, fn (Builder $q) => $q->where('is_new', true));

        // "On sale" means a discount that genuinely undercuts the base price,
        // matching what the card shows.
        $query->when($filter->onSale, fn (Builder $q) => $q
            ->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'base_price'));

        // Price filters compare the effective price, so a discounted product is
        // matched on what a shopper would actually pay.
        $effective = self::EFFECTIVE_PRICE_SQL;

        $query->when(
            $filter->minPrice !== null,
            fn (Builder $q) => $q->whereRaw("{$effective} >= ?", [$filter->minPrice]),
        );

        $query->when(
            $filter->maxPrice !== null,
            fn (Builder $q) => $q->whereRaw("{$effective} <= ?", [$filter->maxPrice]),
        );

        $query->when($filter->search !== null, fn (Builder $q) => $q->where(
            fn (Builder $inner) => $inner
                ->searchTranslation('name', $filter->search)
                ->orWhereHas('variants', fn (Builder $v) => $v->where('sku', 'like', '%'.$filter->search.'%')),
        ));
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyShopSort(Builder $query, ProductFilter $filter): void
    {
        $effective = self::EFFECTIVE_PRICE_SQL;

        match ($filter->sort) {
            'price_asc'    => $query->orderByRaw("{$effective} asc"),
            'price_desc'   => $query->orderByRaw("{$effective} desc"),
            'name'         => $query->orderByTranslation('name'),
            'best_selling' => $this->applyBestSellingSort($query),
            default        => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }

    /**
     * Rank by units sold.
     *
     * Guarded on the orders table existing so the sort degrades to "newest"
     * rather than erroring before the order module ships.
     *
     * @param  Builder<Product>  $query
     */
    private function applyBestSellingSort(Builder $query): void
    {
        if (! Schema::hasTable('order_items')) {
            $query->orderByDesc('published_at')->orderByDesc('id');

            return;
        }

        /*
         * Rank by units actually sold, read from the order lines themselves
         * rather than a denormalised counter that could drift.
         *
         * Cancelled and returned orders are excluded: they released their
         * stock, so they are not sales.
         */
        $query->withCount([
            'orderItems as units_sold' => fn ($items) => $items
                ->select(DB::raw('COALESCE(SUM(order_items.quantity), 0)'))
                ->whereHas('order', fn ($order) => $order->holdingStock()),
        ])
            ->orderByDesc('units_sold')
            ->orderByDesc('published_at')->orderByDesc('id');
    }

    /**
     * Facet values for the filter panel, plus the catalog's price bounds.
     *
     * Each list is one query rather than a count per option, so this stays a
     * handful of queries no matter how large the catalog grows.
     *
     * @return array{categories: mixed, sizes: mixed, colors: mixed, price: array{min: int, max: int}}
     */
    public function shopFacets(): array
    {
        $published = fn (Builder $query) => $query->published();

        /*
         * Products are filed against the most specific category, so a parent
         * such as "Jeans" holds none of its own. Counting only direct products
         * would drop the broadest and most useful filter from the panel, so
         * each category is counted across itself and its children.
         *
         * One grouped query feeds every count rather than a query per row.
         */
        $categories = Category::query()
            ->active()
            ->with(['children' => fn ($query) => $query->active()])
            ->ordered()
            ->get();

        $directCounts = Product::query()
            ->published()
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');

        $categories = $categories
            ->each(function (Category $category) use ($directCounts): void {
                $ids = $category->children->pluck('id')->push($category->id);

                $category->setAttribute(
                    'products_count',
                    (int) $ids->sum(fn (int $id): int => (int) ($directCounts[$id] ?? 0)),
                );
            })
            ->filter(fn (Category $category): bool => $category->products_count > 0)
            // Parents first, so the broad filters lead the list.
            ->sortBy(fn (Category $category): array => [$category->parent_id !== null ? 1 : 0, $category->sort_order])
            ->values();

        $sizes = Size::query()
            ->active()
            ->ordered()
            ->whereHas('variants.product', $published)
            ->get();

        $colors = Color::query()
            ->active()
            ->ordered()
            ->whereHas('variants.product', $published)
            ->get();

        $bounds = Product::query()
            ->published()
            ->selectRaw(sprintf(
                'MIN(%1$s) as min_price, MAX(%1$s) as max_price',
                self::EFFECTIVE_PRICE_SQL,
            ))
            ->first();

        return [
            'categories' => $categories,
            'sizes'      => $sizes,
            'colors'     => $colors,
            'price'      => [
                'min' => (int) ($bounds->min_price ?? 0),
                'max' => (int) ($bounds->max_price ?? 0),
            ],
        ];
    }
    /**
     * Relations the product detail page renders.
     *
     * The gallery, the colour and size selectors and the price all read through
     * these, so loading them together keeps the page to a handful of queries.
     *
     * @var list<string>
     */
    private const DETAIL_RELATIONS = [
        'category',
        'images',
        'variants.color',
        'variants.size',
    ];

    /**
     * Load everything the product detail page needs.
     */
    public function loadForDetail(Product $product): Product
    {
        return $product->load(self::DETAIL_RELATIONS);
    }

    /**
     * Products to suggest alongside the one being viewed.
     *
     * Prefers siblings in the same category, then falls back to the wider
     * catalog so the rail is never short on a small or narrow category.
     *
     * @return Collection<int, Product>
     */
    public function related(Product $product, int $limit = 4): Collection
    {
        $siblings = Product::query()
            ->published()
            ->with(self::CARD_RELATIONS)
            ->whereKeyNot($product->getKey())
            ->where('category_id', $product->category_id)
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        if ($siblings->count() >= $limit) {
            return $siblings;
        }

        $filler = Product::query()
            ->published()
            ->with(self::CARD_RELATIONS)
            ->whereKeyNot($product->getKey())
            ->whereNotIn('id', $siblings->modelKeys())
            ->inStock()
            ->orderByDesc('published_at')
            // Products published in the same second, from a seeder or a
            // bulk publish, would otherwise come back in any order.
            ->orderByDesc('id')
            ->limit($limit - $siblings->count())
            ->get();

        return $siblings->concat($filler);
    }
    /**
     * Load a product with everything the edit form renders.
     */
    public function loadForForm(Product $product): Product
    {
        return $product->load(self::FORM_RELATIONS);
    }

    public function findBySlug(string $slug): ?Product
    {
        return Product::query()->with(self::FORM_RELATIONS)->where('slug', $slug)->first();
    }

    /**
     * Counters shown on the admin dashboard and index header.
     *
     * @return array{total: int, published: int, draft: int, out_of_stock: int, low_stock: int}
     */
    public function statistics(): array
    {
        $byStatus = Product::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'total'        => (int) $byStatus->sum(),
            'published'    => (int) ($byStatus[ProductStatus::Published->value] ?? 0),
            'draft'        => (int) ($byStatus[ProductStatus::Draft->value] ?? 0),
            'out_of_stock' => Product::query()
                ->whereDoesntHave('variants', fn (Builder $v) => $v->active()->inStock())
                ->count(),
            'low_stock'    => ProductVariant::query()->active()->inStock()->lowStock()->count(),
        ];
    }

    /**
     * Variants at or below their reorder threshold, for the admin report.
     *
     * @return Collection<int, ProductVariant>
     */
    public function lowStockVariants(int $limit = 25): Collection
    {
        return ProductVariant::query()
            ->with(['product', 'color', 'size'])
            ->active()
            ->inStock()
            ->lowStock()
            ->orderBy('stock_quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Product
    {
        return Product::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Product $product, array $attributes): Product
    {
        $product->update($attributes);

        return $product->refresh();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
