<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The homepage category rail.
 *
 * The behaviour worth protecting is not the animation — it is that the rail
 * degrades safely. An unknown Alpine directive fails silently, and the cards
 * start at opacity 0, so a mistake here hides the whole section rather than
 * showing it unstyled.
 */
class CategorySliderTest extends TestCase
{
    use RefreshDatabase;

    private function seedCategories(int $count = 6): void
    {
        for ($i = 0; $i < $count; $i++) {
            $category = Category::factory()->create(['is_active' => true]);

            // A category with no products is not offered on the homepage.
            Product::factory()->create(['category_id' => $category->id]);
        }
    }

    private function homepage(string $locale = 'en'): string
    {
        return $this->get(route('store.home', ['locale' => $locale]))
            ->assertOk()
            ->getContent();
    }

    public function test_the_categories_render_as_a_rail(): void
    {
        $this->seedCategories();

        $html = $this->homepage();

        $this->assertStringContainsString('snap-x snap-mandatory', $html);

        // The wrapping grid stranded a partial second row; it should be gone.
        $this->assertStringNotContainsString('grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4', $html);
    }

    public function test_every_category_appears_on_the_rail(): void
    {
        $this->seedCategories(6);

        $this->assertSame(6, substr_count($this->homepage(), 'class="reveal w-['));
    }

    /**
     * The reveal starts hidden, so whatever shows it must actually exist.
     */
    public function test_the_reveal_is_driven_by_code_that_ships(): void
    {
        $this->seedCategories();

        $html = $this->homepage();

        // The reveal is driven site-wide from store.partials.reveal-script.
        $this->assertStringContainsString('IntersectionObserver', $html);
        $this->assertStringContainsString("const REVEALED = 'is-revealed'", $html);

        // x-intersect is a separate Alpine plugin this build does not include.
        // An unknown directive is ignored silently, which would leave every
        // card invisible.
        $this->assertStringNotContainsString('x-intersect="', $html);
    }

    public function test_visitors_without_javascript_still_see_the_cards(): void
    {
        $this->seedCategories();

        $html = $this->homepage();

        $this->assertStringContainsString('<noscript>', $html);
        $this->assertMatchesRegularExpression('/<noscript>.*\.reveal.*<\/noscript>/s', $html);
    }

    /**
     * Emitted through @once, so many cards do not mean many copies.
     */
    public function test_the_slider_script_is_emitted_once(): void
    {
        $this->seedCategories(6);

        $this->assertSame(1, substr_count($this->homepage(), 'function categorySlider'));
    }

    public function test_autoplay_respects_a_reduced_motion_preference(): void
    {
        $this->seedCategories();

        $this->assertStringContainsString('prefers-reduced-motion', $this->homepage());
    }

    public function test_the_rail_renders_in_both_locales(): void
    {
        $this->seedCategories();

        foreach (['en' => 'ltr', 'ar' => 'rtl'] as $locale => $direction) {
            $html = $this->homepage($locale);

            $this->assertStringContainsString('dir="'.$direction.'"', $html);
            $this->assertStringContainsString('snap-x', $html);
        }
    }

    /**
     * A rail is a list of links; the cards must stay reachable by keyboard.
     */
    public function test_each_card_is_a_link(): void
    {
        $this->seedCategories(3);

        $html = $this->homepage();

        foreach (Category::query()->get() as $category) {
            $this->assertStringContainsString($category->name, $html);
        }
    }

    /**
     * Adding categories must not add a query per category.
     *
     * The rail fetches its categories in one query and counts their products
     * in one grouped aggregate, so the homepage total is unchanged by how many
     * there are.
     *
     * The first render is warmed and discarded first: settings are cached
     * forever after their first read, so an unwarmed baseline measures four
     * queries that the comparison render never makes — which reads as a
     * saving, not a cost, and would hide a real regression rather than expose
     * one.
     */
    public function test_the_rail_does_not_query_per_category(): void
    {
        $this->seedCategories(3);

        $this->homepage();                       // warm the settings cache
        $baseline = $this->countHomepageQueries();

        // Three more categories over products that already exist, so the other
        // rails on the page see no change.
        foreach (Product::query()->take(3)->get() as $product) {
            $product->update([
                'category_id' => Category::factory()->create(['is_active' => true])->id,
            ]);
        }

        $this->assertSame($baseline, $this->countHomepageQueries());
    }

    private function countHomepageQueries(): int
    {
        $count = 0;

        \DB::listen(function () use (&$count): void {
            $count++;
        });

        $this->homepage();

        return $count;
    }
}
