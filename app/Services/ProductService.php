<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Product\SyncProductImagesAction;
use App\Actions\Product\SyncProductVariantsAction;
use App\Casts\Money;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Orchestrates the whole product write: attributes, variants and gallery are
 * saved as one unit so a product is never left half-updated.
 *
 * Uploaded files are written to disk before the transaction commits, so on
 * failure the service discards them explicitly — the database rolls itself
 * back, storage does not.
 */
class ProductService
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly SyncProductVariantsAction $syncVariants,
        private readonly SyncProductImagesAction $syncImages,
        private readonly ImageService $imageService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return $this->transact(function () use ($data): Product {
            $product = $this->products->create($this->attributesFrom($data));

            $this->syncRelations($product, $data);

            return $this->products->loadForForm($product->refresh());
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return $this->transact(function () use ($product, $data): Product {
            $this->products->update($product, $this->attributesFrom($data));

            $this->syncRelations($product, $data);

            return $this->products->loadForForm($product->refresh());
        });
    }

    /**
     * Soft-delete a product, keeping its images on disk.
     *
     * The product can be restored, and its gallery must survive with it; files
     * are only removed on a permanent delete.
     */
    public function delete(Product $product): void
    {
        $this->products->delete($product);
    }

    /**
     * Permanently remove a product together with every file it owns.
     */
    public function forceDelete(Product $product): void
    {
        $paths = $product->images()->pluck('path')->all();

        DB::transaction(function () use ($product): void {
            $product->images()->delete();
            $product->forceDelete();
        });

        // Rows are gone and committed, so the files are now genuinely orphaned.
        $this->imageService->deleteManyAfterCommit($paths);
    }

    /**
     * Run a unit of work, discarding any files written if it fails.
     *
     * @template T
     * @param  callable(): T  $work
     * @return T
     */
    private function transact(callable $work): mixed
    {
        try {
            $result = DB::transaction($work);
        } catch (Throwable $e) {
            $this->imageService->discardPending();

            throw $e;
        }

        $this->imageService->commitPending();

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncRelations(Product $product, array $data): void
    {
        if (array_key_exists('variants', $data)) {
            $this->syncVariants->execute($product, $data['variants'] ?? []);
        }

        $this->syncImages->execute(
            product: $product,
            uploads: $data['images'] ?? [],
            meta: $data['image_meta'] ?? [],
            removedIds: $data['removed_images'] ?? [],
            primary: $data['primary_image'] ?? null,
        );
    }

    /**
     * Map validated form input onto product columns.
     *
     * Prices arrive from the form in EGP and are converted to piastres here, so
     * no caller has to remember the storage unit.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributesFrom(array $data): array
    {
        $attributes = collect($data)->only([
            'category_id',
            'name_ar', 'name_en', 'slug',
            'short_description_ar', 'short_description_en',
            'description_ar', 'description_en',
            'status', 'is_featured', 'is_new',
            'fabric_ar', 'fabric_en',
            'care_ar', 'care_en',
            'meta_title_ar', 'meta_title_en',
            'meta_description_ar', 'meta_description_en',
        ])->all();

        $attributes['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $attributes['is_new'] = (bool) ($data['is_new'] ?? false);

        $attributes['base_price'] = Money::fromMajor($data['base_price']);
        $attributes['sale_price'] = filled($data['sale_price'] ?? null)
            ? Money::fromMajor($data['sale_price'])
            : null;

        return $attributes;
    }
}
