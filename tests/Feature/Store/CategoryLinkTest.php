<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Category tiles and menu entries must lead somewhere.
 *
 * The bug worth guarding: both were wrapped in a Route::has() check for
 * `store.categories.show`, a route that was never defined — so every tile and
 * every menu item quietly fell back to the homepage. Nothing errored; the
 * links simply did nothing.
 */
class CategoryLinkTest extends TestCase
{
    use RefreshDatabase;

    private function seedCategory(string $slug = 'denim-skirts'): Category
    {
        $category = Category::factory()->create(['slug' => $slug, 'is_active' => true]);

        Product::factory()->create(['category_id' => $category->id]);

        return $category;
    }

    /**
     * The tile, specifically.
     *
     * Matched on the card's own class list rather than on the URL alone: the
     * header menu links to the same place, so a URL-only assertion passes even
     * when every tile is broken — which is exactly what happened the first
     * time this test was written.
     */
    public function test_a_category_tile_links_into_the_filtered_shop(): void
    {
        $this->seedCategory('denim-skirts');

        $html = $this->get(route('store.home', ['locale' => 'en']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '#<a href="[^"]*shop\?category=denim-skirts"[^>]*class="group relative block aspect#s',
            $html,
            'the category tile should link into the filtered shop',
        );
    }

    /**
     * A tile that points at the homepage is a tile that does nothing.
     */
    public function test_no_category_tile_falls_back_to_the_homepage(): void
    {
        $this->seedCategory('jackets');

        $html = $this->get(route('store.home', ['locale' => 'en']))->assertOk()->getContent();

        // Find every tile and confirm none of them is a bare homepage link.
        preg_match_all('#<a href="([^"]*)"[^>]*class="group relative block aspect#s', $html, $matches);

        $this->assertNotEmpty($matches[1], 'no category tiles were rendered');

        foreach ($matches[1] as $href) {
            $this->assertStringContainsString('category=', $href);
        }
    }

    public function test_the_link_actually_filters(): void
    {
        $skirts = $this->seedCategory('denim-skirts');
        $jackets = $this->seedCategory('jackets');

        $skirt = Product::query()->where('category_id', $skirts->id)->firstOrFail();
        $jacket = Product::query()->where('category_id', $jackets->id)->firstOrFail();

        $this->get(route('store.shop', ['locale' => 'en', 'category' => 'denim-skirts']))
            ->assertOk()
            ->assertSee($skirt->name)
            ->assertDontSee($jacket->name);
    }

    public function test_the_header_menu_links_into_the_shop_too(): void
    {
        $this->seedCategory('wide-leg');

        $html = $this->get(route('store.home', ['locale' => 'en']))->assertOk()->getContent();

        $this->assertStringContainsString('shop?category=wide-leg', $html);
    }
}
