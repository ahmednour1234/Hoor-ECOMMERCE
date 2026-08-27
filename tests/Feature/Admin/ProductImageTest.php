<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\ImageService;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Image handling is where a mistake destroys data that cannot be rolled back,
 * so these tests cover the storage side as closely as the database side.
 */
class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->admin()->create();
        $this->category = Category::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name_en'     => 'Denim Jacket',
            'name_ar'     => 'جاكيت دنيم',
            'category_id' => $this->category->id,
            'base_price'  => 1450,
            'status'      => ProductStatus::Published->value,
            'variants'    => [],
        ], $overrides);
    }

    public function test_uploaded_images_are_written_to_disk_and_only_paths_are_stored(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store', ['locale' => 'en']), $this->payload([
                'images' => [
                    UploadedFile::fake()->image('front.jpg'),
                    UploadedFile::fake()->image('back.jpg'),
                ],
            ]))
            ->assertSessionHasNoErrors();

        $product = Product::query()->firstOrFail();
        $images = $product->images()->orderBy('sort_order')->get();

        $this->assertCount(2, $images);

        foreach ($images as $image) {
            Storage::disk('public')->assertExists($image->path);

            // The column holds a path, never binary data.
            $this->assertStringStartsWith('products/', $image->path);
            $this->assertLessThan(255, strlen($image->path));
        }
    }

    public function test_the_first_upload_becomes_primary_when_none_is_chosen(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store', ['locale' => 'en']), $this->payload([
                'images' => [
                    UploadedFile::fake()->image('a.jpg'),
                    UploadedFile::fake()->image('b.jpg'),
                ],
            ]))
            ->assertSessionHasNoErrors();

        $product = Product::query()->firstOrFail();

        $this->assertSame(1, $product->images()->where('is_primary', true)->count());
        $this->assertNotNull($product->primaryImage);
    }

    public function test_admin_can_choose_which_existing_image_is_primary(): void
    {
        $product = Product::factory()->for($this->category)->create();
        $first = ProductImage::factory()->for($product)->primary()->create();
        $second = ProductImage::factory()->for($product)->create();

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', ['locale' => 'en', 'product' => $product]), $this->payload([
                'slug'          => $product->slug,
                'primary_image' => $second->id,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
        $this->assertSame(1, $product->images()->where('is_primary', true)->count());
    }

    public function test_image_order_and_alt_text_can_be_edited(): void
    {
        $product = Product::factory()->for($this->category)->create();
        $image = ProductImage::factory()->for($product)->primary()->create(['sort_order' => 0]);

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', ['locale' => 'en', 'product' => $product]), $this->payload([
                'slug'       => $product->slug,
                'image_meta' => [
                    $image->id => [
                        'sort_order' => 7,
                        'alt_en'     => 'Front view',
                        'alt_ar'     => 'منظر أمامي',
                    ],
                ],
            ]))
            ->assertSessionHasNoErrors();

        $image->refresh();

        $this->assertSame(7, $image->sort_order);
        $this->assertSame('Front view', $image->alt_en);
        $this->assertSame('منظر أمامي', $image->alt_ar);
    }

    public function test_removing_an_image_deletes_both_the_row_and_the_file(): void
    {
        $product = Product::factory()->for($this->category)->create();

        // Put a real file behind the row so the orphan cleanup is observable.
        $path = 'products/to-delete.jpg';
        Storage::disk('public')->put($path, 'binary');

        $image = ProductImage::factory()->for($product)->primary()->create(['path' => $path]);

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', ['locale' => 'en', 'product' => $product]), $this->payload([
                'slug'           => $product->slug,
                'removed_images' => [$image->id],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertModelMissing($image);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_files_are_kept_when_the_save_fails(): void
    {
        // A file already referenced by a surviving row must never be deleted
        // because an unrelated part of the same save blew up.
        $product = Product::factory()->for($this->category)->create();

        $path = 'products/keep-me.jpg';
        Storage::disk('public')->put($path, 'binary');
        ProductImage::factory()->for($product)->primary()->create(['path' => $path]);

        $service = app(ProductService::class);

        try {
            // An invalid category id makes the update throw mid-transaction.
            $service->update($product, [
                'name_en'     => 'Broken',
                'name_ar'     => 'معطل',
                'category_id' => 999999,
                'base_price'  => 100,
                'status'      => ProductStatus::Draft->value,
            ]);
        } catch (\Throwable) {
            // Expected.
        }

        Storage::disk('public')->assertExists($path);
    }

    public function test_new_uploads_are_discarded_when_the_transaction_fails(): void
    {
        $images = app(ImageService::class);

        $path = $images->store(UploadedFile::fake()->image('orphan.jpg'), 'products');
        $images->trackPending($path);

        Storage::disk('public')->assertExists($path);

        $images->discardPending();

        // Nothing references it, so the bytes must not linger.
        Storage::disk('public')->assertMissing($path);
    }

    public function test_committed_uploads_survive_the_pending_sweep(): void
    {
        $images = app(ImageService::class);

        $path = $images->store(UploadedFile::fake()->image('kept.jpg'), 'products');
        $images->trackPending($path);
        $images->commitPending();

        // Already committed, so a later discard must not touch it.
        $images->discardPending();

        Storage::disk('public')->assertExists($path);
    }

    public function test_oversized_and_non_image_uploads_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store', ['locale' => 'en']), $this->payload([
                'images' => [UploadedFile::fake()->create('malware.php', 100, 'application/x-php')],
            ]))
            ->assertSessionHasErrors('images.0');

        $this->assertSame(0, Product::query()->count());
    }

    public function test_permanent_delete_removes_every_file_the_product_owned(): void
    {
        $product = Product::factory()->for($this->category)->create();

        $paths = ['products/one.jpg', 'products/two.jpg'];

        foreach ($paths as $path) {
            Storage::disk('public')->put($path, 'binary');
            ProductImage::factory()->for($product)->create(['path' => $path]);
        }

        app(ProductService::class)->forceDelete($product);

        $this->assertDatabaseCount('product_images', 0);

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_soft_delete_keeps_the_files_for_a_possible_restore(): void
    {
        $product = Product::factory()->for($this->category)->create();

        $path = 'products/still-here.jpg';
        Storage::disk('public')->put($path, 'binary');
        ProductImage::factory()->for($product)->primary()->create(['path' => $path]);

        $this->actingAs($this->admin)
            ->delete(route('admin.products.destroy', ['locale' => 'en', 'product' => $product]));

        $this->assertSoftDeleted($product);
        Storage::disk('public')->assertExists($path);
    }
}
