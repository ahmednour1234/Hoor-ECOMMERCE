<?php

declare(strict_types=1);

namespace App\Support;

use App\Casts\Money;
use Illuminate\Http\Request;

/**
 * Parsed, validated shape of the shop page's query string.
 *
 * Every value is whitelisted here so nothing user-supplied reaches a query
 * builder: sort keys map to a fixed set, prices are clamped to sane bounds, and
 * slugs stay slugs. The object is readonly, so a filter set cannot drift
 * between the query that fetches products and the one that counts facets.
 */
final readonly class ProductFilter
{
    /**
     * Sort keys the shop accepts, mapped to their meaning.
     *
     * `best_selling` is listed but only offered once order data exists — see
     * isSortAvailable().
     *
     * @var list<string>
     */
    public const SORTS = ['newest', 'price_asc', 'price_desc', 'name', 'best_selling'];

    public const DEFAULT_SORT = 'newest';

    public const PER_PAGE = 12;

    /**
     * @param  list<string>  $categories
     * @param  list<string>  $sizes
     * @param  list<string>  $colors
     */
    public function __construct(
        public array $categories = [],
        public array $sizes = [],
        public array $colors = [],
        public ?int $minPrice = null,      // piastres
        public ?int $maxPrice = null,      // piastres
        public bool $newArrivals = false,
        public bool $onSale = false,
        public bool $inStockOnly = false,
        public ?string $search = null,
        public string $sort = self::DEFAULT_SORT,
    ) {
    }

    /**
     * Build a filter from the request's query string.
     *
     * Accepts both repeated params (?color=indigo&color=sand) and comma lists
     * (?color=indigo,sand), because both forms occur in shared links.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            categories:  self::slugList($request, 'category'),
            sizes:       self::slugList($request, 'size'),
            colors:      self::slugList($request, 'color'),
            minPrice:    self::price($request, 'min_price'),
            maxPrice:    self::price($request, 'max_price'),
            newArrivals: $request->boolean('new'),
            onSale:      $request->boolean('sale'),
            inStockOnly: $request->boolean('in_stock'),
            search:      self::searchTerm($request),
            sort:        self::sort($request),
        );
    }

    /**
     * @return list<string>
     */
    private static function slugList(Request $request, string $key): array
    {
        $raw = $request->query($key);

        if ($raw === null || $raw === '') {
            return [];
        }

        $values = is_array($raw) ? $raw : explode(',', (string) $raw);

        return collect($values)
            ->map(fn ($value): string => strtolower(trim((string) $value)))
            // Slugs only: keeps anything exotic out of the query entirely.
            ->filter(fn (string $value): bool => $value !== '' && preg_match('/^[a-z0-9-]{1,80}$/', $value) === 1)
            ->unique()
            ->take(20)
            ->values()
            ->all();
    }

    /**
     * Prices arrive in EGP and are stored as piastres.
     */
    private static function price(Request $request, string $key): ?int
    {
        $raw = $request->query($key);

        if ($raw === null || $raw === '' || ! is_numeric($raw)) {
            return null;
        }

        $amount = max(0.0, min((float) $raw, 9_999_999.0));

        return Money::fromMajor($amount);
    }

    private static function searchTerm(Request $request): ?string
    {
        $term = trim((string) $request->query('q', $request->query('search', '')));

        return $term === '' ? null : mb_substr($term, 0, 80);
    }

    private static function sort(Request $request): string
    {
        $sort = (string) $request->query('sort', self::DEFAULT_SORT);

        return in_array($sort, self::SORTS, strict: true) ? $sort : self::DEFAULT_SORT;
    }

    /**
     * Whether any narrowing filter is applied (sorting alone does not count).
     */
    public function isActive(): bool
    {
        return $this->categories !== []
            || $this->sizes !== []
            || $this->colors !== []
            || $this->minPrice !== null
            || $this->maxPrice !== null
            || $this->newArrivals
            || $this->onSale
            || $this->inStockOnly
            || $this->search !== null;
    }

    public function activeCount(): int
    {
        return count($this->categories)
            + count($this->sizes)
            + count($this->colors)
            + ($this->minPrice !== null || $this->maxPrice !== null ? 1 : 0)
            + (int) $this->newArrivals
            + (int) $this->onSale
            + (int) $this->inStockOnly
            + ($this->search !== null ? 1 : 0);
    }

    public function hasCategory(string $slug): bool
    {
        return in_array($slug, $this->categories, strict: true);
    }

    public function hasSize(string $code): bool
    {
        return in_array(strtolower($code), $this->sizes, strict: true);
    }

    public function hasColor(string $slug): bool
    {
        return in_array($slug, $this->colors, strict: true);
    }

    /**
     * The query string for this filter set, as an array suitable for route().
     *
     * Empty values are dropped so shared URLs stay short and canonical.
     *
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return array_filter([
            'category'  => implode(',', $this->categories),
            'size'      => implode(',', $this->sizes),
            'color'     => implode(',', $this->colors),
            'min_price' => $this->minPrice !== null ? (string) Money::toMajor($this->minPrice) : '',
            'max_price' => $this->maxPrice !== null ? (string) Money::toMajor($this->maxPrice) : '',
            'new'       => $this->newArrivals ? '1' : '',
            'sale'      => $this->onSale ? '1' : '',
            'in_stock'  => $this->inStockOnly ? '1' : '',
            'q'         => $this->search ?? '',
            'sort'      => $this->sort === self::DEFAULT_SORT ? '' : $this->sort,
        ], static fn (string $value): bool => $value !== '');
    }

    /**
     * Copy of this filter with one facet value toggled on or off.
     *
     * Used to build every checkbox link, so a click always produces a URL that
     * round-trips back through fromRequest() unchanged.
     */
    public function toggle(string $facet, string $value): self
    {
        $value = strtolower($value);

        $flip = function (array $current) use ($value): array {
            return in_array($value, $current, strict: true)
                ? array_values(array_diff($current, [$value]))
                : [...$current, $value];
        };

        return new self(
            categories:  $facet === 'category' ? $flip($this->categories) : $this->categories,
            sizes:       $facet === 'size'     ? $flip($this->sizes)      : $this->sizes,
            colors:      $facet === 'color'    ? $flip($this->colors)     : $this->colors,
            minPrice:    $this->minPrice,
            maxPrice:    $this->maxPrice,
            newArrivals: $facet === 'new'      ? ! $this->newArrivals : $this->newArrivals,
            onSale:      $facet === 'sale'     ? ! $this->onSale      : $this->onSale,
            inStockOnly: $facet === 'in_stock' ? ! $this->inStockOnly : $this->inStockOnly,
            search:      $this->search,
            sort:        $this->sort,
        );
    }

    public function withSort(string $sort): self
    {
        return new self(
            categories: $this->categories,
            sizes: $this->sizes,
            colors: $this->colors,
            minPrice: $this->minPrice,
            maxPrice: $this->maxPrice,
            newArrivals: $this->newArrivals,
            onSale: $this->onSale,
            inStockOnly: $this->inStockOnly,
            search: $this->search,
            sort: in_array($sort, self::SORTS, strict: true) ? $sort : self::DEFAULT_SORT,
        );
    }

    /**
     * Sorting by sales needs order history, which arrives with the order
     * module. Offering it before then would be a control that does nothing.
     */
    public static function isSortAvailable(string $sort): bool
    {
        if ($sort !== 'best_selling') {
            return true;
        }

        return \Illuminate\Support\Facades\Schema::hasTable('order_items');
    }

    /**
     * @return list<string>
     */
    public static function availableSorts(): array
    {
        return array_values(array_filter(self::SORTS, self::isSortAvailable(...)));
    }
}
