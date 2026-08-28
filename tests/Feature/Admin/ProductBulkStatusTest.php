<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Changing the status of several products at once.
 *
 * The case this exists for: an import lands fifty products as drafts, and
 * publishing them one at a time is fifty page loads.
 */
class ProductBulkStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    /**
     * @param  list<int>  $ids
     */
    private function apply(array $ids, string $action): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->patch(
            route('admin.products.bulk', ['locale' => 'en']),
            ['action' => $action, 'products' => $ids],
        );
    }

    public function test_several_products_are_published_together(): void
    {
        $products = Product::factory()->count(3)->draft()->create();

        $this->apply($products->pluck('id')->all(), 'published')
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        foreach ($products as $product) {
            $this->assertSame(ProductStatus::Published, $product->fresh()->status);
        }
    }

    public function test_only_the_selected_products_change(): void
    {
        $selected = Product::factory()->count(2)->draft()->create();
        $untouched = Product::factory()->draft()->create();

        $this->apply($selected->pluck('id')->all(), 'published');

        $this->assertSame(ProductStatus::Draft, $untouched->fresh()->status);
    }

    public function test_products_can_be_returned_to_draft_and_archived(): void
    {
        $product = Product::factory()->create(['status' => ProductStatus::Published]);

        $this->apply([$product->id], 'draft');
        $this->assertSame(ProductStatus::Draft, $product->fresh()->status);

        $this->apply([$product->id], 'archived');
        $this->assertSame(ProductStatus::Archived, $product->fresh()->status);
    }

    /**
     * published_at is the date the shop first offered the piece. The homepage's
     * "new in" rail orders by it, so re-publishing an old product after a spell
     * in drafts must not push it back to the front.
     */
    public function test_the_first_publish_is_stamped_and_later_ones_are_not(): void
    {
        $product = Product::factory()->draft()->create(['published_at' => null]);

        $this->apply([$product->id], 'published');

        $firstPublished = $product->fresh()->published_at;
        $this->assertNotNull($firstPublished);

        // Back to draft and out again, a month later.
        $this->apply([$product->id], 'draft');
        $this->travel(30)->days();
        $this->apply([$product->id], 'published');

        $this->assertTrue(
            $firstPublished->equalTo($product->fresh()->published_at),
            'Re-publishing should not restamp the original publish date.',
        );
    }

    // ------------------------------------------------------------ Refusing

    public function test_an_unknown_status_is_refused(): void
    {
        $product = Product::factory()->draft()->create();

        $this->apply([$product->id], 'deleted')->assertSessionHasErrors('action');

        $this->assertSame(ProductStatus::Draft, $product->fresh()->status);
    }

    public function test_selecting_nothing_is_refused(): void
    {
        $this->apply([], 'published')->assertSessionHasErrors('products');
    }

    public function test_a_product_that_does_not_exist_is_refused(): void
    {
        $product = Product::factory()->draft()->create();

        $this->apply([$product->id, 999999], 'published')
            ->assertSessionHasErrors('products.1');

        // And the real one is left alone, since nothing was applied.
        $this->assertSame(ProductStatus::Draft, $product->fresh()->status);
    }

    /**
     * A bound on the work one click can cause, and on a crafted payload.
     */
    public function test_more_than_the_limit_is_refused(): void
    {
        $this->apply(range(1, 201), 'published')->assertSessionHasErrors('products');
    }

    public function test_a_customer_cannot_change_statuses(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->draft()->create();

        $this->actingAs($customer)
            ->patch(route('admin.products.bulk', ['locale' => 'en']), [
                'action'   => 'published',
                'products' => [$product->id],
            ])
            ->assertForbidden();

        $this->assertSame(ProductStatus::Draft, $product->fresh()->status);
    }

    // ---------------------------------------------------------------- The UI

    public function test_the_list_offers_a_checkbox_for_each_product(): void
    {
        Product::factory()->count(3)->create();

        $html = $this->actingAs($this->admin)
            ->get(route('admin.products.index', ['locale' => 'en']))
            ->assertOk()
            ->getContent();

        $this->assertSame(3, substr_count($html, 'name="products[]"'));
    }

    public function test_the_list_renders_in_both_locales(): void
    {
        Product::factory()->count(2)->create();

        foreach (['en', 'ar'] as $locale) {
            $this->actingAs($this->admin)
                ->get(route('admin.products.index', ['locale' => $locale]))
                ->assertOk();
        }
    }
}
