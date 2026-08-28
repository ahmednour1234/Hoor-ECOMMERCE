<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Support\ProductFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Scrolling loads the next page of results.
 *
 * The browser half is an IntersectionObserver, which these cannot run. What
 * they hold is the contract it depends on: the server has to publish where the
 * next page is, keep the active filters on that link, and render each page as a
 * grid the script can lift the tiles out of.
 */
class ShopInfiniteScrollTest extends TestCase
{
    use RefreshDatabase;

    private function products(int $count, array $attributes = []): void
    {
        foreach (range(1, $count) as $i) {
            $product = Product::factory()->create($attributes + [
                'name_en' => "Product {$i}",
            ]);

            ProductImage::factory()->for($product)->primary()->create();
            ProductVariant::factory()->for($product)->inStock(5)->create();
        }
    }

    private function shop(array $query = []): \Illuminate\Testing\TestResponse
    {
        return $this->get(route('store.shop', ['locale' => 'en'] + $query));
    }

    public function test_the_first_page_advertises_where_the_next_one_is(): void
    {
        $this->products(ProductFilter::PER_PAGE + 3);

        $response = $this->shop()->assertOk();

        $next = $this->nextUrl($response->getContent());

        $this->assertNotNull($next, 'the pager must publish a next page');
        $this->assertStringContainsString('page=2', $next);
    }

    public function test_the_last_page_advertises_no_next_page(): void
    {
        // One short of a second page, so the only page is also the last.
        $this->products(ProductFilter::PER_PAGE - 1);

        $next = $this->nextUrl($this->shop()->assertOk()->getContent());

        $this->assertSame(
            '',
            (string) $next,
            'the script stops on an empty data-next; a stale URL would loop',
        );
    }

    public function test_the_next_page_keeps_the_active_filters(): void
    {
        $category = Category::factory()->create(['slug' => 'jeans']);

        $this->products(ProductFilter::PER_PAGE + 3, ['category_id' => $category->id]);

        $next = $this->nextUrl(
            $this->shop(['category' => 'jeans', 'sort' => 'price_asc'])->assertOk()->getContent()
        );

        // Losing these would silently append unfiltered products to a filtered
        // grid — the customer would see items they had excluded.
        $this->assertStringContainsString('category=jeans', urldecode((string) $next));
        $this->assertStringContainsString('sort=price_asc', urldecode((string) $next));
    }

    public function test_every_page_renders_the_grid_the_script_appends_from(): void
    {
        $this->products(ProductFilter::PER_PAGE + 3);

        // The script lifts the children of #shop-grid out of the fetched page,
        // so page 2 has to carry that id as well as page 1.
        foreach ([1, 2] as $page) {
            $this->shop(['page' => $page])
                ->assertOk()
                ->assertSee('id="shop-grid"', false);
        }
    }

    public function test_the_pages_do_not_overlap(): void
    {
        $this->products(ProductFilter::PER_PAGE + 3);

        $first  = $this->shop()->assertOk()->getContent();
        $second = $this->shop(['page' => 2])->assertOk()->getContent();

        // A product shown twice is what a broken tiebreaker looks like once the
        // pages are stitched together by scrolling instead of clicking.
        $overlap = array_intersect($this->names($first), $this->names($second));

        $this->assertSame([], array_values($overlap), 'a product appeared on both pages');
    }

    public function test_the_numbered_pager_survives_for_browsers_without_javascript(): void
    {
        $this->products(ProductFilter::PER_PAGE + 3);

        // The real links stay in the markup; the script only hides them.
        $this->shop()->assertOk()->assertSee('page=2', false);
    }

    private function nextUrl(string $html): ?string
    {
        preg_match('/id="shop-pager"[^>]*data-next="([^"]*)"/', $html, $matches);

        return $matches[1] ?? null;
    }

    /** @return list<string> */
    private function names(string $html): array
    {
        preg_match_all('/Product \d+/', $html, $matches);

        return array_values(array_unique($matches[0]));
    }
}
