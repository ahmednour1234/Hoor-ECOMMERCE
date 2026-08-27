<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;

/**
 * Applies the gallery changes submitted by the product form: new uploads,
 * per-image metadata (alt text, order), removals, and the primary selection.
 *
 * File deletion is deferred to after commit by ImageService, so a failure
 * anywhere in the surrounding transaction leaves both rows and files intact.
 */
class SyncProductImagesAction
{
    public function __construct(private readonly ImageService $images)
    {
    }

    /**
     * @param  list<UploadedFile>  $uploads          Newly selected files.
     * @param  array<int, array{alt_ar?: string|null, alt_en?: string|null, sort_order?: int|string|null}>  $meta
     *                                                Keyed by existing image id.
     * @param  list<int>  $removedIds                 Images the admin removed.
     * @param  int|string|null  $primary              Existing image id, or "new:{index}"
     *                                                when a fresh upload was chosen.
     * @return array{created: int, updated: int, deleted: int}
     */
    public function execute(
        Product $product,
        array $uploads = [],
        array $meta = [],
        array $removedIds = [],
        int|string|null $primary = null,
    ): array {
        $deleted = $this->removeImages($product, $removedIds);
        $updated = $this->applyMetadata($product, $meta);
        $created = $this->storeUploads($product, $uploads, $meta);

        $this->resolvePrimary($product, $primary, $created);

        return ['created' => count($created), 'updated' => $updated, 'deleted' => $deleted];
    }

    /**
     * @param  list<int>  $removedIds
     */
    private function removeImages(Product $product, array $removedIds): int
    {
        if ($removedIds === []) {
            return 0;
        }

        $images = $product->images()->whereIn('id', $removedIds)->get();

        foreach ($images as $image) {
            // Row first, file after commit: an orphaned file is recoverable,
            // a row pointing at a deleted file is not.
            $image->delete();
            $this->images->deleteAfterCommit($image->path);
        }

        return $images->count();
    }

    /**
     * @param  array<int, array<string, mixed>>  $meta
     */
    private function applyMetadata(Product $product, array $meta): int
    {
        $existing = $product->images()->get()->keyBy('id');
        $updated = 0;

        foreach ($meta as $id => $values) {
            $image = $existing->get((int) $id);

            if ($image === null) {
                continue;
            }

            $image->update([
                'alt_ar'     => $values['alt_ar'] ?? $image->alt_ar,
                'alt_en'     => $values['alt_en'] ?? $image->alt_en,
                'sort_order' => (int) ($values['sort_order'] ?? $image->sort_order),
            ]);

            $updated++;
        }

        return $updated;
    }

    /**
     * @param  list<UploadedFile>  $uploads
     * @param  array<int, array<string, mixed>>  $meta
     * @return array<int, ProductImage>  Keyed by upload index.
     */
    private function storeUploads(Product $product, array $uploads, array $meta): array
    {
        if ($uploads === []) {
            return [];
        }

        $directory = $this->images->directoryFor('products');
        $nextOrder = (int) $product->images()->max('sort_order') + 1;
        $created = [];

        foreach ($uploads as $index => $upload) {
            if (! $upload instanceof UploadedFile) {
                continue;
            }

            $path = $this->images->store($upload, $directory);

            $created[$index] = $product->images()->create([
                'path'       => $path,
                'alt_ar'     => $meta['new'][$index]['alt_ar'] ?? $product->name_ar,
                'alt_en'     => $meta['new'][$index]['alt_en'] ?? $product->name_en,
                'sort_order' => $nextOrder++,
                'is_primary' => false,
            ]);

            // Tracked so the owning service can discard it if the work fails.
            $this->images->trackPending($path);
        }

        return $created;
    }

    /**
     * Ensure exactly one image carries the primary flag.
     *
     * @param  array<int, ProductImage>  $created
     */
    private function resolvePrimary(Product $product, int|string|null $primary, array $created): void
    {
        $target = null;

        if (is_string($primary) && str_starts_with($primary, 'new:')) {
            $target = $created[(int) substr($primary, 4)] ?? null;
        } elseif (filled($primary)) {
            $target = $product->images()->whereKey($primary)->first();
        }

        // Fall back to the first remaining image so a product is never left
        // without a card thumbnail.
        $target ??= $product->images()->orderBy('sort_order')->first();

        if ($target === null) {
            return;
        }

        // ProductImage::saved() clears the flag on the product's other images.
        $target->forceFill(['is_primary' => true])->save();
    }
}
