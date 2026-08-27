<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\ContentService;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;

/**
 * Storefront landing page.
 *
 * Every section is driven by the database — nothing on this page is hardcoded.
 * The repository owns the query shapes so each rail arrives fully eager-loaded,
 * and which sections appear at all is the admin's decision, read from settings.
 */
class HomeController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly SettingsService $settings,
        private readonly ContentService $content,
    ) {
    }

    public function __invoke(): View
    {
        return view('store.home', [
            'sections'   => $this->products->forHomepage(),
            'categories' => $this->shoppableCategories(),

            // Slides the admin manages, falling back to the brand plates.
            'slides' => $this->content->heroSlides(),

            // A managed banner takes the promo slot when one is live.
            'promoBanner' => $this->content->banner('home_promo'),

            'featuredTitle' => $this->settings->translated('homepage.featured_title'),

            /*
             * Which sections to draw.
             *
             * Passed as a closure rather than an array so the template reads
             * `$show('hero')` — and so a section added to the page without a
             * matching setting defaults to visible rather than silently
             * disappearing.
             */
            'show' => fn (string $section): bool => $this->settings->boolean(
                'homepage.show_'.$section,
                true,
            ),
        ]);
    }

    /**
     * Top-level categories that actually have something to sell.
     *
     * Products are filed against the most specific category (a pair of jeans
     * sits under "Wide Leg", not "Jeans"), so a parent must be counted across
     * its children too — counting only direct products would hide the largest
     * category on the site.
     *
     * @return \Illuminate\Support\Collection<int, Category>
     */
    private function shoppableCategories()
    {
        $roots = Category::query()
            ->active()
            ->roots()
            ->with(['children' => fn ($query) => $query->active()])
            ->ordered()
            ->get();

        // One grouped query for every branch, rather than a count per category.
        $counts = Product::query()
            ->published()
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');

        // A representative photo per branch, so a category without its own
        // banner still shows real product imagery rather than a flat panel.
        $covers = $this->categoryCovers();

        return $roots
            ->each(function (Category $category) use ($counts, $covers): void {
                $ids = $category->children->pluck('id')->push($category->id);

                $category->setAttribute(
                    'products_count',
                    (int) $ids->sum(fn (int $id): int => (int) ($counts[$id] ?? 0)),
                );

                $category->setAttribute(
                    'cover_path',
                    $ids->map(fn (int $id): ?string => $covers[$id] ?? null)->filter()->first(),
                );
            })
            ->filter(fn (Category $category): bool => $category->products_count > 0)
            ->take(6)
            ->values();
    }

    /**
     * Primary image path for one published product in each category.
     *
     * Resolved in two queries rather than one per category.
     *
     * @return array<int, string>
     */
    private function categoryCovers(): array
    {
        return Product::query()
            ->published()
            ->with('primaryImage')
            ->get(['id', 'category_id'])
            ->reduce(function (array $carry, Product $product): array {
                $path = $product->primaryImage?->path;

                if ($path !== null && ! isset($carry[$product->category_id])) {
                    $carry[$product->category_id] = $path;
                }

                return $carry;
            }, []);
    }
}
